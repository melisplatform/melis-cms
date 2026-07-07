<?php

namespace MelisCms\Controller;

use MelisReactApi\Controller\CapabilityGuardTrait;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * API REST pour l'outil « Platforms IDs » de MelisCms (table melis_cms_platform_ids).
 *
 * Plages d'IDs (pages et templates) allouées par plateforme : start / current / end.
 * Couche API (shared) ; UI livrée par une BRIQUE MelisCms. Gabarit full-React (SQL brut),
 * calqué sur MelisReactApiSiteRedirectController.
 * Routes :
 *   GET    /melis/react-api/cms-platform-ids              → liste paginée + recherche
 *   GET    /melis/react-api/cms-platform-ids/stats        → KPI
 *   GET    /melis/react-api/cms-platform-ids/:id          → détail
 *   POST   /melis/react-api/cms-platform-ids/save         → créer / mettre à jour
 *   DELETE /melis/react-api/cms-platform-ids/delete/:id   → supprimer
 *
 * Contraintes : 6 entiers ≥ 0 ; start ≤ current ≤ end pour chaque plage (page et template).
 */
class MelisReactApiCmsPlatformIdController extends MelisAbstractActionController
{
    use CapabilityGuardTrait;

    private const MELIS_KEY = 'meliscms_tool_platform_ids';

    private const COLS = 'pids_id, pids_page_id_start, pids_page_id_current, pids_page_id_end, '
                       . 'pids_tpl_id_start, pids_tpl_id_current, pids_tpl_id_end';
    // Le nom de la plateforme n'est PAS dans melis_cms_platform_ids : il vient de melis_core_platform
    // via la relation 1:1 pids_id = plf_id (cf. MelisPlatformIdsTable::getPlatformIdsByPlatformName).
    private const JOIN = ' FROM melis_cms_platform_ids p LEFT JOIN melis_core_platform cp ON cp.plf_id = p.pids_id ';

    public function listAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $page   = max(1, (int) $this->params()->fromQuery('page', 1));
            $limit  = min(9999, max(1, (int) $this->params()->fromQuery('limit', 25)));
            $search = trim((string) ($this->params()->fromQuery('search', '') ?? ''));
            $offset = ($page - 1) * $limit;

            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');

            $where = '';
            $params = [];
            if ($search !== '') {
                $like  = '%' . $search . '%';
                $where = 'WHERE (CONCAT_WS(\'|\', p.pids_id, COALESCE(cp.plf_name, \'\'), p.pids_page_id_start, p.pids_page_id_current, p.pids_page_id_end, p.pids_tpl_id_start, p.pids_tpl_id_current, p.pids_tpl_id_end) LIKE ?)';
                $params = [$like];
            }

            $total = (int) (iterator_to_array($db->query(
                "SELECT COUNT(*) AS total" . self::JOIN . "$where", $params
            ))[0]['total'] ?? 0);

            $rows = $db->query(
                "SELECT " . self::COLS . ", cp.plf_name" . self::JOIN . "$where
                 ORDER BY p.pids_id DESC LIMIT ? OFFSET ?",
                array_merge($params, [$limit, $offset])
            );

            $items = [];
            foreach ($rows as $row) { $items[] = $this->formatPid((array) $row); }

            return $this->jsonResponse([
                'success' => true,
                'data'    => ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => $limit],
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
            $db    = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            $total = (int) (iterator_to_array($db->query('SELECT COUNT(*) AS total FROM melis_cms_platform_ids', []))[0]['total'] ?? 0);
            // Plateformes SANS plage définie (comme getAvailablePlatforms : plf sans ligne pids).
            // On ne peut créer une plage QUE pour une de ces plateformes (pids_id = plf_id).
            $avail = [];
            foreach ($db->query(
                'SELECT cp.plf_id AS id, cp.plf_name AS name FROM melis_core_platform cp
                 LEFT JOIN melis_cms_platform_ids p ON p.pids_id = cp.plf_id
                 WHERE p.pids_id IS NULL ORDER BY cp.plf_name', []
            ) as $r) {
                $r = (array) $r;
                $avail[] = ['id' => (int) $r['id'], 'name' => (string) $r['name']];
            }
            return $this->jsonResponse(['success' => true, 'data' => ['total' => $total, 'availablePlatforms' => $avail]]);
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
            $rows = iterator_to_array($db->query('SELECT ' . self::COLS . ', cp.plf_name' . self::JOIN . 'WHERE p.pids_id = ?', [$id]));
            if (!$rows) { return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404); }
            return $this->jsonResponse(['success' => true, 'data' => $this->formatPid((array) $rows[0])]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function saveAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }

        try {
            $body = json_decode($this->getRequest()->getContent(), true) ?? [];
            $id   = isset($body['id']) && $body['id'] ? (int) $body['id'] : null;
            if ($denyCap = $this->denyUnlessCan($id ? 'edit' : 'create')) { return $denyCap; }

            $pageStart   = (int) ($body['pageStart'] ?? 0);
            $pageCurrent = (int) ($body['pageCurrent'] ?? 0);
            $pageEnd     = (int) ($body['pageEnd'] ?? 0);
            $tplStart    = (int) ($body['tplStart'] ?? 0);
            $tplCurrent  = (int) ($body['tplCurrent'] ?? 0);
            $tplEnd      = (int) ($body['tplEnd'] ?? 0);

            foreach ([$pageStart, $pageCurrent, $pageEnd, $tplStart, $tplCurrent, $tplEnd] as $v) {
                if ($v < 0) { return $this->jsonResponse(['success' => false, 'error' => 'Les identifiants doivent être des entiers positifs.'], 400); }
            }
            if ($pageStart > $pageEnd || $tplStart > $tplEnd) {
                return $this->jsonResponse(['success' => false, 'error' => 'La valeur « début » ne peut pas dépasser « fin ».'], 400);
            }
            if ($pageCurrent < $pageStart || $pageCurrent > $pageEnd || $tplCurrent < $tplStart || $tplCurrent > $tplEnd) {
                return $this->jsonResponse(['success' => false, 'error' => 'La valeur « courante » doit être comprise entre « début » et « fin ».'], 400);
            }

            $db   = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            $vals = [$pageStart, $pageCurrent, $pageEnd, $tplStart, $tplCurrent, $tplEnd];

            if ($id) {
                if (!iterator_to_array($db->query('SELECT pids_id FROM melis_cms_platform_ids WHERE pids_id = ?', [$id]))) {
                    return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
                }
                $db->query(
                    'UPDATE melis_cms_platform_ids SET pids_page_id_start = ?, pids_page_id_current = ?, pids_page_id_end = ?,
                     pids_tpl_id_start = ?, pids_tpl_id_current = ?, pids_tpl_id_end = ? WHERE pids_id = ?',
                    array_merge($vals, [$id])
                );
                return $this->jsonResponse(['success' => true, 'data' => ['id' => $id]]);
            }

            // CRÉATION : une plage est rattachée à une plateforme EXISTANTE encore sans plage
            // (relation 1:1 pids_id = plf_id). On ne peut donc créer que pour une plateforme « absente ».
            $platformId = (int) ($body['platformId'] ?? 0);
            if ($platformId <= 0) {
                return $this->jsonResponse(['success' => false, 'error' => 'Platform is required'], 400);
            }
            if (!iterator_to_array($db->query('SELECT plf_id FROM melis_core_platform WHERE plf_id = ?', [$platformId]))) {
                return $this->jsonResponse(['success' => false, 'error' => 'Unknown platform'], 400);
            }
            if (iterator_to_array($db->query('SELECT pids_id FROM melis_cms_platform_ids WHERE pids_id = ?', [$platformId]))) {
                return $this->jsonResponse(['success' => false, 'error' => 'This platform already has a range'], 409);
            }
            $db->query(
                'INSERT INTO melis_cms_platform_ids (pids_id, pids_page_id_start, pids_page_id_current, pids_page_id_end,
                 pids_tpl_id_start, pids_tpl_id_current, pids_tpl_id_end) VALUES (?, ?, ?, ?, ?, ?, ?)',
                array_merge([$platformId], $vals)
            );
            return $this->jsonResponse(['success' => true, 'data' => ['id' => $platformId]], 201);
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
            if (!iterator_to_array($db->query('SELECT pids_id FROM melis_cms_platform_ids WHERE pids_id = ?', [$id]))) {
                return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
            $db->query('DELETE FROM melis_cms_platform_ids WHERE pids_id = ?', [$id]);
            return $this->jsonResponse(['success' => true, 'data' => null]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────────

    private function formatPid(array $r): array
    {
        return [
            'id'          => (int) $r['pids_id'],
            'name'        => (string) ($r['plf_name'] ?? ''),
            'pageStart'   => (int) $r['pids_page_id_start'],
            'pageCurrent' => (int) $r['pids_page_id_current'],
            'pageEnd'     => (int) $r['pids_page_id_end'],
            'tplStart'    => (int) $r['pids_tpl_id_start'],
            'tplCurrent'  => (int) $r['pids_tpl_id_current'],
            'tplEnd'      => (int) $r['pids_tpl_id_end'],
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
