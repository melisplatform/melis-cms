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
    public $form_config_path = 'meliscms/tools/meliscms_mini_template_manager_tool/forms/mini_template_manager_tool_add_form';
    public $file_types = ['png', 'PNG', 'jpg', 'JPG', 'jpeg', 'JPEG'];
    public $mini_template_dir = 'miniTemplatesTinyMce';
    /**
     * TODO:
     *  - change mini template manager refresh. To only refresh the table zone.
     *  - site selector translation
     *  - mini template manager tool table - action translation
     *  - default thumbnail for the mini templates
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
        $draw = $post['draw'];
        $start = $post['start'];
        $length = $post['length'];
        $total = $filtered = 0;
        $miniTemplates = [];

        if (! empty($post['site_name'])) {
            $path = $this->getModuleMiniTemplatePath($post['site_name']);
            if (! empty($path)) {
                $miniTemplates = $this->getMiniTemplates($post['site_name'], $path);
                $total = $filtered = count($miniTemplates);
                $miniTemplates = array_slice($miniTemplates, $start, $length);
            }
        }

        return new JsonModel([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $miniTemplates
        ]);
    }

    /**
     * Get all mini tempaltes of a site
     * @param $module
     * @param $path
     * @return array
     */
    private function getMiniTemplates($module, $path)
    {
        $miniTemplates = array_diff(scandir($path), ['..', '.']);
        $templates = [];
        $explodedPath = explode('..', $path);


        // TODO: change variable name to files
        foreach ($miniTemplates as $miniTemplate) {
            $exploded = explode('.', $miniTemplate);
            $templateName = $exploded[0];
            $extension = $exploded[1];
            $dataTag = '';

            if (in_array($extension, $this->file_types)) {
                $templates[$templateName]['image'] = '<img class="mini-template-tool-table-image" data-image="' . $miniTemplate . '" src="/' . $module . '/miniTemplatesTinyMce/' . $miniTemplate.'" width=100 height=100>';
            } else if (in_array($extension, ['phtml'])) {
                $tag = '<p class="mini-template-tool-table-path" %s>' . $explodedPath[1] . '/' . $miniTemplate . '</p>';
                $dataTag .= 'data-templatename="' . $templateName . '" ';
                $dataTag .= 'data-module="' . $module . '" ';
                $dataTag .= 'data-path="' . $path . '"';
                $templates[$templateName]['html_path'] = sprintf($tag, $dataTag);
            }
        }

        foreach($templates as $key => $template) {
            if (empty($template['html_path']))
                unset($templates[$key]);
        }

        $miniTemplates = [];
        foreach ($templates as $key => $template) {
            array_push($miniTemplates, $template);
        }

        return $miniTemplates;
    }

    /**
     * Gets the mini template thumbnail
     * @param $path
     * @param $template
     * @return array|null
     */
    private function getMiniTemplateThumbnail($path, $template)
    {
        $files = glob($path ."/*" . $template . "*");

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
     * Get all files of a mini template
     * @param $path
     * @param $template
     * @return array
     */
    private function getMiniTemplateFiles($path, $template) {
        $files = glob($path ."/*" . $template . "*");
        $data = [];

        foreach ($files as $file) {
            $base_name = basename($file);
            $exploded_name = explode('.', $base_name);

            if (in_array($exploded_name[1], $this->file_types)) {
                $data['image']['file'] = $base_name;
                $data['image']['path'] = $file;
            } else {
                $data['template']['file'] = $base_name;
                $data['template']['path'] = $file;
            }
        }

        return $data;
    }


    private function getModuleMiniTemplatePath($module) {
        $path = $this->getComposerModulePath($module);
        if (empty($path))
            $path = $this->getNonComposerModulePath($module);
        return $path;
    }

    private function getComposerModulePath($module) {
        $composerSrv = $this->serviceLocator->get('MelisEngineComposer');
        $path = $composerSrv->getComposerModulePath($module);
        $miniTemplatePath = $path . '/public' . '/miniTemplatesTinyMce';
        return (! empty($path)) ? $miniTemplatePath : '';
    }

    /**
     * Returns the module path
     * @param $module
     * @return string
     */
    private function getNonComposerModulePath($module) {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites';
        if (file_exists($path) && is_dir($path)) {
            $sites = $this->getDir($path);
            if (! empty($sites)) {
                if (in_array($module, $sites)) {
                    $publicPath = $path . '/' . $module . '/public';
                    $miniTemplatePath = $publicPath . '/miniTemplatesTinyMce';
                }
            }
        }
        return $miniTemplatePath ?? '';
    }

    /**
     * Create mini template
     * @return JsonModel
     */
    public function createMiniTemplateAction() {
        $data = array_merge((array) $this->getRequest()->getPost(), $this->params()->fromFiles());
        $form = $this->getForm($this->module, $this->tool_key, $this->form_key);
        $success = 0;
        $errors = [];
        $form->setData($data);

        if ($form->isValid()) {
            $this->createMiniTemplate($data, $success, $errors);
        } else {
            $errors = $this->getFormErrors($form->getMessages(), $form);
        }

        return new JsonModel([
            'success' => $success,
            'errors' => $errors
        ]);
    }

    /**
     * create mini template
     * @param $data
     * @param $success
     * @param $errors
     */
    public function createMiniTemplate($data, &$success, &$errors) {
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $module = $siteTable->getEntryById($data['miniTemplateSite'])->current()->site_name;
        $path = $this->getModuleMiniTemplatePath($module);

        if (is_writable($path)) {
            // create the file
            $miniTemplateFile = fopen($path . '/' . $data['miniTemplateName'] . '.phtml', 'wb');
            fwrite($miniTemplateFile, $data['miniTemplateHtml']);
            fclose($miniTemplateFile);
            // copy the thumbnail
            $extension = explode('/', $data['miniTemplateThumbnail']['type'])[1];
            copy(
                $data['miniTemplateThumbnail']['tmp_name'],
                $path . '/' . $data['miniTemplateName'] . '.' . $extension
            );

            $success = 1;
        } else {
            $errors[] = $path . ' is not writtable.';
        }
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
        $current_site = $siteTable->getEntryByField('site_name', $current_module)->current();
        $current_site_path = $this->getModuleMiniTemplatePath($current_site->site_name);
        $form = $this->getForm($this->module, $this->tool_key, $this->form_key);
        $form->setData($data);
        $errors = [];
        $success = 1;

        if ($form->isValid()) {
            // check if site is changed
            if ($current_site->site_id != $data['miniTemplateSite']) {
                $isNewSite = true;
                $new_site = $siteTable->getEntryById($data['miniTemplateSite'])->current();
                $new_site_path = $this->getModuleMiniTemplatePath($new_site->site_name);
                // move files from old to new site
                $thumbnail_file = $this->getMiniTemplateThumbnail($current_site_path, $data['miniTemplateName']);

                if (!file_exists($new_site_path))
                    mkdir($new_site_path, 0777);

                rename($thumbnail_file['path'], $new_site_path . '/' . $thumbnail_file['file']);
                rename($current_site_path . '/' . $data['miniTemplateName'] . '.phtml', $new_site_path . '/' . $data['miniTemplateName'] . '.phtml');
                $current_site_path = $new_site_path;
            }

            // check if template name is changed
            if ($current_template != $data['miniTemplateName']) {
                rename(
                    $current_site_path . '/' . $current_template . '.phtml',
                    $current_site_path . '/' . $data['miniTemplateName'] . '.phtml'
                );
                $thumbnail_file = $this->getMiniTemplateThumbnail($current_site_path, $current_template);
                $extension = explode('.', $thumbnail_file['file'])[1];
                rename(
                    $thumbnail_file['path'],
                    $current_site_path . '/' . $data['miniTemplateName'] . '.' . $extension
                );
            }

            // check if html content is changed
            $file_contents = file_get_contents($current_site_path . '/' . $current_template . '.phtml');
            if ($file_contents !== $data['miniTemplateHtml']) {
                $file = fopen($current_site_path . '/' . $current_template . '.phtml', 'w');
                fwrite($file, $data['miniTemplateHtml']);
                fclose($file);
            }

            // check if image is changed
            if (! empty($data['miniTemplateThumbnail']['name'])) {
                $thumbnail_file = $this->getMiniTemplateThumbnail($current_site_path, $current_template);
                if (file_exists($thumbnail_file['path']))
                    unlink($thumbnail_file['path']);

                $extension = explode('.', $data['miniTemplateThumbnail']['name'])[1];

                copy(
                    $data['miniTemplateThumbnail']['tmp_name'],
                    $current_site_path . '/' . $current_template . '.' . $extension
                );
            }
        } else {
            $errors = $this->getFormErrors($form->getMessages(), $form);
            $success = 0;
        }

        return new JsonModel([
            'success' => $success,
            'errors' => $errors
        ]);
    }

    /**
     * Delete mini template
     * @return JsonModel
     */
    public function deleteMiniTemplateAction() {
        $data = (array) $this->getRequest()->getPost();
        $minitemplate = $data['path'] . '/' . $data['template'] . '.phtml';
        $minitemplateThumbnail = $data['path'] . '/' . $data['image'];
        $errors = [];
        $success = 0;

        if (is_writable($minitemplate)) {
            unlink($minitemplate);

            if (is_writable($minitemplateThumbnail)) {
                unlink($minitemplateThumbnail);
                $success = 1;
            } else {
                $errors[] = 'No permission to delete image. file was not deleted';
            }
        } else {
            $errors[] = 'No permission to delete minitemplate. file was not deleted';
        }

        return new JsonModel([
            'success' => $success,
            'errors' => $errors
        ]);
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
     * Mini template manager table action - edit
     */
    public function renderMiniTemplateManagerToolTableActionEditAction() {}

    /**
     * Mini template manager table action - delete
     */
    public function renderMiniTemplateManagerToolTableActionDeleteAction() {}

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
     * Mini template manager tool - Add new mini-template header container + title
     */
    public function renderMiniTemplateManagerToolAddHeaderAction() {}

    /**
     * Mini template manager tool - Add new mini-template body container
     */
    public function renderMiniTemplateManagerToolAddBodyAction() {}

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
            $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
            $site = $siteTable->getEntryByField('site_name', $params['module'])->current();

            $data['miniTemplateSite'] = $site->site_id;
            $data['miniTemplateName'] = $params['templateName'];
            $data['miniTemplateHtml'] = file_get_contents($params['path'] . '/' . $params['templateName'] . '.phtml');

            $form->setAttribute('id', 'id_mini_template_manager_tool_update');
            $form->setAttribute('name', 'mini_template_manager_tool_update');
            $form->setData($data);
            $type = 'update';
        }

        $view = new ViewModel();
        $view->form = $form;
        $view->type = $type;
        $view->current_module = $params['module'] ?? '';
        $view->current_template = $params['templateName'] ?? '';
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
     * Get directory files
     * @param $dir
     * @param array $excludeSubFolders
     * @return array
     */
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
     * This will get the form config
     * @param $formPath
     * @param $form
     * @return mixed
     */
    private function getFormConfig($formPath, $form)
    {
        $melisCoreConfigSvc = $this->getServiceLocator()->get('MelisCoreConfig');
        return $melisCoreConfigSvc->getFormMergedAndOrdered($formPath, $form);
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