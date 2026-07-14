<?php

namespace MelisCms\Controller;

use MelisReactApi\Controller\CapabilityGuardTrait;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * API REST pour l'outil « Menu manager » de MelisCms (catégories de mini-templates, par site).
 *
 * Réutilise intégralement `MelisCmsMiniTemplateService` (mêmes méthodes que le contrôleur legacy
 * `MiniTemplateMenuManagerController` : getTree/saveTree/saveCategory/deleteCategory/getCategoryTexts) —
 * aucune logique SQL/métier réimplémentée ici, juste le contrat JSON `{success,data,error}`.
 * Routes (déclarées dans config/react-api.php) :
 *   GET  /melis/react-api/menu-manager/sites            → sélecteur de site
 *   GET  /melis/react-api/menu-manager/languages         → langues dispo (traductions de catégorie)
 *   GET  /melis/react-api/menu-manager/tree              → arbre plat (catégories + mini-templates) pour un site+locale
 *   POST /melis/react-api/menu-manager/tree/save         → sauvegarde l'ordre/l'appartenance (drag & drop)
 *   GET  /melis/react-api/menu-manager/category/:id      → textes multilingues d'une catégorie (édition)
 *   POST /melis/react-api/menu-manager/category/save     → créer/mettre à jour une catégorie
 *   DELETE /melis/react-api/menu-manager/category/delete/:id → supprimer une catégorie
 */
class MelisReactApiCmsMenuManagerController extends MelisAbstractActionController
{
    use CapabilityGuardTrait;

    private const MELIS_KEY = 'meliscms_mini_template_menu_manager_tool';

    public function sitesAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $service = $this->getServiceManager()->get('MelisCmsSiteService');
            $rows = $service->getAllSites();
            $sites = array_map(fn ($r) => [
                'id'   => (int) $r['site_id'],
                'name' => trim((string) ($r['site_label'] ?? '')) !== '' ? (string) $r['site_label'] : (string) $r['site_name'],
            ], $rows);
            return $this->jsonResponse(['success' => true, 'data' => ['sites' => $sites]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function languagesAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $service = $this->getServiceManager()->get('MelisEngineLang');
            $rows = $service->getAvailableLanguages();
            $languages = array_map(fn ($r) => [
                'id'     => (int) $r['lang_cms_id'],
                'name'   => (string) $r['lang_cms_name'],
                'locale' => (string) $r['lang_cms_locale'],
            ], $rows);
            return $this->jsonResponse(['success' => true, 'data' => ['languages' => $languages]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function treeAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        $siteId = (int) $this->params()->fromQuery('siteId', 0);
        $locale = trim((string) $this->params()->fromQuery('locale', ''));
        if ($siteId <= 0 || $locale === '') {
            return $this->jsonResponse(['success' => false, 'error' => 'siteId and locale are required'], 400);
        }

        try {
            $service = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
            $tree = $service->getTree($siteId, $locale);
            return $this->jsonResponse(['success' => true, 'data' => ['nodes' => array_values($tree)]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function saveTreeAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('edit')) { return $denyCap; }

        try {
            $body = json_decode($this->getRequest()->getContent(), true) ?? [];
            $siteId = (int) ($body['siteId'] ?? 0);
            $nodes = is_array($body['nodes'] ?? null) ? $body['nodes'] : [];
            if ($siteId <= 0) {
                return $this->jsonResponse(['success' => false, 'error' => 'siteId is required'], 400);
            }

            $treeData = array_map(fn ($n) => [
                'id'     => (string) ($n['id'] ?? ''),
                'parent' => (string) ($n['parent'] ?? '#'),
                'type'   => (string) ($n['type'] ?? ''),
            ], $nodes);

            $service = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
            $res = $service->saveTree($siteId, $treeData);

            if (!$res['success']) {
                return $this->jsonResponse(['success' => false, 'error' => implode(', ', $res['errors'] ?? []) ?: 'Save failed'], 400);
            }
            return $this->jsonResponse(['success' => true, 'data' => null]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function categoryAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('edit')) { return $denyCap; }

        $catId = (int) $this->params()->fromRoute('id', 0);
        if ($catId <= 0) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid ID'], 400);
        }

        try {
            $service = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
            $texts = $service->getCategoryTexts($catId);
            $translations = [];
            foreach ($texts as $text) {
                $translations[(int) $text['mtplct_lang_id']] = (string) $text['mtplct_name'];
            }

            $categoryTable = $this->getServiceManager()->get('MelisCmsMiniTplCategoryTable');
            $category = $categoryTable->getEntryById($catId)->current();
            $status = $category ? (int) $category->mtplc_status : 1;

            return $this->jsonResponse(['success' => true, 'data' => ['id' => $catId, 'status' => $status, 'translations' => $translations]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function saveCategoryAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }

        try {
            $body = json_decode($this->getRequest()->getContent(), true) ?? [];
            $catId = isset($body['catId']) && $body['catId'] ? (int) $body['catId'] : null;
            if ($denyCap = $this->denyUnlessCan($catId ? 'edit' : 'create')) { return $denyCap; }

            $siteId = (int) ($body['siteId'] ?? 0);
            $status = (int) (!empty($body['status']) ? 1 : 0);
            $currentLocale = trim((string) ($body['currentLocale'] ?? ''));
            $translations = is_array($body['translations'] ?? null) ? $body['translations'] : [];

            if (!$catId && $siteId <= 0) {
                return $this->jsonResponse(['success' => false, 'error' => 'Le site est obligatoire.'], 400);
            }

            // Reconstruit le format attendu par MelisCmsMiniTemplateService::saveCategory() :
            // params['site_id'|'cat_id'|'status'|'currentLocale'] + une clé "{langId}_name" par langue.
            $params = [
                'site_id'       => $siteId,
                'cat_id'        => $catId,
                'status'        => $status,
                'currentLocale' => $currentLocale,
            ];
            foreach ($translations as $langId => $name) {
                $params[((int) $langId) . '_name'] = trim((string) $name);
            }

            // Les modules greffent des listeners sur ces événements (MelisCmsFlashMessengerListener
            // journalise les *_end). L'API React DOIT donc déclencher les mêmes événements que
            // MiniTemplateMenuManagerController, avec la même forme de params.
            $event    = $catId ? 'meliscms_mini_template_menu_manager_update_category' : 'meliscms_mini_template_menu_manager_create_category';
            $typeCode = $catId ? 'CMS_MTPL_CATEGORY_UPDATE' : 'CMS_MTPL_CATEGORY_ADD';

            $this->getEventManager()->trigger($event . '_start', $this, $params);

            $service = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
            $res = $service->saveCategory($params, $catId);

            $success = !empty($res['success']);
            $message = $catId
                ? ($success ? 'tr_meliscms_mini_template_menu_manager_category_updated_successfully' : 'tr_meliscms_mini_template_menu_manager_category_update_fail')
                : ($success ? 'tr_meliscms_mini_template_menu_manager_category_created_successfully' : 'tr_meliscms_mini_template_menu_manager_category_create_fail');

            $this->getEventManager()->trigger($event . '_end', $this, [
                'success'      => $success ? 1 : 0,
                'textTitle'    => 'tr_meliscms_mini_template_menu_manager_category',
                'textMessage'  => $message,
                'errors'       => $res['errors'] ?? [],
                'id'           => $res['id'] ?? 0,
                'categoryName' => $success ? $this->categoryNameForLog($translations, $currentLocale) : '',
                'typeCode'     => $typeCode,
                'itemId'       => $res['id'] ?? 0,
            ]);

            if (!$success) {
                return $this->jsonResponse(['success' => false, 'error' => $this->flattenErrors($res['errors'] ?? [])], 400);
            }
            return $this->jsonResponse(['success' => true, 'data' => ['id' => (int) $res['id']]], $catId ? 200 : 201);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function deleteCategoryAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('delete')) { return $denyCap; }

        $catId = (int) $this->params()->fromRoute('id', 0);
        if ($catId <= 0) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid ID'], 400);
        }

        try {
            // Legacy : $params = POST, dont ['id' => '<catId>-…'] (id du nœud jsTree). Ici l'id est déjà
            // numérique et vient de la route — on le passe sous la même clé pour les listeners.
            $this->getEventManager()->trigger('meliscms_mini_template_menu_manager_delete_category_start', $this, ['id' => $catId]);

            $service = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
            $res = $service->deleteCategory($catId);

            $success = !empty($res['success']);

            $this->getEventManager()->trigger('meliscms_mini_template_menu_manager_delete_category_end', $this, [
                'success'     => $success ? 1 : 0,
                'textTitle'   => 'tr_meliscms_mini_template_menu_manager_category',
                'textMessage' => $success ? 'tr_meliscms_mini_template_menu_manager_category_deleted_successfully' : 'tr_meliscms_mini_template_menu_manager_category_delete_fail',
                'errors'      => $res['errors'] ?? [],
                'id'          => $catId,
                'typeCode'    => 'CMS_MTPL_CATEGORY_DELETE',
                'itemId'      => $catId,
            ]);

            if (!$success) {
                return $this->jsonResponse(['success' => false, 'error' => $this->flattenErrors($res['errors'] ?? [])], 400);
            }
            return $this->jsonResponse(['success' => true, 'data' => null]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────────

    /**
     * Nom de catégorie transmis aux listeners *_end (journalisation), même règle que le legacy :
     * la traduction de la locale courante si elle existe, sinon la 1re non vide suffixée de sa langue.
     */
    private function categoryNameForLog(array $translations, string $currentLocale): string
    {
        try {
            $langService = $this->getServiceManager()->get('MelisEngineLangService');
            $currentLang = $langService->getLangByLocale($currentLocale);
            $currentLangId = (int) ($currentLang['lang_cms_id'] ?? 0);

            $fallback = '';
            foreach ($translations as $langId => $name) {
                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }
                if ((int) $langId === $currentLangId) {
                    return $name;
                }
                if ($fallback === '') {
                    $lang = $langService->getLangDataById((int) $langId);
                    $lang = !empty($lang) ? $lang[0] : [];
                    $fallback = $name . ' (' . ($lang['lang_cms_name'] ?? '') . ')';
                }
            }
            return $fallback;
        } catch (\Throwable) {
            return '';
        }
    }

    private function flattenErrors(array $errors): string
    {
        $messages = [];
        foreach ($errors as $error) {
            if (is_array($error)) {
                $messages[] = (string) ($error['error'] ?? json_encode($error));
            } else {
                $messages[] = (string) $error;
            }
        }
        return implode(', ', $messages) ?: 'Erreur';
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
