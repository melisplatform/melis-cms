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
    public $module = 'meliscms';
    public $tool_key = 'meliscms_mini_template_manager_tool';
    public $form_key = 'mini_template_manager_tool_add_form';
    public $file_types = ['png', 'PNG', 'jpg', 'JPG', 'jpeg', 'JPEG'];

    // TODO:
    //  Update Add-mini-template page header deatils to "Enter here the details of your mini-template"
    //  Add in add-mini-template page select site error when changing site and it is linked to a category
    // not writable error

    public function renderMiniTemplateManagerToolAction() {}
    public function renderMiniTemplateManagerToolHeaderAction() {}
    public function renderMiniTemplateManagerToolHeaderAddBtnAction() {}
    public function renderMiniTemplateManagerToolBodyAction() {}
    public function renderMiniTemplateManagerToolTableLimitAction() {}
    public function renderMiniTemplateManagerToolTableSearchAction() {}
    public function renderMiniTemplateManagerToolTableRefreshAction() {}
    public function renderMiniTemplateManagerToolTableActionEditAction() {}
    public function renderMiniTemplateManagerToolTableActionDeleteAction() {}
    public function renderMiniTemplateManagerToolAddBodyAction() {}

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
     * Mini template manager tool - Add new mini-template container
     * @return ViewModel
     */
    public function renderMiniTemplateManagerToolAddAction() {
        $view = new ViewModel();
        $view->templateName = $this->params()->fromQuery('templateName', '');
        return $view;
    }

    public function renderMiniTemplateManagerToolAddHeaderAction() {
        $params = $this->params()->fromQuery();
        $view = new ViewModel();
        $view->formType = ($params['templateName'] == 'new_template') ? 'create' : 'update';
        return $view;
    }

    /**
     * Mini template manager tool- add new mini-template form container
     * @return ViewModel
     */
    public function renderMiniTemplateManagerToolAddBodyFormAction() {
        $form = $this->getForm($this->module, $this->tool_key, $this->form_key);
        $params = $this->params()->fromQuery();
        $data = [];

        $max_post = ini_get('post_max_size');
        $max_upload = ini_get('upload_max_filesize');
        $max_size = 0;

        // get the smaller value
        if ($max_post != $max_upload) {
            if ($max_post > $max_upload) {
                if ($max_upload != 0) {
                    $max_size = $max_upload;
                } else {
                    $max_size = $max_post;
                }
            }
        } else {
            $max_size = $max_post;
        }

        if ($params['templateName'] !== 'new_template') {
            $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
//            $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');
//            $category = $table->getEntryByField('mtplct_template_name', $params['templateName'])->current();

            $path = $service->getModuleMiniTemplatePath($params['module']);
            $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
            $site = $siteTable->getEntryByField('site_name', $params['module'])->current();

            $data['miniTemplateSite'] = $site->site_id;
            $data['miniTemplateName'] = $params['templateName'];
            $data['miniTemplateHtml'] = file_get_contents($path . '/' . $params['templateName'] . '.phtml');

            $form->setAttribute('id', 'id_mini_template_manager_tool_update');
            $form->setAttribute('name', 'mini_template_manager_tool_update');
            $form->setData($data);
        } else {
            $form->setData(['miniTemplateSite' => $params['siteId']]);
        }

        $view = new ViewModel();
        $view->form = $form;
        $view->formType = ($params['templateName'] == 'new_template') ? 'create' : 'update';
        $view->current_module = $params['module'] ?? '';
        $view->current_template = $params['templateName'] ?? '';
        $view->max_size = $this->asBytes($max_size);
        $view->categoryId = $params['categoryId'] ?? '';
        return $view;
    }

    /**
     * Body/content - Data table
     */
    public function renderMiniTemplateManagerToolBodyDataTableAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $melisKey = $this->getMelisKey();
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey('meliscms', 'meliscms_mini_template_manager_tool');
        $columns = $melisTool->getColumns();
        $columns['actions'] = ['text' => $translator->translate('tr_meliscms_action')];

        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->tableColumns = $columns;
        $view->getToolDataTableConfig = $melisTool->getDataTableConfiguration(
            '#tableMiniTemplateManager',
            false,
            false,
            []
        );
        return $view;
    }

    /**
     * Returns the list of mini templates for the mini template manager tool data table
     * @return JsonModel
     */
    public function getMiniTemplatesAction()
    {
        $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
        $post = $this->getRequest()->getPost();
        $draw = $post['draw'];
        $start = $post['start'];
        $length = $post['length'];
        $total = $filtered = 0;
        $mini_templates_filtered = [];
        $search = $post['search']['value'] ?? '';

        if (! empty($post['site_name'])) {
            $path = $service->getModuleMiniTemplatePath($post['site_name']);
            if (! empty($path)) {
                $mini_templates_temp = $service->getMiniTemplates($post['site_name']);
                $mini_templates = [];

                foreach ($mini_templates_temp as $mini_template) {
                    $exploded = explode('.', $mini_template);
                    $templateName = $exploded[0];
                    $thumbnail = $this->getMiniTemplateThumbnail($path, $templateName);

                    if (! empty($thumbnail))
                        $thumbnail = '<img class="mini-template-tool-table-image" src="'.'/'.$post['site_name'].'/miniTemplatesTinyMce/'.$thumbnail['file'].'" width=100 height=100>';
                    else
                        $thumbnail =  '<img class="mini-template-tool-table-image" src="/MelisFront/plugins/images/default.jpg" width=100 height=100>';

                    if (! empty($search)) {
                        if (stripos($mini_template, $search) !== false) {
                            $mini_templates[] = [
                                'image' => $thumbnail,
                                'html_path' => '<p data-module="' . $post['site_name'] . '">' . explode('..', $path)[1] . '/' . $mini_template . '</p>',
                                'DT_RowId' => $templateName
                            ];
                        }
                    } else {
                        $mini_templates[] = [
                            'image' => $thumbnail,
                            'html_path' => '<p data-module="' . $post['site_name'] . '">' . explode('..', $path)[1] . '/' . $mini_template . '</p>',
                            'DT_RowId' => $templateName
                        ];
                    }
                }

                $total = $filtered = count($mini_templates);
                $mini_templates_filtered = array_slice($mini_templates, $start, $length);
            }
        }

        return new JsonModel([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $mini_templates_filtered
        ]);
    }

    /**
     * Gets the mini template thumbnail
     * @param $path
     * @param $template
     * @return array|null
     */
    private function getMiniTemplateThumbnail($path, $template)
    {
        $files = glob($path ."/" . $template . ".*");

        foreach ($files as $file) {
            $base_name = basename($file);
            $exploded_name = explode('.', $base_name);

            if (in_array($exploded_name[1], $this->file_types)) {
                return [
                    'file' => $base_name,
                    'path' => $file
                ];
            }
        }

        return null;
    }

    /**
     * Create mini template
     * @return JsonModel
     */
    public function createMiniTemplateAction() {
        $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
        $data = array_merge((array) $this->getRequest()->getPost(), $this->params()->fromFiles());
        $cat_id = $data['categoryId'];
        unset($data['categoryId']);
        $form = $this->getForm($this->module, $this->tool_key, $this->form_key);
        $form->setData($data);
        $success = 0;
        $errors = [];

        if ($form->isValid()) {
            $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
            $module = $siteTable->getEntryById($data['miniTemplateSite'])->current()->site_name;
            $uploaded_img = null;
            $uploaded_img_extension = null;

            if (! empty($data['miniTemplateThumbnail']['tmp_name'])) {
                $uploaded_img = $data['miniTemplateThumbnail']['tmp_name'];
                $uploaded_img_extension = explode('/', $data['miniTemplateThumbnail']['type'])[1];
            }

            $res = $service->createMiniTemplate($module, $data['miniTemplateName'], $data['miniTemplateHtml'], $uploaded_img, $uploaded_img_extension, $cat_id);

            if ($res['success']) {
                $success = 1;
            } else {
                $errors = $res['errors'];
            }
        } else {
            $errors = $this->getFormErrors($form->getMessages(), $form);
        }

        return new JsonModel([
            'success' => $success,
            'errors' => $errors
        ]);
    }

    /**
     * update mini template
     * @return JsonModel
     */
    public function updateMiniTemplateAction() {
        $data = array_merge((array) $this->getRequest()->getPost(), $this->params()->fromFiles());
        $current_data = [
            'miniTemplateSite' => $data['current_module'],
            'miniTemplateName' => $data['current_template']
        ];
        $new_data = [
            'miniTemplateSite' => $data['miniTemplateSite'],
            'miniTemplateName' => $data['miniTemplateName'],
            'miniTemplateHtml' => $data['miniTemplateHtml'],
            'miniTemplateThumbnail' => $data['miniTemplateThumbnail'],
        ];
        $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $current_site_data = $siteTable->getEntryByField('site_name', $current_data['miniTemplateSite'])->current();
        $current_data['miniTemplateSite'] = $current_site_data->site_id;
        $success = 0;

        $form = $this->getForm($this->module, $this->tool_key, $this->form_key);
        $form->setData($new_data);

        $errors = $this->checkUpdateErrors(
            $form,
            $current_data,
            $new_data
        );

        if (empty($errors)) {
            $res = $service->updateMiniTemplate($current_data, $new_data, $data['image']);
            $success = $res['success'];
            $errors = $res['errors'];
        }

        return new JsonModel([
            'success' => $success,
            'errors' => $errors
        ]);
    }

    /**
     * This will check for errors when updating mini-template
     * @param $form
     * @param $current_site
     * @param $current_site_path
     * @param $current_template
     * @param $data
     * @return array|mixed
     */
    private function checkUpdateErrors($form, $current_data, $new_data) {
        $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $current_site = $siteTable->getEntryById($current_data['miniTemplateSite'])->current();
        $current_site_path = $service->getModuleMiniTemplatePath($current_site->site_name);
        $new_site = $siteTable->getEntryById($new_data['miniTemplateSite'])->current();
        $new_site_path = $service->getModuleMiniTemplatePath($new_site->site_name);
        $errors = [];

        if ($form->isValid()) {
            if ($current_site->site_id !== $new_data['miniTemplateSite']) {
                if (file_exists($new_site_path . '/' . $new_data['miniTemplateName'] . '.phtml')) {
                    $errors['miniTemplateName'] = [
                        'error' => 'File can\'t be created because it already exists',
                        'label' => $form->get('miniTemplateName')->getLabel()
                    ];
                }
            } else {
                if ($current_data['miniTemplateName'] !== $new_data['miniTemplateName']) {
                    if (file_exists($current_site_path . '/' . $new_data['miniTemplateName'] . '.phtml')) {
                        $errors['miniTemplateName'] = [
                            'error' => 'File can\'t be created because it already exists',
                            'label' => $form->get('miniTemplateName')->getLabel()
                        ];
                    }
                }
            }
        } else {
            $errors = $this->getFormErrors($form->getMessages(), $form);
        }

        return $errors;
    }

    /**
     * Delete mini template
     * @return JsonModel
     */
    public function deleteMiniTemplateAction() {
        $data = (array) $this->getRequest()->getPost();
        $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
        $path = $service->getModuleMiniTemplatePath($data['module']);
        $minitemplate = $path . '/' . $data['template'] . '.phtml';
        $minitemplate_thumbnail = $this->getMiniTemplateThumbnail($path, $data['template']);
        $thumbnail_path = null;
        $errors = [];
        $success = 0;

        if (! empty($minitemplate_thumbnail)) {
            $thumbnail_path = $minitemplate_thumbnail['path'];
        }

        $ress = $service->deleteMiniTemplate($minitemplate, $thumbnail_path, $data['template']);

        if (! empty($ress)) {
            $errors = $ress['errors'];
            $success = $ress['success'];
        }

        return new JsonModel([
            'success' => $success,
            'errors' => $errors
        ]);
    }

    // get bytes
    private function asBytes($size) {
        $size = trim($size);
        $s = [ 'g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10 ];
        return intval($size) * ($s[strtolower(substr($size,-1))] ?: 1);
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

    /**
     * This will get the errors of the form
     * @param $errors
     * @param $formConfig
     * @return mixed
     */
    private function getFormErrors($errors, $form)
    {
        foreach ($errors as $fieldName => $fieldErrors) {
            $errors[$fieldName] = $fieldErrors;
            $errors[$fieldName]['label'] = $form->get($fieldName)->getLabel();
        }

        return $errors;
    }
}