<?php

namespace MelisCms\Controller;

use MelisReactApi\Controller\CapabilityGuardTrait;
use MelisCore\Controller\MelisReactKeysetListTrait;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * API REST pour l'outil « Styles » de MelisCms (table melis_cms_style).
 *
 * Styles CSS déclarés par site (nom, chemin, statut on/off). Couche API (shared) ;
 * UI livrée par une BRIQUE MelisCms. Gabarit full-React (SQL brut), calqué sur
 * MelisReactApiSiteRedirectController. Réutilise melis_cms_site pour le nom du site.
 * Routes :
 *   GET    /melis/react-api/cms-styles              → liste paginée + recherche + filtres (site, statut)
 *   GET    /melis/react-api/cms-styles/stats        → KPI (total / actifs / inactifs)
 *   GET    /melis/react-api/cms-styles/sites        → options du sélecteur de site
 *   GET    /melis/react-api/cms-styles/:id          → détail
 *   POST   /melis/react-api/cms-styles/save         → créer / mettre à jour
 *   DELETE /melis/react-api/cms-styles/delete/:id   → supprimer
 *
 * Contraintes : style_name + style_path requis ; style_site_id requis (site existant) ;
 * style_status ∈ {0,1}.
 */
class MelisReactApiCmsStyleController extends MelisAbstractActionController
{
    use CapabilityGuardTrait;
    use MelisReactKeysetListTrait;

    private const MELIS_KEY = 'meliscms_tool_styles';

    public function listAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $limit  = min(9999, max(1, (int) $this->params()->fromQuery('limit', 25)));
            $search = trim((string) ($this->params()->fromQuery('search', '') ?? ''));
            $siteId = (int) $this->params()->fromQuery('site', 0) ?: null;
            $status = $this->params()->fromQuery('status', '');
            $sort   = (string) $this->params()->fromQuery('sort', 'id');
            $dir    = (string) $this->params()->fromQuery('dir', 'desc');
            $after  = (string) $this->params()->fromQuery('after', '');

            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');

            $filterWhere  = [];
            $filterParams = [];
            if ($search !== '') {
                $like           = '%' . $search . '%';
                $filterWhere[]  = '(st.style_name LIKE ? OR st.style_path LIKE ? OR s.site_name LIKE ? OR s.site_label LIKE ?)';
                $filterParams   = array_merge($filterParams, [$like, $like, $like, $like]);
            }
            if ($siteId) { $filterWhere[] = 'st.style_site_id = ?'; $filterParams[] = $siteId; }
            if ($status === '0' || $status === '1') { $filterWhere[] = 'st.style_status = ?'; $filterParams[] = (int) $status; }

            [$rows, $total, $next] = $this->keysetList([
                'db'           => $db,
                'from'         => 'melis_cms_style st',
                'joins'        => 'LEFT JOIN melis_cms_site s ON s.site_id = st.style_site_id',
                'selectCols'   => 'st.style_id, st.style_site_id, st.style_name, st.style_status, st.style_path, s.site_name, s.site_label',
                'filterWhere'  => $filterWhere,
                'filterParams' => $filterParams,
                'sortMap'      => [
                    'id'     => 'st.style_id',
                    'status' => 'COALESCE(st.style_status, 0)',
                    'name'   => "COALESCE(st.style_name, '')",
                    'path'   => "COALESCE(st.style_path, '')",
                    'site'   => "COALESCE(NULLIF(s.site_label, ''), s.site_name, '')",
                ],
                'idCol'        => 'st.style_id',
                'idAlias'      => 'style_id',
                'sortKey'      => $sort,
                'dir'          => $dir,
                'after'        => $after,
                'limit'        => $limit,
            ]);

            $items = [];
            foreach ($rows as $row) { $items[] = $this->formatStyle((array) $row); }

            return $this->jsonResponse([
                'success' => true,
                'data'    => ['items' => $items, 'total' => $total, 'nextCursor' => $next],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function statsAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $db  = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            $row = (array) (iterator_to_array($db->query(
                'SELECT COUNT(*) AS total,
                        SUM(CASE WHEN style_status = 1 THEN 1 ELSE 0 END) AS active,
                        SUM(CASE WHEN style_status = 0 THEN 1 ELSE 0 END) AS inactive
                 FROM melis_cms_style', []
            ))[0] ?? []);
            return $this->jsonResponse([
                'success' => true,
                'data'    => [
                    'total'    => (int) ($row['total'] ?? 0),
                    'active'   => (int) ($row['active'] ?? 0),
                    'inactive' => (int) ($row['inactive'] ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

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
                'id'   => (int) $r['site_id'],
                'name' => trim((string) $r['site_label']) !== '' ? (string) $r['site_label'] : (string) $r['site_name'],
            ], $rows);
            return $this->jsonResponse(['success' => true, 'data' => ['sites' => $sites]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function getAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('edit')) { return $denyCap; }

        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id <= 0) { return $this->jsonResponse(['success' => false, 'error' => 'Invalid ID'], 400); }

        try {
            $db   = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            $rows = iterator_to_array($db->query(
                "SELECT st.style_id, st.style_site_id, st.style_name, st.style_status, st.style_path,
                        s.site_name, s.site_label
                 FROM melis_cms_style st
                 LEFT JOIN melis_cms_site s ON s.site_id = st.style_site_id
                 WHERE st.style_id = ?", [$id]
            ));
            if (!$rows) { return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404); }
            return $this->jsonResponse(['success' => true, 'data' => $this->formatStyle((array) $rows[0])]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function saveAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }

        try {
            $body   = json_decode($this->getRequest()->getContent(), true) ?? [];
            $id     = isset($body['id']) && $body['id'] ? (int) $body['id'] : null;
            if ($denyCap = $this->denyUnlessCan($id ? 'edit' : 'create')) { return $denyCap; }
            $siteId = (int) ($body['siteId'] ?? 0);
            $name   = trim((string) ($body['name'] ?? ''));
            $path   = trim((string) ($body['path'] ?? ''));
            $status = (int) (!empty($body['status']) ? 1 : 0);

            if ($siteId <= 0) {
                return $this->jsonResponse(['success' => false, 'error' => 'Le site est obligatoire.'], 400);
            }
            if ($name === '' || $path === '') {
                return $this->jsonResponse(['success' => false, 'error' => 'Le nom et le chemin sont obligatoires.'], 400);
            }

            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            if (!iterator_to_array($db->query('SELECT site_id FROM melis_cms_site WHERE site_id = ?', [$siteId]))) {
                return $this->jsonResponse(['success' => false, 'error' => 'Site introuvable.'], 400);
            }

            if ($id) {
                if (!iterator_to_array($db->query('SELECT style_id FROM melis_cms_style WHERE style_id = ?', [$id]))) {
                    return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
                }
                $db->query(
                    'UPDATE melis_cms_style SET style_site_id = ?, style_name = ?, style_status = ?, style_path = ? WHERE style_id = ?',
                    [$siteId, $name, $status, $path, $id]
                );
                return $this->jsonResponse(['success' => true, 'data' => ['id' => $id]]);
            }

            $db->query(
                'INSERT INTO melis_cms_style (style_site_id, style_name, style_status, style_path) VALUES (?, ?, ?, ?)',
                [$siteId, $name, $status, $path]
            );
            $newId = (int) iterator_to_array($db->query('SELECT LAST_INSERT_ID() AS id', []))[0]['id'];
            return $this->jsonResponse(['success' => true, 'data' => ['id' => $newId]], 201);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function deleteAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('delete')) { return $denyCap; }

        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id <= 0) { return $this->jsonResponse(['success' => false, 'error' => 'Invalid ID'], 400); }

        try {
            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            if (!iterator_to_array($db->query('SELECT style_id FROM melis_cms_style WHERE style_id = ?', [$id]))) {
                return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
            $db->query('DELETE FROM melis_cms_style WHERE style_id = ?', [$id]);
            return $this->jsonResponse(['success' => true, 'data' => null]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────────

    private function formatStyle(array $r): array
    {
        $siteName = trim((string) ($r['site_label'] ?? ''));
        if ($siteName === '') { $siteName = (string) ($r['site_name'] ?? ''); }
        return [
            'id'       => (int)    $r['style_id'],
            'siteId'   => (int)    $r['style_site_id'],
            'siteName' => $siteName !== '' ? $siteName : ('#' . (int) $r['style_site_id']),
            'name'     => (string) $r['style_name'],
            'status'   => (int)    $r['style_status'] === 1 ? 1 : 0,
            'path'     => (string) $r['style_path'],
        ];
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
