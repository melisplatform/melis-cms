<?php

namespace MelisCms\Controller;

use MelisReactApi\Controller\CapabilityGuardTrait;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * API REST pour l'outil « Mini-Template Manager » de MelisCms.
 *
 * Les mini-templates sont des fichiers .phtml sur disque (pas de table DB primaire).
 * Identifiant composite : (site_module, template_name). Les opérations délèguent à
 * MelisCmsMiniTemplateService qui gère les chemins (root public vs module) et la table
 * de flagging. Couche API partagée ; UI livrée par la BRIQUE MelisCms (brick.js).
 *
 * Routes :
 *   GET    /melis/react-api/cms-mini-templates[/]         → liste (filtré par site, search)
 *   GET    /melis/react-api/cms-mini-templates/stats[/]   → KPI (total / nb sites)
 *   GET    /melis/react-api/cms-mini-templates/sites[/]   → options du sélecteur de site
 *   GET    /melis/react-api/cms-mini-templates/item[/]    → détail template (?site=&name=)
 *   POST   /melis/react-api/cms-mini-templates/save[/]    → créer / mettre à jour (multipart)
 *   POST   /melis/react-api/cms-mini-templates/delete[/]  → supprimer (JSON body)
 */
class MelisReactApiCmsMiniTemplateController extends MelisAbstractActionController
{
    use CapabilityGuardTrait;

    private const MELIS_KEY = 'meliscms_mini_template_manager_tool';

    // ─── List ─────────────────────────────────────────────────────────────────

    public function listAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $site   = trim((string) ($this->params()->fromQuery('site', '') ?? ''));
            $search = strtolower(trim((string) ($this->params()->fromQuery('search', '') ?? '')));
            $limit  = min(9999, max(1, (int) $this->params()->fromQuery('limit', 50)));
            $sort   = (string) $this->params()->fromQuery('sort', 'path');
            $dir    = strtolower((string) $this->params()->fromQuery('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
            $after  = (string) $this->params()->fromQuery('after', '');

            if ($site === '') {
                return $this->jsonResponse([
                    'success' => true,
                    'data'    => ['items' => [], 'total' => 0, 'nextCursor' => null],
                ]);
            }

            // Inventaire = fichiers .phtml sur disque (pas de table DB), donc on ne peut pas passer par
            // MelisReactKeysetListTrait (SQL). On reproduit le scan du service SANS l'appeler (spec),
            // puis on trie + pagine en keyset côté PHP sur le tableau en mémoire (contrat identique :
            // items/total/nextCursor → hook useKeysetList inchangé).
            $names = $this->scanMiniTemplateNames($site);

            // Search filter (sur le nom / chemin affiché = le nom du template).
            if ($search !== '') {
                $names = array_values(array_filter($names, fn ($n) => strpos(strtolower($n), $search) !== false));
            }

            $total = count($names);

            // Tri server-side whitelisté. Seul « path » (= nom du template) est triable côté UI.
            $sortAsc = $dir === 'asc';
            usort($names, function ($a, $b) use ($sortAsc) {
                $cmp = strcasecmp($a, $b);
                if ($cmp === 0) { $cmp = strcmp($a, $b); } // tiebreaker stable
                return $sortAsc ? $cmp : -$cmp;
            });

            // Keyset : le curseur porte le dernier nom émis ; on reprend STRICTEMENT après.
            $startIdx = 0;
            if ($after !== '') {
                $cur = json_decode((string) base64_decode($after, true), true);
                $lastName = is_array($cur) ? (string) ($cur['v'] ?? '') : '';
                if ($lastName !== '') {
                    foreach ($names as $i => $n) {
                        $c = strcasecmp($n, $lastName);
                        if ($c === 0) { $c = strcmp($n, $lastName); }
                        $strictlyAfter = $sortAsc ? ($c > 0) : ($c < 0);
                        if ($strictlyAfter) { $startIdx = $i; break; }
                        $startIdx = $i + 1;
                    }
                }
            }

            $paged = array_slice($names, $startIdx, $limit);

            $nextCursor = null;
            if (count($paged) === $limit && ($startIdx + $limit) < $total) {
                $lastEmitted = $paged[count($paged) - 1];
                $nextCursor  = base64_encode((string) json_encode(['v' => $lastEmitted]));
            }

            /** @var \MelisCms\Service\MelisCmsMiniTemplateService $svc */
            $svc   = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
            $items = [];
            foreach ($paged as $name) {
                $items[] = $this->formatTemplate($svc, $site, $name);
            }

            return $this->jsonResponse([
                'success' => true,
                'data'    => ['items' => $items, 'total' => $total, 'nextCursor' => $nextCursor],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Reproduit MelisCmsMiniTemplateService::getMiniTemplates() SANS appeler le service (spec) :
     * scanne le dossier miniTemplatesTinyMce du MODULE + celui du ROOT PUBLIC, ne garde que les
     * .phtml, et EXCLUT du module ceux déjà « flagged » (déplacés/à jour dans le root public).
     * La table de flagging est lue en SQL brut (melis_cms_mini_tpl_flagged_template).
     *
     * @return string[] noms de templates (dédupliqués)
     */
    private function scanMiniTemplateNames(string $site): array
    {
        $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');

        // Templates du module déjà flaggés → à ignorer côté module (leur version à jour est en root public).
        $flagged = [];
        foreach (iterator_to_array($db->query(
            'SELECT mtpft_template_name FROM melis_cms_mini_tpl_flagged_template WHERE mtpft_template_module = ?',
            [$site]
        )) as $r) {
            $flagged[(string) $r['mtpft_template_name']] = true;
        }

        $modulePath     = $this->moduleMtplPath($site);
        $rootPublicPath = $this->rootPublicMtplPath($site);

        $names = [];
        // Ordre : module puis root public (comme le service). $isModulePath contrôle l'exclusion des flaggés.
        foreach ([[$modulePath, true], [$rootPublicPath, false]] as [$path, $isModulePath]) {
            if ($path === null || !is_dir($path)) { continue; }
            foreach (array_diff(scandir($path), ['.', '..']) as $file) {
                $ext = pathinfo((string) $file, PATHINFO_EXTENSION);
                if ($ext !== 'phtml') { continue; }
                $name = pathinfo((string) $file, PATHINFO_FILENAME);
                if ($name === '') { continue; }
                if ($isModulePath && isset($flagged[$name])) { continue; }
                $names[$name] = true; // dédup (module + root public peuvent se recouper)
            }
        }

        return array_keys($names);
    }

    /**
     * Chemin miniTemplatesTinyMce DU MODULE (site) sans passer par MelisCmsSiteService (spec).
     * On résout le dossier du module via ModulesService (composer) avec repli module/MelisSites.
     */
    private function moduleMtplPath(string $site): ?string
    {
        $modRoot = null;
        try {
            $modulesSrv = $this->getServiceManager()->get('ModulesService');
            $composer   = (string) $modulesSrv->getComposerModulePath($site);
            if ($composer !== '') { $modRoot = $composer; }
        } catch (\Throwable) {}
        if ($modRoot === null) {
            $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
            $modRoot = $docRoot . '/../module/MelisSites/' . $site;
        }
        $mtpl = rtrim((string) $modRoot, '/\\') . '/public/miniTemplatesTinyMce';
        $real = realpath($mtpl);
        return $real !== false ? $real : (is_dir($mtpl) ? $mtpl : null);
    }

    // ─── Stats ────────────────────────────────────────────────────────────────

    public function statsAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            $siteRows = iterator_to_array($db->query(
                'SELECT site_name FROM melis_cms_site ORDER BY site_name ASC', []
            ));

            /** @var \MelisCms\Service\MelisCmsMiniTemplateService $svc */
            $svc       = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
            $total     = 0;
            $siteCount = 0;

            foreach ($siteRows as $row) {
                $site  = (string) $row['site_name'];
                $names = $svc->getMiniTemplates($site);
                $cnt   = count($names);
                $total += $cnt;
                if ($cnt > 0) { $siteCount++; }
            }

            return $this->jsonResponse([
                'success' => true,
                'data'    => ['total' => $total, 'sites' => $siteCount],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Sites dropdown ───────────────────────────────────────────────────────

    public function sitesAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $db   = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            $rows = iterator_to_array($db->query(
                'SELECT site_id, site_name, site_label FROM melis_cms_site ORDER BY site_label ASC, site_name ASC', []
            ));
            $sites = array_map(fn ($r) => [
                'id'     => (int) $r['site_id'],
                'name'   => trim((string) $r['site_label']) !== '' ? (string) $r['site_label'] : (string) $r['site_name'],
                'module' => (string) $r['site_name'],
            ], $rows);
            return $this->jsonResponse(['success' => true, 'data' => ['sites' => $sites]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Item (read HTML content + thumbnail) ─────────────────────────────────

    public function itemAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('edit')) { return $denyCap; }

        try {
            $site = trim((string) ($this->params()->fromQuery('site', '') ?? ''));
            $name = trim((string) ($this->params()->fromQuery('name', '') ?? ''));

            if ($site === '' || $name === '') {
                return $this->jsonResponse(['success' => false, 'error' => 'Paramètres site et name requis.'], 400);
            }
            // Sécurité : $name compose un chemin de fichier .phtml → on n'accepte qu'un segment de
            // chemin simple (pas de « / », « \ » ni « .. »), donc pas de traversée de répertoire.
            // ⚠ PAS la regex de saveAction (`^[a-zA-Z_][a-zA-Z0-9_]*$`) : elle interdit le tiret, or
            // les mini-templates livrés en ont tous un (« 2-cols-paragraph »…) — les lire renvoyait
            // 400, d'où un aperçu vide dans le dialogue IA alors que le legacy, qui ne valide rien
            // en lecture, les affichait. Ici on lit un fichier, la garde anti-traversée suffit.
            if (!preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
                return $this->jsonResponse(['success' => false, 'error' => 'Nom de template invalide.'], 400);
            }

            /** @var \MelisCms\Service\MelisCmsMiniTemplateService $svc */
            $svc  = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
            $path = $this->findTemplatePath($site, $name);

            if ($path === null || !file_exists($path . '/' . $name . '.phtml')) {
                return $this->jsonResponse(['success' => false, 'error' => 'Template introuvable.'], 404);
            }

            $html = (string) @file_get_contents($path . '/' . $name . '.phtml');
            [$thumbUrl] = $this->buildThumbnailInfo($svc, $site, $path, $name);

            return $this->jsonResponse([
                'success' => true,
                'data'    => [
                    'site'         => $site,
                    'name'         => $name,
                    'html'         => $html,
                    'thumbnailUrl' => $thumbUrl,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Save (create or update, multipart) ───────────────────────────────────

    public function saveAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }

        try {
            // multipart/form-data → use $_POST / $_FILES
            $site    = trim((string) ($_POST['site']    ?? ''));
            $name    = trim((string) ($_POST['name']    ?? ''));
            $oldSite = trim((string) ($_POST['oldSite'] ?? ''));
            $oldName = trim((string) ($_POST['oldName'] ?? ''));
            $html    = (string) ($_POST['html'] ?? '');

            $isEdit = $oldName !== '';
            if ($denyCap = $this->denyUnlessCan($isEdit ? 'edit' : 'create')) { return $denyCap; }

            if ($site === '') {
                return $this->jsonResponse(['success' => false, 'error' => 'Le site est obligatoire.'], 400);
            }
            if ($name === '') {
                return $this->jsonResponse(['success' => false, 'error' => 'Le nom du template est obligatoire.'], 400);
            }
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
                return $this->jsonResponse(['success' => false, 'error' => 'Nom invalide (lettres, chiffres, underscore ; doit commencer par une lettre ou _).'], 400);
            }

            /** @var \MelisCms\Service\MelisCmsMiniTemplateService $svc */
            $svc = $this->getServiceManager()->get('MelisCmsMiniTemplateService');

            $thumbnail = $_FILES['thumbnail'] ?? ['name' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE];

            $currentSite = $oldSite !== '' ? $oldSite : $site;
            $catId       = isset($_POST['category']) && $_POST['category'] !== '' ? (int) $_POST['category'] : null;
            $siteId      = $catId ? $this->siteIdFromModule($site) : null;

            // Les modules greffent des listeners sur ces événements (ex. MelisAICommunityExtensions,
            // qui déplace les CSS/JS/images générés par l'IA de temp/ vers leur emplacement définitif
            // sur create_end, et nettoie ces assets sur delete_end). L'API React DOIT donc déclencher
            // les mêmes événements que MiniTemplateManagerController, avec la même forme de params.
            $startData = [
                'miniTemplateSiteModule' => $site,
                'miniTemplateName'       => $name,
                'miniTemplateHtml'       => $html,
                'miniTemplateThumbnail'  => $thumbnail,
                'categoryId'             => $catId,
                'siteId'                 => $siteId,
            ];
            if ($isEdit) {
                $startData['current_module']   = $currentSite;
                $startData['current_template'] = $oldName;
            }

            $this->getEventManager()->trigger(
                $isEdit ? 'meliscms_mini_template_manager_update_start' : 'meliscms_mini_template_manager_create_start',
                $this,
                $startData
            );

            if ($isEdit) {
                $result = $svc->updateMiniTemplate(
                    ['miniTemplateSiteModule' => $currentSite, 'miniTemplateName' => $oldName],
                    [
                        'miniTemplateSiteModule' => $site,
                        'miniTemplateName'       => $name,
                        'miniTemplateHtml'       => $html,
                        'miniTemplateThumbnail'  => $thumbnail,
                    ],
                    !empty($thumbnail['name']) && ($thumbnail['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
                );
            } else {
                $imgTmpPath = (!empty($thumbnail['tmp_name']) && ($thumbnail['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)
                    ? $thumbnail['tmp_name'] : null;
                $imgExt = $imgTmpPath ? pathinfo((string) ($thumbnail['name'] ?? ''), PATHINFO_EXTENSION) : null;

                // Bouton « + » du Menu manager : lie le template à une catégorie dès la création.
                // Le service attend le site_id NUMÉRIQUE (mtplct_site_id) — résolu depuis le module.
                $result = $svc->createMiniTemplate($site, $name, $html, $imgTmpPath, $imgExt, $catId, $siteId);
            }

            $success = !empty($result['success']);

            // *_end : mêmes clés que le legacy (success / textTitle / textMessage / errors / data
            // + typeCode), attendues par les listeners (MelisCmsFlashMessengerListener journalise,
            // MelisAICommunityExtensionsCreateMiniTemplateListener lit data.module/data.template_name).
            $endData = [
                'success'     => $success ? 1 : 0,
                'textTitle'   => 'Mini-template',
                'textMessage' => $isEdit
                    ? ($success ? 'tr_meliscms_mini_template_updated_successfully' : 'tr_meliscms_mini_template_update_fail')
                    : ($success ? 'tr_meliscms_mini_template_created_successfully' : 'tr_meliscms_mini_template_create_fail'),
                'errors'      => $result['errors'] ?? [],
                'data'        => $result['data'] ?? [],
                'typeCode'    => $isEdit ? 'CMS_MTPL_UPDATE' : 'CMS_MTPL_ADD',
            ];

            $this->getEventManager()->trigger(
                $isEdit ? 'meliscms_mini_template_manager_update_end' : 'meliscms_mini_template_manager_create_end',
                $this,
                $endData
            );

            if (!$success) {
                $errors = $result['errors'] ?? [];
                $msg    = is_array($errors) && !empty($errors) ? implode(' ', array_column($errors, 'error')) : 'Erreur lors de la sauvegarde.';
                return $this->jsonResponse(['success' => false, 'error' => $msg], 400);
            }

            $savedSite = $result['data']['module'] ?? $site;
            $savedName = $result['data']['template_name'] ?? $name;

            return $this->jsonResponse([
                'success' => true,
                'data'    => $this->formatTemplate($svc, $savedSite, $savedName),
            ], $isEdit ? 200 : 201);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function deleteAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('delete')) { return $denyCap; }

        try {
            $body = json_decode($this->getRequest()->getContent(), true) ?? [];
            $site = trim((string) ($body['site'] ?? ''));
            $name = trim((string) ($body['name'] ?? ''));

            if ($site === '' || $name === '') {
                return $this->jsonResponse(['success' => false, 'error' => 'Paramètres site et name requis.'], 400);
            }

            /** @var \MelisCms\Service\MelisCmsMiniTemplateService $svc */
            $svc  = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
            $path = $this->findTemplatePath($site, $name);

            if ($path === null) {
                return $this->jsonResponse(['success' => false, 'error' => 'Template introuvable.'], 404);
            }

            $phtmlPath = $path . '/' . $name . '.phtml';
            [, $thumbAbsPath] = $this->buildThumbnailInfo($svc, $site, $path, $name);

            // Legacy: $data = POST ['module' => …, 'template' => …]. Ici le corps est du JSON, donc on
            // passe aussi module/template dans le payload de l'événement (les listeners les lisent
            // d'abord depuis les params, avec repli sur le POST pour le tool legacy).
            $eventData = ['module' => $site, 'template' => $name];

            $this->getEventManager()->trigger('meliscms_mini_template_manager_delete_start', $this, $eventData);

            $result = $svc->deleteMiniTemplate($phtmlPath, (string) $thumbAbsPath, $name);

            $success = !empty($result['success']);

            $this->getEventManager()->trigger('meliscms_mini_template_manager_delete_end', $this, [
                'success'     => $success ? 1 : 0,
                'textTitle'   => 'Mini-template',
                'textMessage' => $success ? 'tr_meliscms_mini_template_deleted_successfully' : 'tr_meliscms_mini_template_delete_fail',
                'errors'      => $result['errors'] ?? [],
                'data'        => $eventData,
                'typeCode'    => 'CMS_MTPL_DELETE',
            ]);

            if (!$success) {
                $errors = $result['errors'] ?? [];
                $msg    = is_array($errors) && !empty($errors) ? (is_array($errors[0]) ? ($errors[0]['error'] ?? 'Erreur.') : (string) $errors[0]) : 'Erreur lors de la suppression.';
                return $this->jsonResponse(['success' => false, 'error' => $msg], 400);
            }

            return $this->jsonResponse(['success' => true, 'data' => null]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Résout le site_id numérique (melis_cms_site.site_id) depuis son module (site_name).
     * Utilisé pour lier un mini-template à une catégorie à la création (mtplct_site_id).
     */
    private function siteIdFromModule(string $module): ?int
    {
        $db   = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
        $rows = iterator_to_array($db->query('SELECT site_id FROM melis_cms_site WHERE site_name = ?', [$module]));
        return !empty($rows) ? (int) $rows[0]['site_id'] : null;
    }

    /**
     * Find the filesystem directory containing {name}.phtml for a given site.
     * Checks the root-public path first, then the module path.
     * Returns null if not found in either.
     */
    private function findTemplatePath(string $site, string $name): ?string
    {
        $rootPath = $this->rootPublicMtplPath($site);
        if ($rootPath !== null && file_exists($rootPath . '/' . $name . '.phtml')) {
            return $rootPath;
        }
        // Module path (legacy or module-served templates)
        try {
            /** @var \MelisCms\Service\MelisCmsSiteService $siteSvc */
            $siteSvc  = $this->getServiceManager()->get('MelisCmsSiteService');
            $modPath  = rtrim((string) $siteSvc->getModulePath($site), '/\\') . '/public/miniTemplatesTinyMce';
            if (file_exists($modPath . '/' . $name . '.phtml')) {
                return $modPath;
            }
        } catch (\Throwable) {}
        return null;
    }

    /**
     * Build [thumbnailUrl, thumbnailAbsPath] for a template.
     * thumbnailUrl = null if no thumbnail found.
     * thumbnailAbsPath = '' if no thumbnail found.
     */
    private function buildThumbnailInfo(
        \MelisCms\Service\MelisCmsMiniTemplateService $svc,
        string $site,
        string $path,
        string $name
    ): array {
        $thumb = $svc->getMiniTemplateThumbnail($path, $name);
        if (!$thumb || empty($thumb['file'])) {
            return [null, ''];
        }
        $file       = (string) $thumb['file'];
        $absPath    = (string) ($thumb['path'] ?? ($path . '/' . $file));
        $rootPath   = $this->rootPublicMtplPath($site);
        // If in the root-public directory, serve via /miniTemplatesTinyMce/{site}/{file}
        $url = ($rootPath !== null && strpos(str_replace('\\', '/', $path), str_replace('\\', '/', $rootPath)) === 0)
            ? '/miniTemplatesTinyMce/' . $site . '/' . $file
            : '/' . $site . '/miniTemplatesTinyMce/' . $file;
        if (file_exists($absPath)) {
            $url .= '?t=' . filemtime($absPath);
        }
        return [$url, $absPath];
    }

    /** Returns the root-public miniTemplatesTinyMce path for a site (no directory creation). */
    private function rootPublicMtplPath(string $site): ?string
    {
        $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        // Melis public dir = DOCUMENT_ROOT/../public (DOCUMENT_ROOT IS the public dir in normal setup,
        // but the service uses DOCUMENT_ROOT/../public — mirror that logic).
        $candidates = [
            $docRoot . '/../public/miniTemplatesTinyMce/' . $site,
            $docRoot . '/miniTemplatesTinyMce/' . $site,
        ];
        foreach ($candidates as $c) {
            $real = realpath($c);
            if ($real !== false && is_dir($real)) {
                return $real;
            }
            // Directory might not exist yet but path itself is valid
            $parent = dirname($c);
            if (is_dir($parent)) {
                return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $c);
            }
        }
        return null;
    }

    private function formatTemplate(
        \MelisCms\Service\MelisCmsMiniTemplateService $svc,
        string $site,
        string $name
    ): array {
        $path = $this->findTemplatePath($site, $name);
        [$thumbUrl] = $path !== null
            ? $this->buildThumbnailInfo($svc, $site, $path, $name)
            : [null, ''];
        $relPath = $svc->getSrcHtml($site, $name);
        return [
            'site'         => $site,
            'name'         => $name,
            'thumbnailUrl' => $thumbUrl,
            'path'         => $relPath,
        ];
    }

    // ─── Auth / Access helpers (verbatim gabarit) ─────────────────────────────

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
