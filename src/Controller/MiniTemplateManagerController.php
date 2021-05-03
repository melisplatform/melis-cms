<?php
/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use MelisCore\Controller\MelisAbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;

class MiniTemplateManagerController extends MelisAbstractActionController {
    public $module = 'meliscms';
    public $tool_key = 'meliscms_mini_template_manager_tool';
    public $form_key = 'mini_template_manager_tool_add_form';

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
     * Returns the list of sites for the table
     * @return ViewModel
     */
    public function renderMiniTemplateManagerToolTableSitesAction() {
        $view = new ViewModel();
        $view->site_modules = $this->getSiteModules();
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
     * @return ViewModel
     */
    public function renderMiniTemplateManagerToolAddHeaderAction() {
        $params = $this->params()->fromQuery();
        $view = new ViewModel();
        $view->formType = ($params['templateName'] == 'new_template') ? 'create' : 'update';
        $view->templateName = ($params['templateName'] != 'new_template') ? $params['templateName'] : '';
        return $view;
    }

    /**
     * Mini template manager tool- add new mini-template form container
     * @return ViewModel
     */
    public function renderMiniTemplateManagerToolAddBodyFormAction() {
        $params = $this->params()->fromQuery();
        $form = $this->getForm($this->module, $this->tool_key, $this->form_key);
        $form->get('miniTemplateSiteModule')->setValueOptions($this->getSiteModules());

        if ($params['templateName'] !== 'new_template') {
            $site_service = $this->getServiceManager()->get('MelisCmsSiteService');
            $path = $site_service->getModulePath($params['module']) . '/public/miniTemplatesTinyMce/';

            $data = [
                'miniTemplateSiteModule' => $params['module'],
                'miniTemplateName' => $params['templateName'],
                'miniTemplateHtml' => file_get_contents($path . $params['templateName'] . '.phtml')
            ];

            $form->setAttribute('id', 'id_mini_template_manager_tool_update');
            $form->setAttribute('class', 'mini_template_manager_tool_update');
            $form->setAttribute('name', 'mini_template_manager_tool_update');
            $form->setData($data);
        } else {
            $form->setData(['miniTemplateSiteModule' => $params['module']]);
        }

        $view = new ViewModel();
        $view->form = $form;
        $view->formType = ($params['templateName'] == 'new_template') ? 'create' : 'update';
        $view->max_size = $this->getMaxUploadOrPostSize();
        $view->current_module = $params['module'] ?? '';
        $view->current_template = $params['templateName'] ?? '';
        $view->categoryId = $params['categoryId'] ?? '';
        $view->imgSource = $params['thumbnail'] ?? '';
        $view->siteId = $params['siteId'] ?? '';
        return $view;
    }

    /**
     * Body/content - Data table
     */
    public function renderMiniTemplateManagerToolBodyDataTableAction()
    {
        $translator = $this->getServiceManager()->get('translator');
        $melisKey = $this->getMelisKey();
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
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
            ['order' => '[[1, "asc"]]']
        );
        return $view;
    }

    /**
     * Returns the list of mini templates for the mini template manager tool data table
     * @return JsonModel
     */
    public function getMiniTemplatesAction()
    {
        $post = $this->getRequest()->getPost();
        $total = $filtered = 0;
        $filtered_templates = [];
        $order = 'asc';
        $search = $post['search']['value'];

        // get order
        if (! empty($post['order'])) {
            if ($post['order'][0]['column'] == 1) {
                $order = $post['order'][0]['dir'];
            }
        }

        if (! empty($post['site_name'])) {
            $site_service = $this->getServiceManager()->get('MelisCmsSiteService');
            $path = $site_service->getModulePath($post['site_name']) . '/public/miniTemplatesTinyMce';

            if (file_exists($path)) {
                $mtpl_service = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
                $mini_templates_temp = $mtpl_service->getMiniTemplates($post['site_name']);
                $mini_templates = [];

                foreach ($mini_templates_temp as $mini_template) {
                    $exploded = explode('.', $mini_template);
                    $templateName = $exploded[0];
                    $thumbnail = $mtpl_service->getMiniTemplateThumbnail($path, $templateName);
                    $thumbnail_file = '';

                    if (! empty($thumbnail)) {
                        $thumbnail_file = '/' . $post['site_name'] . '/miniTemplatesTinyMce/' . $thumbnail['file'];
                        $thumbnail = '<img class="mini-template-tool-table-image" src="' . '/' . $post['site_name'] . '/miniTemplatesTinyMce/' . $thumbnail['file'] . '?rand=' . uniqid('', true) . '">';
                    } else {
                        $thumbnail = '<img class="mini-template-tool-table-image" src="/MelisFront/plugins/images/default.jpg" width=100 height=100>';
                    }

                    $template = [
                        'image' => $thumbnail,
                        'html_path' => '<p>' . explode('..', $path)[1] . '/' . $mini_template . '</p>',
                        'DT_RowId' => $templateName,
                        'DT_RowAttr' => [
                            'templateName' => $templateName,
                            'thumbnail' => $thumbnail_file,
                            'module' => $post['site_name']
                        ]
                    ];

                    if (! empty($post['search']['value'])) {
                        if (stripos($mini_template, $search) !== false)
                            $mini_templates[] = $template;
                    } else {
                        $mini_templates[] = $template;
                    }
                }

                if ($order == 'desc')
                    $mini_templates = array_reverse($mini_templates);

                $total = $filtered = count($mini_templates);
                $filtered_templates = array_slice($mini_templates, $post['start'], $post['length']);
            }
        }

        return new JsonModel([
            'draw' => $post['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $filtered_templates
        ]);
    }

    /**
     * Create mini template
     * @return JsonModel
     */
    public function createMiniTemplateAction() {
        $data = array_merge((array) $this->getRequest()->getPost(), $this->params()->fromFiles());

        $this->getEventManager()->trigger('meliscms_mini_template_manager_create_start', $this, $data);

        $cat_id = $data['categoryId'];
        unset($data['categoryId']);
        $siteId = $data['siteId'];
        unset($data['siteId']);

        $service = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
        $form = $this->getForm($this->module, $this->tool_key, $this->form_key);
        $form->setData($data);
        $success = 0;
        $errors = [];
        $message = 'tr_meliscms_mini_template_create_fail';

        if (!$form->isValid())
            $errors = $this->getFormErrors($form->getMessages(), $form);

        if (empty($errors)) {
            $uploaded_img = null;
            $uploaded_img_extension = null;

            if (! empty($data['miniTemplateThumbnail']['tmp_name'])) {
                $uploaded_img = $data['miniTemplateThumbnail']['tmp_name'];
                $uploaded_img_extension = explode('/', $data['miniTemplateThumbnail']['type'])[1];
            }

            $res = $service->createMiniTemplate(
                $data['miniTemplateSiteModule'],
                $data['miniTemplateName'],
                $data['miniTemplateHtml'],
                $uploaded_img,
                $uploaded_img_extension,
                $cat_id,
                $siteId
            );

            $success = $res['success'];
            $errors = $res['errors'];

            if ($success)
                $message = 'tr_meliscms_mini_template_created_successfully';
        }

        $response = [
            'success' => $success,
            'textTitle' => 'Mini-template',
            'textMessage' => $message,
            'errors' => $errors,
            'data' => $res['data'] ?? [],
        ];

        $this->getEventManager()->trigger(
            'meliscms_mini_template_manager_create_end',
            $this,
            array_merge($response, ['typeCode' => 'CMS_MTPL_ADD'])
        );
        return new JsonModel($response);
    }

    /**
     * update mini template
     * @return JsonModel
     */
    public function updateMiniTemplateAction() {
        $data = array_merge((array) $this->getRequest()->getPost(), $this->params()->fromFiles());
        $this->getEventManager()->trigger('meliscms_mini_template_manager_update_start', $this, $data);

        $current_data = [
            'miniTemplateSiteModule' => $data['current_module'],
            'miniTemplateName' => $data['current_template']
        ];
        $new_data = [
            'miniTemplateSiteModule' => $data['miniTemplateSiteModule'],
            'miniTemplateName' => $data['miniTemplateName'],
            'miniTemplateHtml' => $data['miniTemplateHtml'],
            'miniTemplateThumbnail' => $data['miniTemplateThumbnail'],
        ];

        $service = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
        $success = 0;
        $message = 'tr_meliscms_mini_template_update_fail';

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

            if ($success)
                $message = 'tr_meliscms_mini_template_updated_successfully';
        }

        $response = [
            'success' => $success,
            'textTitle' => 'Mini-template',
            'textMessage' => $message,
            'errors' => $errors,
            'data' => $res['data'] ?? [],
        ];

        $this->getEventManager()->trigger(
            'meliscms_mini_template_manager_update_end',
            $this,
            array_merge($response, ['typeCode' => 'CMS_MTPL_UPDATE'])
        );
        return new JsonModel($response);
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
        $site_service = $this->getServiceManager()->get('MelisCmsSiteService');
        $current_site_path = $site_service->getModulePath($current_data['miniTemplateSiteModule']) . '/public/miniTemplatesTinyMce';
        $new_site_path = $site_service->getModulePath($new_data['miniTemplateSiteModule']) . '/public/miniTemplatesTinyMce';
        $translator = $this->getServiceManager()->get('translator');
        $errors = [];

        if ($form->isValid()) {
            if ($current_data['miniTemplateSiteModule'] != $new_data['miniTemplateSiteModule']) {
                if (file_exists($new_site_path . '/' . $new_data['miniTemplateName'] . '.phtml')) {
                    $errors['miniTemplateName'] = [
                        'error' => $translator->translate('tr_meliscms_mini_template_manager_tool_form_create_error_file_already_exists'),
                        'label' => $form->get('miniTemplateName')->getLabel()
                    ];
                }
            } else {
                if ($current_data['miniTemplateName'] !== $new_data['miniTemplateName']) {
                    if (file_exists($current_site_path . '/' . $new_data['miniTemplateName'] . '.phtml')) {
                        $errors['miniTemplateName'] = [
                            'error' => $translator->translate('tr_meliscms_mini_template_manager_tool_form_create_error_file_already_exists'),
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
        $this->getEventManager()->trigger('meliscms_mini_template_manager_delete_start', $this, $data);
        $mtpl_service = $this->getServiceManager()->get('MelisCmsMiniTemplateService');
        $site_service = $this->getServiceManager()->get('MelisCmsSiteService');
        $path = $site_service->getModulePath($data['module']) . '/public/miniTemplatesTinyMce';
        $minitemplate = $path . '/' . $data['template'] . '.phtml';
        $minitemplate_thumbnail = $mtpl_service->getMiniTemplateThumbnail($path, $data['template']);
        $thumbnail_path = null;
        $errors = [];
        $success = 0;
        $message = 'tr_meliscms_mini_template_delete_fail';

        if (! empty($minitemplate_thumbnail)) {
            $thumbnail_path = $minitemplate_thumbnail['path'];
        }

        $ress = $mtpl_service->deleteMiniTemplate($minitemplate, $thumbnail_path, $data['template']);

        if (! empty($ress)) {
            $errors = $ress['errors'];
            $success = $ress['success'];

            if ($success)
                $message = 'tr_meliscms_mini_template_deleted_successfully';
        }

        $response = [
            'success' => $success,
            'textTitle' => 'Mini-template',
            'textMessage' => $message,
            'errors' => $errors,
        ];

        $this->getEventManager()->trigger(
            'meliscms_mini_template_manager_delete_end',
            $this,
            array_merge($response, ['typeCode' => 'CMS_MTPL_DELETE'])
        );
        return new JsonModel($response);
    }

    /**
     * Format as bytes
     * @param $size
     * @return float|int
     */
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
        $toolSvc = $this->getServiceManager()->get('MelisCoreTool');
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

    /**
     * Returns the max upload size
     * @return float|int|string
     */
    private function getMaxUploadOrPostSize() {
        $max_post = ini_get('post_max_size');
        $max_upload = ini_get('upload_max_filesize');
        $max_size = 0;

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

        if ($max_size != 0)
            $max_size = $this->asBytes($max_size);

        return $max_size;
    }

    private function getSiteModules() {
        $site_service = $this->getServiceManager()->get('MelisCmsSiteService');
        $sites = $site_service->getAllSites();
        $site_modules = [];

        foreach ($sites as $site) {
            if (! in_array($site['site_name'], $site_modules))
                $site_modules[$site['site_name']] = $site['site_name'];
        }

        return $site_modules;
    }
}