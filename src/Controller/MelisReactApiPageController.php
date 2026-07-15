<?php

namespace MelisCms\Controller;

use MelisReactApi\Controller\CapabilityGuardTrait;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use Laminas\Session\Container as SessionContainer;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * API REST de l'ÉDITEUR DE PAGE CMS (meliscms_page) pour le back-office React.
 *
 * Objectif clé : exposer la STRUCTURE MODULAIRE de l'éditeur (onglets + boutons) telle
 * qu'assemblée par la FUSION DE CONFIG Laminas sous
 *   plugins/meliscms/interface/meliscms_page/interface/meliscms_tabs        (onglets)
 *   plugins/meliscms/interface/meliscms_page_actions                        (boutons)
 * Comme cette config est mergée à l'init de l'app, les contributions des AUTRES modules
 * (small-business : Versioning/Commentaires/Workflow/Unlock ; page-historic : Historique ;
 * page-analytics : Analytics ; page-script-editor : Scripts) apparaissent AUTOMATIQUEMENT
 * ici — la coquille React n'a rien à coder par module. La modularité est gratuite.
 *
 * Chaque onglet/bouton porte son `melisKey` : la coquille React charge son contenu legacy
 * via /melis/react-tool-page?key=<melisKey>&idPage=X (mécanisme react-override), et l'onglet
 * Édition (load:iframe → renderMode=melis) garde le drag'n'drop legacy tel quel.
 *
 * Le gating capabilities (onglets ET boutons) est porté côté React (useCaps) + /me ; ce
 * contrôleur annote juste chaque entrée avec sa clé de capacité (`cap`).
 *
 * Modularité (règle d'or) : ce contrôleur vit dans MelisCms (le module PROPRIÉTAIRE de
 * l'outil page), routes déclarées dans melis-cms/config/react-api.php, mergées via
 * MelisCms\Module::getConfig(). Le legacy /melis reste 100% intact (aucune modif legacy).
 *
 * Routes :
 *   GET /melis/react-api/cms-page/structure?idPage=X → { header, tabs[], buttons[] }
 */
class MelisReactApiPageController extends MelisAbstractActionController
{
    use CapabilityGuardTrait;

    /** melisKey de l'outil page — clé d'accès (canAccess) ET clé racine des capabilities. */
    private const MELIS_KEY = 'meliscms_page';

    /** Onglet dont le contenu reste legacy drag'n'drop (rendu renderMode=melis en iframe). */
    private const EDITION_TAB_KEY = 'meliscms_page_edition';

    /**
     * Map onglet melisKey → capacité (pour le gating React). Les onglets non listés (modulaires
     * inconnus) prennent leur propre melisKey comme cap → default-allow tant que non déclaré.
     */
    private const TAB_CAP = [
        'meliscms_page_edition'    => 'edition',
        'meliscms_page_properties' => 'properties',
        'meliscms_page_seo'        => 'seo',
        'meliscms_page_languages'  => 'languages',
    ];

    /** Map bouton melisKey → capacité (pour le gating React). */
    private const BTN_CAP = [
        'meliscms_page_action_new'       => 'create',
        'meliscms_page_action_save'      => 'save',
        'meliscms_page_action_clear'     => 'save',
        'meliscms_page_action_publish'   => 'publish',
        'meliscms_page_action_delete'    => 'delete',
        'meliscms_page_action_duplicate' => 'duplicate',
        'melissb_page_action_workflow'   => 'workflow',
    ];

    // ─── GET /cms-page/structure ─────────────────────────────────────────────────

    public function structureAction(): HttpResponse
    {
        // NB : l'éditeur de page ne s'ouvre pas via une clé de menu `meliscms_page` mais via
        // l'arbre du site avec des droits PAR-PAGE (section usr_rights `meliscms_pages`).
        // La structure ne renvoie que le CHROME (labels/icônes d'onglets+boutons) → on exige
        // juste l'authentification ; l'accès réel par-page reste appliqué par le contenu legacy
        // (react-tool-page) et par les endpoints d'écriture (à venir) qui vérifient la page ciblée.
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

        try {
            $idPage = (int) $this->params()->fromQuery('idPage', 0);
            $config = $this->getServiceManager()->get('MelisCoreConfig');

            $tabsCfg = $config->getItem('/meliscms/interface/meliscms_page/interface/meliscms_tabs');
            $btnsCfg = $config->getItem('/meliscms/interface/meliscms_page_actions');

            return $this->jsonResponse([
                'success' => true,
                'data'    => [
                    'idPage'  => $idPage,
                    'header'  => $this->buildHeader($idPage),
                    'tabs'    => $this->buildEntries($tabsCfg['interface'] ?? [], self::TAB_CAP, true),
                    'buttons' => $this->buildEntries($btnsCfg['interface'] ?? [], self::BTN_CAP, false),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Onglet PROPRIÉTÉS ────────────────────────────────────────────────────────

    /** GET /cms-page/properties?idPage=X → valeurs courantes (version 'saved', fallback 'published'). */
    public function propertiesAction(): HttpResponse
    {
        if (!$this->isAuthenticated()) { return $this->jsonResponse(['success' => false, 'error' => 'Unauthenticated'], 401); }
        try {
            $idPage = (int) $this->params()->fromQuery('idPage', 0);
            $tree = $this->pageTree($idPage);
            if (!$tree) { return $this->jsonResponse(['success' => false, 'error' => 'Page not found'], 404); }
            $db = $this->db();
            $style = iterator_to_array($db->query('SELECT pstyle_style_id FROM melis_cms_page_style WHERE pstyle_page_id = ? LIMIT 1', [$idPage]));
            return $this->jsonResponse(['success' => true, 'data' => [
                'idPage'       => $idPage,
                'name'         => $tree->page_name ?? '',
                'type'         => $tree->page_type ?? 'PAGE',
                'menu'         => $tree->page_menu ?? 'LINK',
                'templateId'   => (int) ($tree->page_tpl_id ?? 0),
                'langId'       => (int) ($tree->plang_lang_id ?? 0),
                'styleId'      => (int) ($style[0]['pstyle_style_id'] ?? 0),
                'taxonomy'     => $tree->page_taxonomy ?? '',
                'creationDate' => $tree->page_creation_date ?? null,
            ]]);
        } catch (\Throwable $e) { return $this->errorResponse($e); }
    }

    /** POST /cms-page/properties/save — UPDATE ciblé des colonnes de propriétés (préserve page_content). */
    public function savePropertiesAction(): HttpResponse
    {
        if (!$this->isAuthenticated()) { return $this->jsonResponse(['success' => false, 'error' => 'Unauthenticated'], 401); }
        try {
            $in = $this->jsonBody();
            $idPage = (int) ($in['idPage'] ?? 0);
            if ($idPage <= 0) { return $this->jsonResponse(['success' => false, 'error' => 'idPage requis'], 400); }
            $name = trim((string) ($in['name'] ?? ''));
            $tplId = (int) ($in['templateId'] ?? 0);
            if ($name === '') { return $this->jsonResponse(['success' => false, 'error' => 'Le nom est requis'], 400); }
            if ($tplId <= 0) { return $this->jsonResponse(['success' => false, 'error' => 'Le template est requis'], 400); }

            $sm = $this->getServiceManager();
            $savedTable = $sm->get('MelisEngineTablePageSaved');
            $existing   = $savedTable->getEntryById($idPage)->current();
            if (!$existing) {
                // pas de brouillon → copier la version publiée pour ne pas perdre le contenu
                $pub = $sm->get('MelisEngineTablePagePublished')->getEntryById($idPage)->current();
                if ($pub) { $savedTable->save(array_diff_key(get_object_vars($pub), ['page_id' => 1]), $idPage); }
            }
            $eng = $sm->get('MelisEnginePage');
            $datas = [
                'page_id'        => $idPage,
                'page_name'      => $name,
                'page_type'      => (string) ($in['type'] ?? 'PAGE'),
                'page_menu'      => (string) ($in['menu'] ?? 'LINK'),
                'page_tpl_id'    => $tplId,
                'page_taxonomy'  => (string) ($in['taxonomy'] ?? ''),
                'page_edit_date' => date('Y-m-d H:i:s'),
                'page_last_user_id' => (int) ($sm->get('MelisCoreAuth')->getIdentity()->usr_id ?? 0),
            ];
            // événements legacy (compat listeners) autour du save
            $em = $this->getEventManager();
            $em->trigger('meliscms_page_saveproperties_start', $this, ['idPage' => $idPage, 'datas' => $datas]);
            $savedTable->save($datas, $idPage);

            // style (table dédiée) — upsert
            $styleId = (int) ($in['styleId'] ?? 0);
            $styleTable = $sm->get('MelisEngineTablePageStyle');
            $curStyle = $styleTable->getEntryByField('pstyle_page_id', $idPage)->current();
            if ($styleId > 0) {
                $styleTable->savePageStyle(['pstyle_page_id' => $idPage, 'pstyle_style_id' => $styleId], $curStyle ? $idPage : null);
            } elseif ($curStyle) {
                $styleTable->deleteByField('pstyle_page_id', $idPage);
            }
            $em->trigger('meliscms_page_saveproperties_end', $this, ['idPage' => $idPage]);
            if (method_exists($eng, 'delDataPageDatasByPageId')) { try { $eng->delDataPageDatasByPageId($idPage); } catch (\Throwable) {} }

            return $this->jsonResponse(['success' => true, 'data' => ['idPage' => $idPage, 'name' => $name]]);
        } catch (\Throwable $e) { return $this->errorResponse($e); }
    }

    // ─── Onglet SEO ───────────────────────────────────────────────────────────────

    /** GET /cms-page/seo?idPage=X */
    public function seoAction(): HttpResponse
    {
        if (!$this->isAuthenticated()) { return $this->jsonResponse(['success' => false, 'error' => 'Unauthenticated'], 401); }
        try {
            $idPage = (int) $this->params()->fromQuery('idPage', 0);
            $rows = iterator_to_array($this->db()->query(
                'SELECT pseo_url, pseo_url_redirect, pseo_url_301, pseo_meta_title, pseo_meta_description, pseo_canonical FROM melis_cms_page_seo WHERE pseo_id = ? LIMIT 1', [$idPage]));
            $r = $rows[0] ?? [];
            return $this->jsonResponse(['success' => true, 'data' => [
                'idPage'      => $idPage,
                'url'         => $r['pseo_url'] ?? '',
                'urlRedirect' => $r['pseo_url_redirect'] ?? '',
                'url301'      => $r['pseo_url_301'] ?? '',
                'metaTitle'   => $r['pseo_meta_title'] ?? '',
                'metaDesc'    => $r['pseo_meta_description'] ?? '',
                'canonical'   => $r['pseo_canonical'] ?? '',
            ]]);
        } catch (\Throwable $e) { return $this->errorResponse($e); }
    }

    /** POST /cms-page/seo/save — upsert melis_cms_page_seo ; supprime si tout vide ; URL unique + nettoyée. */
    public function saveSeoAction(): HttpResponse
    {
        if (!$this->isAuthenticated()) { return $this->jsonResponse(['success' => false, 'error' => 'Unauthenticated'], 401); }
        try {
            $in = $this->jsonBody();
            $idPage = (int) ($in['idPage'] ?? 0);
            if ($idPage <= 0) { return $this->jsonResponse(['success' => false, 'error' => 'idPage requis'], 400); }
            $eng  = $this->getServiceManager()->get('MelisEnginePage');
            $seoT = $this->getServiceManager()->get('MelisEngineTablePageSeo');

            // Nettoyage URL identique au legacy : espaces→-, retrait / initial, chars spéciaux→-, minuscule
            $url = (string) ($in['url'] ?? '');
            if ($url !== '') {
                $url = preg_replace('/\s+/', '-', trim($url));
                $url = ltrim($url, '/');
                $url = preg_replace('/[^A-Za-z0-9\/\-]+/', '-', $url);
                $url = mb_strtolower($url);
                if (method_exists($eng, 'cleanString')) { try { $url = $eng->cleanString($url); } catch (\Throwable) {} }
            }
            // Unicité URL (autre page)
            if ($url !== '') {
                $dupe = iterator_to_array($this->db()->query('SELECT pseo_id FROM melis_cms_page_seo WHERE pseo_url = ? AND pseo_id <> ? LIMIT 1', [$url, $idPage]));
                if (!empty($dupe)) { return $this->jsonResponse(['success' => false, 'error' => "Cette URL est déjà utilisée par la page #" . $dupe[0]['pseo_id']], 400); }
            }
            $datas = [
                'pseo_id'               => $idPage,
                'pseo_url'              => $url,
                'pseo_url_redirect'    => (string) ($in['urlRedirect'] ?? ''),
                'pseo_url_301'         => (string) ($in['url301'] ?? ''),
                'pseo_meta_title'      => (string) ($in['metaTitle'] ?? ''),
                'pseo_meta_description'=> (string) ($in['metaDesc'] ?? ''),
                'pseo_canonical'       => (string) ($in['canonical'] ?? ''),
            ];
            $allEmpty = ($url === '' && $datas['pseo_url_redirect'] === '' && $datas['pseo_url_301'] === '' && $datas['pseo_meta_title'] === '' && $datas['pseo_meta_description'] === '' && $datas['pseo_canonical'] === '');
            $em = $this->getEventManager();
            $em->trigger('meliscms_page_saveseo_start', $this, ['idPage' => $idPage]);
            if ($allEmpty) { $seoT->deleteById($idPage); }
            else { $seoT->save($datas, $idPage); }
            $em->trigger('meliscms_page_saveseo_end', $this, ['idPage' => $idPage]);
            return $this->jsonResponse(['success' => true, 'data' => ['idPage' => $idPage]]);
        } catch (\Throwable $e) { return $this->errorResponse($e); }
    }

    // ─── Onglet LANGAGES ──────────────────────────────────────────────────────────

    /** GET /cms-page/languages?idPage=X → versions existantes + langues créables. */
    public function languagesAction(): HttpResponse
    {
        if (!$this->isAuthenticated()) { return $this->jsonResponse(['success' => false, 'error' => 'Unauthenticated'], 401); }
        try {
            $idPage = (int) $this->params()->fromQuery('idPage', 0);
            $db = $this->db();
            $me = iterator_to_array($db->query('SELECT plang_page_id_initial FROM melis_cms_page_lang WHERE plang_page_id = ? LIMIT 1', [$idPage]));
            $initial = (int) ($me[0]['plang_page_id_initial'] ?? $idPage);
            // versions existantes (pages sœurs) + libellé langue
            $versions = iterator_to_array($db->query(
                'SELECT pl.plang_page_id AS pageId, pl.plang_lang_id AS langId, l.lang_cms_name AS langName, l.lang_cms_locale AS locale, t.page_name AS pageName
                 FROM melis_cms_page_lang pl
                 LEFT JOIN melis_cms_lang l ON l.lang_cms_id = pl.plang_lang_id
                 LEFT JOIN melis_cms_page_tree tr ON tr.tree_page_id = pl.plang_page_id
                 LEFT JOIN melis_cms_page_published t ON t.page_id = pl.plang_page_id
                 WHERE pl.plang_page_id_initial = ?', [$initial]));
            $usedLangs = array_map(fn($v) => (int) $v['langId'], $versions);
            // langues du site créables (pas déjà utilisées)
            $siteId = $this->siteIdForPage($idPage);
            $siteLangs = $siteId ? iterator_to_array($db->query(
                'SELECT l.lang_cms_id AS id, l.lang_cms_name AS name, l.lang_cms_locale AS locale
                 FROM melis_cms_site_langs sl JOIN melis_cms_lang l ON l.lang_cms_id = sl.slang_lang_id
                 WHERE sl.slang_site_id = ?', [$siteId])) : [];
            $creatable = array_values(array_filter($siteLangs, fn($l) => !in_array((int) $l['id'], $usedLangs, true)));
            return $this->jsonResponse(['success' => true, 'data' => [
                'idPage' => $idPage, 'initial' => $initial, 'versions' => $versions, 'creatable' => $creatable,
            ]]);
        } catch (\Throwable $e) { return $this->errorResponse($e); }
    }

    // ─── Arbre : chemin vers une page ─────────────────────────────────────────────

    /**
     * GET /cms-page/ancestors?idPage=X → { ancestors: [id_racine, …, id_parent] }.
     * Remonte melis_cms_page_tree.tree_father_page_id jusqu'à la racine (father 0). Sert à la
     * coquille React pour DÉPLOYER l'arbre du menu jusqu'à la page en cours après un reload.
     */
    public function ancestorsAction(): HttpResponse
    {
        if (!$this->isAuthenticated()) { return $this->jsonResponse(['success' => false, 'error' => 'Unauthenticated'], 401); }
        try {
            $idPage = (int) $this->params()->fromQuery('idPage', 0);
            $db = $this->db();
            $chain = [];
            $cur = $idPage;
            $guard = 0;
            while ($cur > 0 && $guard++ < 100) {
                $rows = iterator_to_array($db->query('SELECT tree_father_page_id FROM melis_cms_page_tree WHERE tree_page_id = ? LIMIT 1', [$cur]));
                if (empty($rows)) { break; }
                $father = (int) ($rows[0]['tree_father_page_id'] ?? 0);
                if ($father <= 0) { break; }
                array_unshift($chain, $father); // racine en tête
                $cur = $father;
            }
            return $this->jsonResponse(['success' => true, 'data' => ['idPage' => $idPage, 'ancestors' => $chain]]);
        } catch (\Throwable $e) { return $this->errorResponse($e); }
    }

    // ─── Références (listes déroulantes) ──────────────────────────────────────────

    /** GET /cms-page/refs?idPage=X → templates (du site), langues, styles (du site), enums type/menu. */
    public function refsAction(): HttpResponse
    {
        if (!$this->isAuthenticated()) { return $this->jsonResponse(['success' => false, 'error' => 'Unauthenticated'], 401); }
        try {
            $idPage = (int) $this->params()->fromQuery('idPage', 0);
            $db = $this->db();
            $siteId = $this->siteIdForPage($idPage);
            $templates = iterator_to_array($db->query(
                'SELECT t.tpl_id AS id, t.tpl_name AS name FROM melis_cms_template t' . ($siteId ? ' WHERE t.tpl_site_id = ?' : ''),
                $siteId ? [$siteId] : []));
            $languages = iterator_to_array($db->query('SELECT lang_cms_id AS id, lang_cms_name AS name, lang_cms_locale AS locale FROM melis_cms_lang ORDER BY lang_cms_name', []));
            $styles = $siteId ? iterator_to_array($db->query('SELECT style_id AS id, style_name AS name FROM melis_cms_style WHERE style_site_id = ? AND style_status = 1 ORDER BY style_name', [$siteId])) : [];
            return $this->jsonResponse(['success' => true, 'data' => [
                'templates' => $templates, 'languages' => $languages, 'styles' => $styles,
                'types' => ['SITE', 'FOLDER', 'PAGE'], 'menus' => ['LINK', 'NOLINK', 'NONE'],
            ]]);
        } catch (\Throwable $e) { return $this->errorResponse($e); }
    }

    // ─── helpers ─────────────────────────────────────────────────────────────────

    private function db(): \Laminas\Db\Adapter\AdapterInterface
    {
        return $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
    }

    private function jsonBody(): array
    {
        $raw = (string) $this->getRequest()->getContent();
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /** Objet pageTree (saved, fallback published) ou null. */
    private function pageTree(int $idPage): ?object
    {
        $eng = $this->getServiceManager()->get('MelisEnginePage');
        $p = $eng->getDatasPage($idPage, 'saved');
        $tree = $p ? $p->getMelisPageTree() : null;
        if (empty($tree)) { $p = $eng->getDatasPage($idPage, 'published'); $tree = $p ? $p->getMelisPageTree() : null; }
        return $tree ?: null;
    }

    /** site_id d'une page (via l'arbre) ou 0. */
    private function siteIdForPage(int $idPage): int
    {
        try {
            $site = $this->getServiceManager()->get('MelisEngineTree')->getSiteByPageId($idPage);
            return (int) ($site->site_id ?? 0);
        } catch (\Throwable) { return 0; }
    }

    /**
     * Transforme la sous-config `interface` (onglets ou boutons) en liste ordonnée d'entrées
     * consommables par React. Préserve l'ORDRE de la config (= ordre d'affichage legacy).
     * @param array<string,array> $interface  clés = melisKey, valeurs = {conf,forward,interface?}
     * @param array<string,string> $capMap    melisKey → capacité (gating React)
     * @param bool $isTab                      true = onglet (drapeau iframe/edition), false = bouton
     * @return array<int,array>
     */
    private function buildEntries(array $interface, array $capMap, bool $isTab): array
    {
        $out = [];
        foreach ($interface as $melisKey => $node) {
            $conf = $node['conf'] ?? [];
            // Un noeud `type`-link résolu par getItem peut porter son melisKey dans conf.
            $key = $conf['melisKey'] ?? (is_string($melisKey) ? $melisKey : '');
            if ($key === '') { continue; }

            $entry = [
                'key'    => $key,
                'label'  => $this->translate($conf['name'] ?? $key),
                'icon'   => $conf['icon'] ?? null,
                'cap'    => $capMap[$key] ?? $key,          // capacité pour le gating React
                'iframe' => $isTab ? (($conf['load'] ?? '') === 'iframe' || $key === self::EDITION_TAB_KEY) : null,
                'edition'=> $isTab ? ($key === self::EDITION_TAB_KEY) : null,
            ];
            // Boutons dropdown (Voir → preview/seeonline ; Affichage → mobile/tablet/desktop) :
            // remonter les sous-items pour que la barre React native les rende en menu.
            if (!$isTab && !empty($node['interface']) && is_array($node['interface'])) {
                $children = [];
                foreach ($node['interface'] as $ck => $cnode) {
                    $cconf = $cnode['conf'] ?? [];
                    $ckey  = $cconf['melisKey'] ?? (is_string($ck) ? $ck : '');
                    if ($ckey === '') { continue; }
                    $children[] = [
                        'key'   => $ckey,
                        'label' => $this->translate($cconf['name'] ?? $ckey),
                        'icon'  => $cconf['icon'] ?? null,
                    ];
                }
                if ($children) { $entry['children'] = $children; }
            }
            $out[] = $entry;
        }
        return $out;
    }

    /** En-tête de l'éditeur : nom de page, statut (brouillon/publié), dernière édition + auteur. */
    private function buildHeader(int $idPage): array
    {
        $header = [
            'idPage'   => $idPage,
            'pageName' => null,
            'status'   => null,      // 'draft' | 'published' | 'unpublished' | null
            'hasDraft' => false,
            'online'   => false,     // page EN LIGNE = version publiée existante avec page_status=1 (pilote le switch Publié/Dépublié)
            'editDate' => null,
            'editor'   => null,
        ];
        if ($idPage <= 0) { return $header; }

        try {
            $engine = $this->getServiceManager()->get('MelisEnginePage');
            $saved  = $engine->getDatasPage($idPage, 'saved');
            $tree   = $saved ? $saved->getMelisPageTree() : null;
            $header['hasDraft'] = !empty($saved) && $saved->getType() === 'saved';

            // État EN LIGNE réel : lu sur la version PUBLIÉE (indépendant de l'existence d'un brouillon).
            $pub     = $engine->getDatasPage($idPage, 'published');
            $pubTree = $pub ? $pub->getMelisPageTree() : null;
            $header['online'] = !empty($pubTree) && (int) ($pubTree->page_status ?? 0) === 1;

            if (empty($tree)) {
                // pas de brouillon → lire la version publiée pour l'en-tête
                $tree = $pubTree;
            }
            if (!empty($tree)) {
                $header['pageName'] = $tree->page_name ?? null;
                $status = isset($tree->page_status) ? (int) $tree->page_status : null;
                $header['status']   = $header['hasDraft'] ? 'draft' : ($status === 1 ? 'published' : ($status === 0 ? 'unpublished' : null));
                $header['editDate'] = $tree->page_edit_date ?? null;
                $header['editor']   = $this->editorName($tree->page_last_user_id ?? null);
            }
        } catch (\Throwable) { /* en-tête best-effort */ }

        return $header;
    }

    private function editorName($userId): ?string
    {
        if (empty($userId)) { return null; }
        try {
            $user = $this->getServiceManager()->get('MelisCoreTableUser')->getEntryById((int) $userId)->current();
            if ($user) {
                $name = trim(($user->usr_firstname ?? '') . ' ' . ($user->usr_lastname ?? ''));
                return $name !== '' ? $name : ($user->usr_login ?? null);
            }
        } catch (\Throwable) {}
        return null;
    }

    /** Traduit une clé tr_… selon la locale de session ; renvoie tel quel si déjà traduit. */
    private function translate(?string $key): string
    {
        $key = (string) $key;
        if ($key === '' || strncmp($key, 'tr_', 3) !== 0) { return $key; }
        try {
            return (string) $this->getServiceManager()->get('translator')->translate($key);
        } catch (\Throwable) { return $key; }
    }

    private function isAuthenticated(): bool
    {
        return $this->getServiceManager()->get('MelisCoreAuth')->hasIdentity();
    }

    private function denyUnlessAccess(): ?HttpResponse
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Unauthenticated'], 401);
        }
        try {
            if (!$this->getServiceManager()->get('MelisCoreRights')->canAccess(self::MELIS_KEY)) {
                return $this->jsonResponse(['success' => false, 'error' => 'Forbidden'], 403);
            }
        } catch (\Throwable) {}
        return null;
    }

    private function jsonResponse(array $data, int $status = 200): HttpResponse
    {
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        $response->setStatusCode($status);
        $response->getHeaders()->addHeaders([
            'Content-Type'           => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response;
    }

    private function errorResponse(\Throwable $e, int $status = 500): HttpResponse
    {
        return $this->jsonResponse([
            'success' => false,
            'error'   => $e->getMessage(),
            'file'    => basename($e->getFile()) . ':' . $e->getLine(),
        ], $status);
    }
}
