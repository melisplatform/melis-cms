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

class MiniTemplateManagerController extends AbstractActionController
{
    /**
     * TODO:
     *  - change mini template manager refresh. To only refresh the table zone.
     *  - site selector translation
     */

    /**
     * Tool view container
     */
    public function renderMiniTemplateManagerToolAction() {}

    /**
     * Header view container + title and subtitle
     */
    public function renderMiniTemplateManagerToolHeaderAction() {}

    /**
     * Header - Add button
     */
    public function renderMiniTemplateManagerToolHeaderAddBtnAction() {}

    /**
     * Body or contents view container
     */
    public function renderMiniTemplateManagerToolBodyAction() {}

    /**
     * Body/content - Data table
     */
    public function renderMiniTemplateManagerToolBodyDataTableAction() {
        $translator = $this->getServiceLocator()->get('translator');
        $melisKey = $this->getMelisKey();
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey('meliscms', 'meliscms_mini_template_manager_tool');

        $columns = $melisTool->getColumns();
        $columns['actions'] = ['text' => $translator->translate('tr_meliscms_action')];

        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->tableColumns = $columns;
        $view->getToolDataTableConfig = $melisTool->getDataTableConfiguration('#tableMiniTemplateManager',false,false,array('order' => '[[ 0, "desc" ]]'));
        return $view;
    }

    /**
     * Returns the list of mini templates for the mini template manager tool data table
     * @return JsonModel
     */
    public function getMiniTemplatesAction()
    {
        $post = $this->getRequest()->getPost();

        if (empty($post['site_id'])) {
            return new JsonModel([
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        } else {
            print_r($post);
            exit;
            // Get Templates
//        $this->getMiniTemplates();
            // Prepare Templates Data

            // MelisFrontMiniTemplateConfigListener.php

            return new JsonModel([
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }
    }

    private function getMiniTemplates()
    {

    }

    private function getAllSiteModules()
    {
        $sites = [];
        $this->getAllSiteModuleInVendor($sites);
        $this->getAllSiteModuleInMelisSites($sites);


    }

    /**
     * Get all site modules inside vendor
     */
    private function getAllSiteModuleInVendor(&$sites)
    {
        $composerSrv = $this->serviceLocator->get('MelisEngineComposer');
        $vendorModules = $composerSrv->getVendorModules();
        if (! empty($vendorModules)) {
            foreach ($vendorModules as $key => $module) {
                if ($composerSrv->isSiteModule($module)) {
                    $path = $composerSrv->getComposerModulePath($module);
                    if (!empty($path)) {
                        $miniTemplatePath = $path . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'miniTemplatesTinyMce';

                        if (file_exists($miniTemplatePath) && is_dir($miniTemplatePath))
                            $sites[$module] = $path;
                    }
                }
            }
        }
    }

    private function getAllSiteModuleInMelisSites(&$sites)
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites';
        if(file_exists($path) && is_dir($path)) {
            $sitesModule = $this->getDir($path);
            if (!empty($sitesModule)) {
                foreach ($sitesModule as $key => $module) {
                    //public site folder
                    $publicFolder = $path . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'public';
                    //get the mini template folder path
                    $miniTemplatePath = $publicFolder . DIRECTORY_SEPARATOR . 'miniTemplatesTinyMce';

                    if (file_exists($miniTemplatePath) && is_dir($miniTemplatePath))
                        $sites[$module] = $path;
                }
            }
        }
    }

    /**
     * Mini template manager table filter - limit
     */
    public function renderMiniTemplateManagerToolTableLimitAction() {}

    /**
     * @return ViewModel
     */
    public function renderMiniTemplateManagerToolTableSitesAction() {
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $view = new ViewModel();
        $view->sites = $siteTable->fetchAll()->toArray();
        return $view;
    }

    /**
     * Mini template manager table filter - search
     */
    public function renderMiniTemplateManagerToolTableSearchAction() {}

    /**
     * Mini template manager table filter - refresh
     */
    public function renderMiniTemplateManagerToolTableRefreshAction() {}

    /**
     * Mini template manager tool - Add new mini-template container
     * @return ViewModel
     */
    public function renderMiniTemplateManagerToolAddAction() {
        $view = new ViewModel();
        $view->id = (int) $this->params()->fromQuery('newsId', '');
        return $view;
    }

    /**
     * Mini template manager tool - Add new mini-template header container + title
     */
    public function renderMiniTemplateManagerToolAddHeaderAction() {}

    public function renderMiniTemplateManagerToolAddBodyAction() {}

    public function renderMiniTemplateManagerToolAddBodyFormAction() {}

    /**
     * Returns the melis key
     * @return mixed
     */
    private function getMelisKey()
    {
        return $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);
    }

    private function getDir($dir, $excludeSubFolders = [])
    {
        $directories = [];
        if (file_exists($dir)) {
            $excludeDir = array_merge(['.', '..', '.gitignore'], $excludeSubFolders);
            $directory  = array_diff(scandir($dir), $excludeDir);

            foreach ($directory as $d) {
                if (is_dir($dir.'/'.$d)) {
                    $directories[] = $d;
                }
            }

        }
        return $directories;
    }
}