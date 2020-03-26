<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class MiniTemplateMenuManagerController extends AbstractActionController
{
    public $module = 'meliscms';
    public $tool_key = 'meliscms_mini_template_menu_manager_tool';
    public $form_key = 'menu_manager_tool_site';
    public $form_config_path = 'meliscms/tools/meliscms_mini_template_menu_manager_tool/forms/menu_manager_tool_site';

    public function renderMenuManagerToolAction() {}
    public function renderMenuManagerToolHeaderAction() {}
    public function renderMenuManagerToolBodyAction() {}

    public function renderMenuManagerToolBodyLeftAction() {
        $data = $this->getRequest()->getPost();
        $form = $this->getForm($this->module, $this->tool_key, $this->form_key);

        $view = new ViewModel();
        $view->form = $form;
        return $view;
    }

    public function renderMenuManagerToolBodyRightAction() {
        $cms_lang_table = $this->getServiceLocator()->get('MelisEngineTableCmsLang');
        $languages = $cms_lang_table->fetchAll()->toArray();

        $container = new Container('meliscore');
        $current_locale = $container['melis-lang-locale'];
        $current_lang = $cms_lang_table->getEntryByField('lang_cms_locale', $current_locale)->current();


        $view = new ViewModel();
        $view->melisKey = $this->getMelisKey();
        $view->languages = $languages;
        $view->current_lang = $current_lang;
        return $view;
    }

    /**
     * Returns the melis key
     * @return mixed
     */
    private function getMelisKey()
    {
        return $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);
    }

    /**
     * Get Form
     * @param $module
     * @param $toolKey
     * @param $formKey
     * @return mixed
     */
    private function getForm($module, $toolKey, $formKey)
    {
        $toolSvc = $this->getServiceLocator()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey($module, $toolKey);
        return $toolSvc->getForm($formKey);
    }
}