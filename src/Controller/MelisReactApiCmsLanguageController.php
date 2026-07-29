<?php

namespace MelisCms\Controller;

use MelisReactApi\Controller\CapabilityGuardTrait;
use MelisCore\Controller\MelisReactKeysetListTrait;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * API REST pour l'outil « Languages » de MelisCms (table melis_cms_lang).
 *
 * Couche API (shared) du back-office React ; l'UI est livrée par une BRIQUE du module
 * MelisCms. Calqué sur MelisReactApiSiteRedirectController (gabarit full-React, SQL brut).
 * Routes :
 *   GET    /melis/react-api/cms-languages              → liste paginée + recherche
 *   GET    /melis/react-api/cms-languages/stats        → statistiques (KPI)
 *   GET    /melis/react-api/cms-languages/:id          → détail
 *   POST   /melis/react-api/cms-languages/save         → créer / mettre à jour
 *   DELETE /melis/react-api/cms-languages/delete/:id   → supprimer
 *
 * Contraintes (parité legacy) : lang_cms_name requis ; lang_cms_locale requis,
 * format xx_XX, unique.
 */
class MelisReactApiCmsLanguageController extends MelisAbstractActionController
{
    use CapabilityGuardTrait;
    use MelisReactKeysetListTrait;

    private const MELIS_KEY = 'meliscms_tool_language';

    public function listAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $limit  = min(9999, max(1, (int) $this->params()->fromQuery('limit', 25)));
            $search = trim((string) ($this->params()->fromQuery('search', '') ?? ''));
            $sort   = (string) $this->params()->fromQuery('sort', 'id');
            $dir    = (string) $this->params()->fromQuery('dir', 'desc');
            $after  = (string) $this->params()->fromQuery('after', '');

            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');

            $filterWhere  = [];
            $filterParams = [];
            if ($search !== '') {
                $like           = '%' . $search . '%';
                $filterWhere[]  = '(lang_cms_locale LIKE ? OR lang_cms_name LIKE ?)';
                $filterParams[] = $like;
                $filterParams[] = $like;
            }

            [$rows, $total, $next] = $this->keysetList([
                'db'           => $db,
                'from'         => 'melis_cms_lang',
                'selectCols'   => 'lang_cms_id, lang_cms_locale, lang_cms_name',
                'filterWhere'  => $filterWhere,
                'filterParams' => $filterParams,
                'sortMap'      => [
                    'id'     => 'lang_cms_id',
                    'locale' => "COALESCE(lang_cms_locale, '')",
                    'name'   => "COALESCE(lang_cms_name, '')",
                ],
                'idCol'        => 'lang_cms_id',
                'idAlias'      => 'lang_cms_id',
                'sortKey'      => $sort,
                'dir'          => $dir,
                'after'        => $after,
                'limit'        => $limit,
            ]);

            $items = [];
            foreach ($rows as $row) { $items[] = $this->formatLang((array) $row); }

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
            $total = (int) (iterator_to_array($db->query('SELECT COUNT(*) AS total FROM melis_cms_lang', []))[0]['total'] ?? 0);
            return $this->jsonResponse(['success' => true, 'data' => ['total' => $total]]);
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
                'SELECT lang_cms_id, lang_cms_locale, lang_cms_name FROM melis_cms_lang WHERE lang_cms_id = ?', [$id]
            ));
            if (!$rows) { return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404); }
            return $this->jsonResponse(['success' => true, 'data' => $this->formatLang((array) $rows[0])]);
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
            $locale = trim((string) ($body['locale'] ?? ''));
            $name   = trim((string) ($body['name'] ?? ''));

            if ($name === '') {
                return $this->jsonResponse(['success' => false, 'error' => 'Le nom est obligatoire.'], 400);
            }
            if (!preg_match('/^[a-zA-Z]{2}_[a-zA-Z]{2}$/', $locale)) {
                return $this->jsonResponse(['success' => false, 'error' => 'La locale doit être au format xx_XX (ex. en_EN).'], 400);
            }

            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');

            $dupSql    = $id
                ? 'SELECT lang_cms_id FROM melis_cms_lang WHERE lang_cms_locale = ? AND lang_cms_id <> ?'
                : 'SELECT lang_cms_id FROM melis_cms_lang WHERE lang_cms_locale = ?';
            $dupParams = $id ? [$locale, $id] : [$locale];
            if (iterator_to_array($db->query($dupSql, $dupParams))) {
                return $this->jsonResponse(['success' => false, 'error' => 'Cette locale existe déjà.'], 400);
            }

            if ($id) {
                if (!iterator_to_array($db->query('SELECT lang_cms_id FROM melis_cms_lang WHERE lang_cms_id = ?', [$id]))) {
                    return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
                }
                $db->query('UPDATE melis_cms_lang SET lang_cms_locale = ?, lang_cms_name = ? WHERE lang_cms_id = ?', [$locale, $name, $id]);
                return $this->jsonResponse(['success' => true, 'data' => ['id' => $id]]);
            }

            $db->query('INSERT INTO melis_cms_lang (lang_cms_locale, lang_cms_name) VALUES (?, ?)', [$locale, $name]);
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
            if (!iterator_to_array($db->query('SELECT lang_cms_id FROM melis_cms_lang WHERE lang_cms_id = ?', [$id]))) {
                return $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
            $db->query('DELETE FROM melis_cms_lang WHERE lang_cms_id = ?', [$id]);
            return $this->jsonResponse(['success' => true, 'data' => null]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────────

    private function formatLang(array $r): array
    {
        return [
            'id'     => (int)    $r['lang_cms_id'],
            'locale' => (string) $r['lang_cms_locale'],
            'name'   => (string) $r['lang_cms_name'],
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
