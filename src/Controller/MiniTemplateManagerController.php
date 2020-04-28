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
    public $mini_template_dir = 'miniTemplatesTinyMce';

    public function renderMiniTemplateManagerToolAction() {}
    public function renderMiniTemplateManagerToolHeaderAction() {}
    public function renderMiniTemplateManagerToolHeaderAddBtnAction() {}
    public function renderMiniTemplateManagerToolBodyAction() {}
    public function renderMiniTemplateManagerToolTableLimitAction() {}
    public function renderMiniTemplateManagerToolTableSearchAction() {}
    public function renderMiniTemplateManagerToolTableRefreshAction() {}
    public function renderMiniTemplateManagerToolTableActionEditAction() {}
    public function renderMiniTemplateManagerToolTableActionDeleteAction() {}
    public function renderMiniTemplateManagerToolAddHeaderAction() {}
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

    /**
     * Mini template manager tool- add new mini-template form container
     * @return ViewModel
     */
    public function renderMiniTemplateManagerToolAddBodyFormAction() {
        $form = $this->getForm($this->module, $this->tool_key, $this->form_key);
        $params = $this->params()->fromQuery();
        $data = [];
        $type = 'create';

        if ($params['templateName'] != 'new_template') {
            $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
            $path = $service->getModuleMiniTemplatePath($params['module']);
            $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
            $site = $siteTable->getEntryByField('site_name', $params['module'])->current();

            $data['miniTemplateSite'] = $site->site_id;
            $data['miniTemplateName'] = $params['templateName'];
            $data['miniTemplateHtml'] = file_get_contents($path . '/' . $params['templateName'] . '.phtml');

            $form->setAttribute('id', 'id_mini_template_manager_tool_update');
            $form->setAttribute('name', 'mini_template_manager_tool_update');
            $form->setData($data);
            $type = 'update';
        } else {
            $form->setData(['miniTemplateSite' => $params['siteId']]);
        }

        $view = new ViewModel();
        $view->form = $form;
        $view->formType = $type;
        $view->current_module = $params['module'] ?? '';
        $view->current_template = $params['templateName'] ?? '';
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

                    $mini_templates[] = [
                        'image' => $thumbnail,
                        'html_path' => '<p data-module="' . $post['site_name'] . '">' . explode('..', $path)[1] . '/' . $mini_template . '</p>',
                        'DT_RowId' => $templateName
                    ];
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

            $res = $service->createMiniTemplate($module, $data['miniTemplateName'], $data['miniTemplateHtml'], $uploaded_img, $uploaded_img_extension);

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
        $current_module = $data['current_module'];
        $current_template = $data['current_template'];
        unset($data['current_module'], $data['current_template'], $data['image']);
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
        $current_site = $siteTable->getEntryByField('site_name', $current_module)->current();
        $current_site_path = $service->getModuleMiniTemplatePath($current_site->site_name);
        $form = $this->getForm($this->module, $this->tool_key, $this->form_key);
        $form->setData($data);
        $new_site = $siteTable->getEntryById($data['miniTemplateSite'])->current();
        $new_site_path = $service->getModuleMiniTemplatePath($new_site->site_name);
        $errors = [];
        $success = 0;

        $errors = $this->checkUpdateErrors(
            $form,
            $current_site,
            $current_site_path,
            $new_site_path,
            $current_template,
            $data
        );

        if (empty($errors)) {
            // If site is changed
            if ($current_site->site_id !== $data['miniTemplateSite']) {
                $isNewSite = true;
                // Move files from to the new site
                $thumbnail_file = $this->getMiniTemplateThumbnail($current_site_path, $current_template);

                if (!file_exists($new_site_path))
                    mkdir($new_site_path, 0777);

                rename($thumbnail_file['path'], $new_site_path . '/' . $thumbnail_file['file']);
                rename(
                    $current_site_path . '/' . $current_template . '.phtml',
                    $new_site_path . '/' . $current_template . '.phtml'
                );

                $current_site_path = $new_site_path;
            }

            // If template name is changed
            if ($current_template !== $data['miniTemplateName']) {
                // Update the current template
                rename(
                    $current_site_path . '/' . $current_template . '.phtml',
                    $current_site_path . '/' . $data['miniTemplateName'] . '.phtml'
                );

                // If there is a thumbnail, rename it
                $thumbnail_file = $this->getMiniTemplateThumbnail($current_site_path, $current_template);
                $current_template = $data['miniTemplateName'];

                if (!empty($thumbnail_file)) {
                    $extension = explode('.', $thumbnail_file['file'])[1];
                    rename(
                        $thumbnail_file['path'],
                        $current_site_path . '/' . $data['miniTemplateName'] . '.' . $extension
                    );
                }
            }

            // check if html content is changed
            $file_contents = file_get_contents($current_site_path . '/' . $current_template . '.phtml');
            if ($file_contents !== $data['miniTemplateHtml']) {
                $file = fopen($current_site_path . '/' . $current_template . '.phtml', 'w');
                fwrite($file, $data['miniTemplateHtml']);
                fclose($file);
            }

            // check if image is changed
            if (!empty($data['miniTemplateThumbnail']['name'])) {
                $thumbnail_file = $this->getMiniTemplateThumbnail($current_site_path, $current_template);
                if (!empty($thumbnail_file))
                    unlink($thumbnail_file['path']);

                $extension = explode('.', $data['miniTemplateThumbnail']['name'])[1];

                copy(
                    $data['miniTemplateThumbnail']['tmp_name'],
                    $current_site_path . '/' . $current_template . '.' . $extension
                );
            }

            $success = 1;
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
    private function checkUpdateErrors($form, $current_site, $current_site_path, $new_site_path, $current_template, $data) {
        $errors = [];

        if ($form->isValid()) {
            if ($current_site->site_id !== $data['miniTemplateSite']) {
                if (file_exists($new_site_path . '/' . $data['miniTemplateName'] . '.phtml')) {
                    $errors['miniTemplateName'] = [
                        'error' => 'File can\'t be created because it already exists',
                        'label' => $form->get('miniTemplateName')->getLabel()
                    ];
                }
            } else {
                if ($current_template !== $data['miniTemplateName']) {
                    if (file_exists($current_site_path . '/' . $data['miniTemplateName'] . '.phtml')) {
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
        $errors = [];
        $success = 0;

        if (is_writable($minitemplate)) {
            unlink($minitemplate);

            if (! empty($minitemplate_thumbnail)) {
                if (is_writable($minitemplate_thumbnail['path'])) {
                    unlink($minitemplate_thumbnail['path']);
                    $success = 1;
                } else {
                    $errors[] = 'No permission to delete image. No file/s deleted';
                }
            }

            $table = $this->getServiceLocator()->get('MelisCmsMiniTplCategoryTemplateTable');
            $table->deleteByField('mtplct_template_name', $data['template']);
        } else {
            $errors[] = 'No permission to delete minitemplate. No file/s deleted';
        }

        return new JsonModel([
            'success' => $success,
            'errors' => $errors
        ]);
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