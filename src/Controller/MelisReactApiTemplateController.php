<?php

namespace MelisCms\Controller;

use MelisReactApi\Controller\CapabilityGuardTrait;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * API REST (lecture + suppression) pour l'outil Templates de MelisCms (table melis_cms_template).
 *
 * Couche API (shared) du back-office React ; l'UI est livrée par une BRIQUE de MelisCms.
 * La LISTE est native React ; la CRÉATION/ÉDITION reste l'outil legacy (formulaire
 * lourdement couplé au système de fichiers — scan des contrôleurs/actions/layouts — et
 * à un compteur de plateforme pour `tpl_id` : logique métier non réimplémentée ici).
 * Routes :
 *   GET    /melis/react-api/templates              → liste paginée + recherche + filtres
 *   GET    /melis/react-api/templates/stats        → statistiques (cartes KPI)
 *   GET    /melis/react-api/templates/sites        → options du filtre de site
 *   GET    /melis/react-api/templates/:id          → détail
 *   DELETE /melis/react-api/templates/delete/:id   → supprimer
 */
class MelisReactApiTemplateController extends MelisAbstractActionController
{
    use CapabilityGuardTrait;

    /** melisKey de l'outil — utilisé par le garde de droits (cf. denyUnlessAccess). */
    private const MELIS_KEY = 'meliscms_tool_templates';

    /** Libellé d'affichage du type de template. */
    private const TYPE_LABELS = ['ZF2' => 'Laminas', 'PHP' => 'PHP', 'TWG' => 'Twig'];

    // ─── GET /templates ──────────────────────────────────────────────────────────

    public function listAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $page   = max(1, (int) $this->params()->fromQuery('page', 1));
            $limit  = min(9999, max(1, (int) $this->params()->fromQuery('limit', 25)));
            $search = trim((string) ($this->params()->fromQuery('search', '') ?? ''));
            $siteId = (int) $this->params()->fromQuery('site', 0) ?: null;
            $type   = trim((string) ($this->params()->fromQuery('type', '') ?? ''));
            $offset = ($page - 1) * $limit;

            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');

            $where = [];
            $params = [];
            if ($search !== '') {
                $like    = '%' . $search . '%';
                $where[] = '(t.tpl_name LIKE ? OR t.tpl_zf2_controller LIKE ? OR t.tpl_zf2_action LIKE ? OR s.site_name LIKE ? OR s.site_label LIKE ?)';
                $params  = array_merge($params, [$like, $like, $like, $like, $like]);
            }
            if ($siteId) { $where[] = 't.tpl_site_id = ?'; $params[] = $siteId; }
            if ($type !== '' && in_array($type, ['PHP', 'ZF2', 'TWG'], true)) { $where[] = 't.tpl_type = ?'; $params[] = $type; }
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $countRow = iterator_to_array($db->query(
                "SELECT COUNT(*) AS total FROM melis_cms_template t
                 LEFT JOIN melis_cms_site s ON s.site_id = t.tpl_site_id $whereClause",
                $params
            ));
            $total = (int) ($countRow[0]['total'] ?? 0);

            $rows = $db->query(
                "SELECT t.tpl_id, t.tpl_site_id, t.tpl_name, t.tpl_type, t.tpl_zf2_website_folder,
                        t.tpl_zf2_layout, t.tpl_zf2_controller, t.tpl_zf2_action, t.tpl_php_path, t.tpl_creation_date,
                        s.site_name, s.site_label
                 FROM melis_cms_template t
                 LEFT JOIN melis_cms_site s ON s.site_id = t.tpl_site_id
                 $whereClause
                 ORDER BY t.tpl_id ASC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$limit, $offset])
            );

            $items = [];
            foreach ($rows as $row) {
                $items[] = $this->formatTemplate((array) $row);
            }

            return $this->jsonResponse([
                'success' => true,
                'data'    => ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── GET /templates/stats ──────────────────────────────────────────────────────

    public function statsAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $db  = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            $row = (array) (iterator_to_array($db->query(
                "SELECT COUNT(*) AS total, COUNT(DISTINCT tpl_site_id) AS sites, COUNT(DISTINCT tpl_type) AS types
                 FROM melis_cms_template",
                []
            ))[0] ?? []);

            return $this->jsonResponse([
                'success' => true,
                'data'    => [
                    'total' => (int) ($row['total'] ?? 0),
                    'sites' => (int) ($row['sites'] ?? 0),
                    'types' => (int) ($row['types'] ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── GET /templates/sites ──────────────────────────────────────────────────────

    public function sitesAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $db   = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            $rows = iterator_to_array($db->query(
                'SELECT site_id, site_name, site_label FROM melis_cms_site ORDER BY site_label ASC, site_name ASC',
                []
            ));
            $sites = array_map(fn ($r) => [
                'id'   => (int) $r['site_id'],
                'name' => trim((string) $r['site_label']) !== '' ? (string) $r['site_label'] : (string) $r['site_name'],
            ], $rows);

            return $this->jsonResponse(['success' => true, 'data' => ['sites' => $sites]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── GET /templates/:id ────────────────────────────────────────────────────────

    public function getAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('edit')) { return $denyCap; }

        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id <= 0) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid ID'], 400);
        }

        try {
            $db   = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            $rows = iterator_to_array($db->query(
                "SELECT t.tpl_id, t.tpl_site_id, t.tpl_name, t.tpl_type, t.tpl_zf2_website_folder,
                        t.tpl_zf2_layout, t.tpl_zf2_controller, t.tpl_zf2_action, t.tpl_php_path, t.tpl_creation_date,
                        s.site_name, s.site_label
                 FROM melis_cms_template t
                 LEFT JOIN melis_cms_site s ON s.site_id = t.tpl_site_id
                 WHERE t.tpl_id = ?",
                [$id]
            ));
            if (!$rows) {
                return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
            return $this->jsonResponse(['success' => true, 'data' => $this->formatTemplate((array) $rows[0])]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── POST /templates/save ─────────────────────────────────────────────────────

    public function saveAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }

        try {
            $body  = json_decode($this->getRequest()->getContent(), true) ?? [];
            $id    = (int) ($body['id'] ?? 0);
            $isNew = $id <= 0;
            if ($denyCap = $this->denyUnlessCan($isNew ? 'create' : 'edit')) { return $denyCap; }

            $name = trim((string) ($body['name'] ?? ''));
            if ($name === '') {
                return $this->jsonResponse(['success' => false, 'error' => 'Name is required'], 400);
            }

            $type          = in_array($body['type'] ?? '', ['ZF2', 'PHP', 'TWG'], true) ? $body['type'] : 'ZF2';
            $siteId        = ((int) ($body['siteId'] ?? 0)) ?: null;
            $websiteFolder = trim((string) ($body['websiteFolder'] ?? ''));
            $layout        = trim((string) ($body['layout'] ?? ''));
            $controller    = trim((string) ($body['controller'] ?? ''));
            $action        = trim((string) ($body['action'] ?? ''));
            $phpPath       = trim((string) ($body['phpPath'] ?? ''));

            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');

            // Le dossier du site (tpl_zf2_website_folder) suit TOUJOURS le site — pas un champ éditable
            // (comme le tool legacy, où il est dérivé de site_name). Vaut pour création ET édition.
            if ($siteId) {
                $siteRow = $this->getServiceManager()->get('MelisEngineTableSite')->getEntryById((int) $siteId)->current();
                if (!empty($siteRow->site_name)) { $websiteFolder = $siteRow->site_name; }
            }

            // ─── CRÉATION ─────────────────────────────────────────────────────────
            if ($isNew) {
                // Validation métier (comme le tool legacy) : site obligatoire (tpl_site_id NOT NULL) ;
                // le type Laminas (ZF2) requiert layout + contrôleur + action.
                if (!$siteId) {
                    return $this->jsonResponse(['success' => false, 'error' => 'Site is required'], 400);
                }
                if ($layout === '' || $controller === '' || $action === '') {
                    return $this->jsonResponse(['success' => false, 'error' => 'Layout, controller and action are required'], 400);
                }

                // tpl_id alloué depuis le compteur de la plateforme courante (comme ToolTemplateController).
                $pidsTable = $this->getServiceManager()->get('MelisEngineTablePlatformIds');
                $pids = $pidsTable->getPlatformIdsByPlatformName(getenv('MELIS_PLATFORM'))->current();
                if (empty($pids)) {
                    return $this->jsonResponse(['success' => false, 'error' => 'No platform id counter found for this platform'], 500);
                }
                $newId = (int) $pids->pids_tpl_id_current;

                $userId = null;
                try {
                    $auth = $this->getServiceManager()->get('MelisCoreAuth')->getIdentity();
                    $userId = is_object($auth) ? ($auth->usr_id ?? null) : (is_array($auth) ? ($auth['usr_id'] ?? null) : null);
                } catch (\Throwable) {}

                $tplTable = $this->getServiceManager()->get('MelisEngineTableTemplate');
                $tplTable->save([
                    'tpl_id'                 => $newId,
                    'tpl_name'               => $name,
                    'tpl_type'               => $type,
                    'tpl_site_id'            => (int) $siteId,
                    'tpl_zf2_website_folder' => $websiteFolder,
                    'tpl_zf2_layout'         => $layout,
                    'tpl_zf2_controller'     => $controller,
                    'tpl_zf2_action'         => $action,
                    'tpl_php_path'           => $phpPath,
                    'tpl_creation_date'      => date('Y-m-d H:i:s'),
                    'tpl_last_user_id'       => $userId,
                ]);
                // Incrémenter le compteur de la plateforme (comme le legacy).
                $pidsTable->save(['pids_tpl_id_current' => $newId + 1], $pids->pids_id);

                return $this->jsonResponse(['success' => true, 'data' => ['id' => $newId]]);
            }

            // ─── ÉDITION ──────────────────────────────────────────────────────────
            if (!iterator_to_array($db->query('SELECT tpl_id FROM melis_cms_template WHERE tpl_id = ?', [$id]))) {
                return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }

            $db->query(
                'UPDATE melis_cms_template
                 SET tpl_name = ?, tpl_type = ?, tpl_site_id = ?, tpl_zf2_website_folder = ?,
                     tpl_zf2_layout = ?, tpl_zf2_controller = ?, tpl_zf2_action = ?, tpl_php_path = ?
                 WHERE tpl_id = ?',
                [$name, $type, $siteId, $websiteFolder, $layout, $controller, $action, $phpPath, $id]
            );

            return $this->jsonResponse(['success' => true, 'data' => ['id' => $id]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── DELETE /templates/delete/:id ──────────────────────────────────────────────

    public function deleteAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('delete')) { return $denyCap; }

        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id <= 0) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid ID'], 400);
        }

        try {
            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            if (!iterator_to_array($db->query('SELECT tpl_id FROM melis_cms_template WHERE tpl_id = ?', [$id]))) {
                return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
            $db->query('DELETE FROM melis_cms_template WHERE tpl_id = ?', [$id]);
            return $this->jsonResponse(['success' => true, 'data' => null]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────────

    private function formatTemplate(array $r): array
    {
        $siteName = trim((string) ($r['site_label'] ?? ''));
        if ($siteName === '') { $siteName = (string) ($r['site_name'] ?? ''); }
        $type       = (string) $r['tpl_type'];
        $controller = (string) ($r['tpl_zf2_controller'] ?? '');
        $action     = (string) ($r['tpl_zf2_action'] ?? '');
        return [
            'id'            => (int)    $r['tpl_id'],
            'siteId'        => (int)    $r['tpl_site_id'],
            'siteName'      => $siteName !== '' ? $siteName : ('#' . (int) $r['tpl_site_id']),
            'name'          => (string) $r['tpl_name'],
            'type'          => $type,
            'typeLabel'     => self::TYPE_LABELS[$type] ?? $type,
            'websiteFolder' => (string) ($r['tpl_zf2_website_folder'] ?? ''),
            'layout'        => (string) ($r['tpl_zf2_layout'] ?? ''),
            'controller'    => $controller,
            'action'        => $action,
            // « Controller/Action » prêt à afficher (comme le legacy).
            'controllerAction' => $action !== '' ? trim($controller . '/' . $action, '/') : $controller,
            'phpPath'       => (string) ($r['tpl_php_path'] ?? ''),
            'creationDate'  => (string) ($r['tpl_creation_date'] ?? ''),
        ];
    }

    private function isAuthenticated(): bool
    {
        return $this->getServiceManager()->get('MelisCoreAuth')->hasIdentity();
    }

    /**
     * Garde de droits : chaque endpoint exige l'ACCÈS à l'outil (`meliscms_tool_templates`),
     * pas seulement une session — ferme la back-door API/URL (cf. gabarit Users). 401/403/null.
     */
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
