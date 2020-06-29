<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use MelisCore\Controller\MelisAbstractActionController;
use MelisCore\Service\MelisCoreRightsService;
use Laminas\Config\Reader\Json;
use phpDocumentor\Reflection\Types\Boolean;

class SitesModuleLoaderController extends MelisAbstractActionController
{
    /**
     * Returns the module that is dependent to the provided module
     * @return JsonModel
     */
    public function getDependentsAction()
    {
        $success = 0;
        $modules = array();
        $request = $this->getRequest();
        $message = 'tr_meliscore_module_management_no_dependencies';
        $tool    = $this->getServiceManager()->get('MelisCoreTool');

        if ($request->isPost()) {
            $module = $tool->sanitize($request->getPost('module'));
            if ($module) {
                $modules = $this->getModuleSvc()->getChildDependencies($module);
                if ($modules) {
                    $message = $tool->getTranslation('tr_melis_cms_sites_module_loading_deactivate_module_with_prerequisites_notice', array($module));
                    $success = 1;
                }
            }
        }

        $response = array(
            'success' => $success,
            'modules' => $modules,
            'message' => $tool->getTranslation($message)
        );

        return new JsonModel($response);

    }

    public function getRequiredDependenciesAction()
    {
        $success = 0;
        $modules = array();
        $requiredModules = array();
        $request = $this->getRequest();
        $tool    = $this->getServiceManager()->get('MelisCoreTool');
        $siteModuleLoadSvc = $this->getServiceManager()->get("MelisCmsSiteModuleLoadService");
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $message = 'tr_meliscore_module_management_no_dependencies';

        if($request->isPost()) {
            $module = $tool->sanitize($request->getPost('module'));
            if($module) {
                $modules = $this->getModuleSvc()->getDependencies($module);
                $existingModules = $siteModuleLoadSvc->getModules($siteId);
                foreach ($modules as $moduleName){
                    if(isset($existingModules[$moduleName])){
                        array_push($requiredModules,$moduleName);
                    }
                }
                if($requiredModules) {
                    $message = $tool->getTranslation('tr_melis_cms_sites_module_loading_activate_module_with_prerequisites_notice', array($module));
                    $success = 1;
                }
            }
        }

        $response = array(
            'success' => $success,
            'modules' => $requiredModules,
            'message' => $tool->getTranslation($message)
        );

        return new JsonModel($response);

    }

    public function renderToolSitesModuleLoadAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();

        $rightService = $this->getServiceManager()->get('MelisCoreRights');
        $canAccess = $rightService->canAccess('meliscms_tool_sites_module_load_content');

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->canAccess = $canAccess;

        return $view;
    }

    /**
     * @return void|ViewModel
     */
    public function renderToolSitesModuleLoadContentAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $moduleName = $this->params()->fromQuery('moduleName', '');
        $siteModuleLoadSvc = $this->getServiceManager()->get("MelisCmsSiteModuleLoadService");
        $modulesSvc = $this->getServiceManager()->get('ModulesService');
        $siteTable = $this->getServiceManager()->get('MelisEngineTableSite');
        $siteData = $siteTable->getEntryByField("site_name",$moduleName)->toArray();
        $siteNames = array();
        if(isset($siteData)){
            foreach ($siteData as $siteDatum){
                array_push($siteNames,$siteDatum['site_label']);
            }
        }

        /**
         * check if site id is empty
         */
        if(empty($siteId))
            return;

        $melisCoreAuth = $this->getServiceManager()->get('MelisCoreAuth');

        $userAuthDatas = $melisCoreAuth->getStorage()->read();

        $isAdmin = isset($userAuthDatas->usr_admin) || $userAuthDatas->usr_admin != "" ? $userAuthDatas->usr_admin : 0;

        $modulesInfo = $modulesSvc->getModulesAndVersions();
        $modules = $siteModuleLoadSvc->getModules($siteId);
        $melisKey = $this->getMelisKey();

        $view = new ViewModel();

        $view->modulesInfo = $modulesInfo;
        $view->modules = $modules;
        $view->melisKey = $melisKey;
        $view->isAdmin = $isAdmin;
        $view->siteId = $siteId;
        $view->siteNames = $siteNames;
        $view->siteModuleName = $moduleName;

        return $view;
    }

    /**
     * @return \MelisCore\Service\MelisCoreModulesService
     */
    protected function getModuleSvc()
    {
        /**
         * @var \MelisCore\Service\MelisCoreModulesService $modulesSvc
         */
        $modulesSvc = $this->getServiceManager()->get('ModulesService');
        return $modulesSvc;
    }

    private function getMelisKey()
    {
        $melisKey = $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);
        return $melisKey;
    }
    /**
     * this method will get the meliscore tool
     */
    private function getTool()
    {
        $toolSvc = $this->getServiceManager()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey('MelisCmsUserAccount', 'melis_cms_user_account');
        return $toolSvc;
    }

}
