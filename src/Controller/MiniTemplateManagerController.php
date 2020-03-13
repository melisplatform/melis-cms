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
        // Get Templates
        $this->getMiniTemplates();
        // Prepare Templates Data

        // MelisFrontMiniTemplateConfigListener.php

        return new JsonModel([
            'draw' => 0,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ]);
    }

    private function getMiniTemplates()
    {
        $sitePath = $this->get_all_site_modules();

        print_r($sitePath);
        exit;
    }

    private function get_all_site_modules()
    {
        $site_path = [];
        $composer_srv = $this->serviceLocator->get('MelisEngineComposer');
        $vendor_modules = $composer_srv->getVendorModules();

        if (! empty($vendor_modules)) {
            foreach ($vendor_modules as $key => $module) {
                if ($composer_srv->isSiteModule($module)) {
                    $path = $composer_srv->getComposerModulePath($module);
                    if (!empty($path)) {
                        $path = $path . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'miniTemplatesTinyMce';
                        $site_path[$module] = $path;
                    }
                }
            }
        }

        return $site_path;
    }

    /**
     * Mini template manager table filter - limit
     */
    public function renderMiniTemplateManagerToolTableLimitAction() {}

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

}