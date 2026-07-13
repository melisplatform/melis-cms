<?php

namespace MelisCms\Controller;

use MelisReactApi\Controller\CapabilityGuardTrait;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * API REST pour l'outil Redirections 301 de MelisCms (table melis_cms_site_301).
 *
 * Couche API (shared) du back-office React ; l'UI est livrée par une BRIQUE du module
 * MelisCms (gating modulaire). Calqué sur les contrôleurs natifs (gabarit full-React).
 * Réutilise les tables Engine (melis_cms_site_301 + melis_cms_site).
 * Routes :
 *   GET    /melis/react-api/site-redirects              → liste paginée + recherche + filtre site
 *   GET    /melis/react-api/site-redirects/stats        → statistiques (cartes KPI)
 *   GET    /melis/react-api/site-redirects/sites        → options du filtre/sélecteur de site
 *   GET    /melis/react-api/site-redirects/:id          → détail
 *   POST   /melis/react-api/site-redirects/save         → créer / mettre à jour
 *   DELETE /melis/react-api/site-redirects/delete/:id   → supprimer
 *
 * Contraintes métier (reprises du legacy SiteRedirectController) :
 *   - s301_site_id requis (site existant).
 *   - s301_old_url / s301_new_url requis, ≤ 255.
 *   - s301_old_url UNIQUE pour un site donné.
 */
class MelisReactApiSiteRedirectController extends MelisAbstractActionController
{
    use CapabilityGuardTrait;

    /** melisKey de l'outil — utilisé par le garde de droits (cf. denyUnlessAccess). */
    private const MELIS_KEY = 'meliscms_tool_site_301';

    // ─── GET /site-redirects ─────────────────────────────────────────────────────

    public function listAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $page   = max(1, (int) $this->params()->fromQuery('page', 1));
            $limit  = min(9999, max(1, (int) $this->params()->fromQuery('limit', 25)));
            $search = trim((string) ($this->params()->fromQuery('search', '') ?? ''));
            $siteId = (int) $this->params()->fromQuery('site', 0) ?: null;
            $offset = ($page - 1) * $limit;

            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');

            $where = [];
            $params = [];
            if ($search !== '') {
                $like    = '%' . $search . '%';
                $where[] = '(r.s301_old_url LIKE ? OR r.s301_new_url LIKE ? OR s.site_name LIKE ? OR s.site_label LIKE ?)';
                $params  = array_merge($params, [$like, $like, $like, $like]);
            }
            if ($siteId) {
                $where[] = 'r.s301_site_id = ?';
                $params[] = $siteId;
            }
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $countRow = iterator_to_array($db->query(
                "SELECT COUNT(*) AS total
                 FROM melis_cms_site_301 r
                 LEFT JOIN melis_cms_site s ON s.site_id = r.s301_site_id
                 $whereClause",
                $params
            ));
            $total = (int) ($countRow[0]['total'] ?? 0);

            $rows = $db->query(
                "SELECT r.s301_id, r.s301_site_id, r.s301_old_url, r.s301_new_url,
                        s.site_name, s.site_label, d.sdom_scheme, d.sdom_domain
                 FROM melis_cms_site_301 r
                 LEFT JOIN melis_cms_site s ON s.site_id = r.s301_site_id
                 LEFT JOIN melis_cms_site_domain d ON d.sdom_site_id = s.site_id AND d.sdom_env = ?
                 $whereClause
                 ORDER BY r.s301_id DESC
                 LIMIT ? OFFSET ?",
                array_merge([(string) getenv('MELIS_PLATFORM')], $params, [$limit, $offset])
            );

            $items = [];
            foreach ($rows as $row) {
                $items[] = $this->formatRedirect((array) $row);
            }

            return $this->jsonResponse([
                'success' => true,
                'data'    => ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── GET /site-redirects/stats ────────────────────────────────────────────────

    public function statsAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $db  = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            $row = (array) (iterator_to_array($db->query(
                "SELECT COUNT(*) AS total, COUNT(DISTINCT s301_site_id) AS sites
                 FROM melis_cms_site_301",
                []
            ))[0] ?? []);

            return $this->jsonResponse([
                'success' => true,
                'data'    => [
                    'total' => (int) ($row['total'] ?? 0),
                    'sites' => (int) ($row['sites'] ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── GET /site-redirects/sites ────────────────────────────────────────────────

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

    // ─── GET /site-redirects/:id ──────────────────────────────────────────────────

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
                "SELECT r.s301_id, r.s301_site_id, r.s301_old_url, r.s301_new_url,
                        s.site_name, s.site_label, d.sdom_scheme, d.sdom_domain
                 FROM melis_cms_site_301 r
                 LEFT JOIN melis_cms_site s ON s.site_id = r.s301_site_id
                 LEFT JOIN melis_cms_site_domain d ON d.sdom_site_id = s.site_id AND d.sdom_env = ?
                 WHERE r.s301_id = ?",
                [(string) getenv('MELIS_PLATFORM'), $id]
            ));
            if (!$rows) {
                return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
            return $this->jsonResponse([
                'success' => true,
                'data'    => $this->formatRedirect((array) $rows[0]),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── POST /site-redirects/save ────────────────────────────────────────────────

    public function saveAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }

        try {
            $body   = json_decode($this->getRequest()->getContent(), true) ?? [];
            $id     = isset($body['id']) && $body['id'] ? (int) $body['id'] : null;
            if ($denyCap = $this->denyUnlessCan($id ? 'edit' : 'create')) { return $denyCap; }
            $siteId = (int) ($body['siteId'] ?? 0);
            $oldUrl = trim((string) ($body['oldUrl'] ?? ''));
            $newUrl = trim((string) ($body['newUrl'] ?? ''));

            // Validations (parité legacy).
            if ($siteId <= 0) {
                return $this->jsonResponse(['success' => false, 'error' => 'Le site est obligatoire.'], 400);
            }
            if ($oldUrl === '' || $newUrl === '') {
                return $this->jsonResponse(['success' => false, 'error' => 'Les URLs (ancienne et nouvelle) sont obligatoires.'], 400);
            }
            if (mb_strlen($oldUrl) > 255 || mb_strlen($newUrl) > 255) {
                return $this->jsonResponse(['success' => false, 'error' => 'Une URL dépasse 255 caractères.'], 400);
            }

            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');

            // Le site doit exister.
            if (!iterator_to_array($db->query('SELECT site_id FROM melis_cms_site WHERE site_id = ?', [$siteId]))) {
                return $this->jsonResponse(['success' => false, 'error' => 'Site introuvable.'], 400);
            }

            // Unicité de l'ancienne URL POUR CE SITE (en excluant l'enregistrement courant si édition).
            $dupSql    = $id
                ? 'SELECT s301_id FROM melis_cms_site_301 WHERE s301_old_url = ? AND s301_site_id = ? AND s301_id <> ?'
                : 'SELECT s301_id FROM melis_cms_site_301 WHERE s301_old_url = ? AND s301_site_id = ?';
            $dupParams = $id ? [$oldUrl, $siteId, $id] : [$oldUrl, $siteId];
            if (iterator_to_array($db->query($dupSql, $dupParams))) {
                return $this->jsonResponse(['success' => false, 'error' => 'Cette ancienne URL existe déjà pour ce site.'], 400);
            }

            if ($id) {
                if (!iterator_to_array($db->query('SELECT s301_id FROM melis_cms_site_301 WHERE s301_id = ?', [$id]))) {
                    return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
                }
                $db->query(
                    'UPDATE melis_cms_site_301 SET s301_site_id = ?, s301_old_url = ?, s301_new_url = ? WHERE s301_id = ?',
                    [$siteId, $oldUrl, $newUrl, $id]
                );
                return $this->jsonResponse(['success' => true, 'data' => ['id' => $id]]);
            }

            $db->query(
                'INSERT INTO melis_cms_site_301 (s301_site_id, s301_old_url, s301_new_url) VALUES (?, ?, ?)',
                [$siteId, $oldUrl, $newUrl]
            );
            $newId = (int) iterator_to_array($db->query('SELECT LAST_INSERT_ID() AS id', []))[0]['id'];

            return $this->jsonResponse(['success' => true, 'data' => ['id' => $newId]], 201);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── DELETE /site-redirects/delete/:id ────────────────────────────────────────

    public function deleteAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('delete')) { return $denyCap; }

        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id <= 0) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid ID'], 400);
        }

        try {
            $db   = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            if (!iterator_to_array($db->query('SELECT s301_id FROM melis_cms_site_301 WHERE s301_id = ?', [$id]))) {
                return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
            $db->query('DELETE FROM melis_cms_site_301 WHERE s301_id = ?', [$id]);
            return $this->jsonResponse(['success' => true, 'data' => null]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────────

    private function formatRedirect(array $r): array
    {
        $siteName = trim((string) ($r['site_label'] ?? ''));
        if ($siteName === '') { $siteName = (string) ($r['site_name'] ?? ''); }
        $domain = trim((string) ($r['sdom_domain'] ?? ''));
        return [
            'id'       => (int)    $r['s301_id'],
            'siteId'   => (int)    $r['s301_site_id'],
            'siteName' => $siteName !== '' ? $siteName : ('#' . (int) $r['s301_site_id']),
            'oldUrl'   => (string) $r['s301_old_url'],
            'newUrl'   => (string) $r['s301_new_url'],
            'baseUrl'  => $domain !== '' ? (((string) ($r['sdom_scheme'] ?? '') ?: 'http') . '://' . $domain) : null,
        ];
    }

    private function isAuthenticated(): bool
    {
        return $this->getServiceManager()->get('MelisCoreAuth')->hasIdentity();
    }

    /**
     * Garde de droits : chaque endpoint exige l'ACCÈS à l'outil (`meliscms_tool_site_301`),
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
