<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use MelisCore\Service\MelisCoreRightsService;
use Zend\Config\Reader\Json;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * Site Tool Plugin
 */
class SitesModuleLoaderController extends AbstractActionController
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
        $tool    = $this->getServiceLocator()->get('MelisCoreTool');

        if ($request->isPost()) {
            $module = $tool->sanitize($request->getPost('module'));
            if ($module) {
                $modules = $this->getModuleSvc()->getChildDependencies($module);
                if ($modules) {
                    $message = $tool->getTranslation('tr_meliscore_module_management_inactive_confirm', array($module, $module));
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
        $tool    = $this->getServiceLocator()->get('MelisCoreTool');
        $siteModuleLoadSvc = $this->getServiceLocator()->get("MelisCmsSiteModuleLoadService");
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
                    $message = $tool->getTranslation('tr_melis_cms_sites_module_loading_activate_module_with_prerequisites_notice', array($module, $module));
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

    /**
     * @return \MelisCore\Service\MelisCoreModulesService
     */
    protected function getModuleSvc()
    {
        /**
         * @var \MelisCore\Service\MelisCoreModulesService $modulesSvc
         */
        $modulesSvc = $this->getServiceLocator()->get('ModulesService');
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
        $toolSvc = $this->getServiceLocator()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey('MelisCmsUserAccount', 'melis_cms_user_account');
        return $toolSvc;
    }

}
