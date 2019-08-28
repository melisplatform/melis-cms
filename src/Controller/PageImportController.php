<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use ZipArchive;

class PageImportController extends AbstractActionController
{
    const FORM_CONFIG_PATH = 'meliscms/tools/meliscms_tree_sites_tool/forms/meliscms_tree_sites_import_page_form';
    const FORM_PARENT_KEY = 'meliscms_tree_sites_tool';
    const FORM_KEY = 'meliscms_tree_sites_import_page_form';

    /**
     * Renders Import Pages Modal
     * @return ViewModel
     */
    public function renderPageImportModalAction()
    {
        $view = new ViewModel();
        $view->setTerminal(false);
        $view->melisKey  = $this->params()->fromQuery('melisKey', null);;
        $view->importForm = $this->getImportForm();
        $view->isAdmin = $this->getUser()->usr_admin;
        $view->pageId = $this->params()->fromQuery('pageId', null);
        return $view;
    }

    public function checkImportFormAction()
    {
        $data = array_merge(get_object_vars($this->getRequest()->getPost()), $this->params()->fromFiles());
        $form = $this->getImportForm();
        $formConfig = $this->getFormConfig(self::FORM_CONFIG_PATH, self::FORM_KEY);
        $this->prepareFile('page_tree_import', $form);
        $form->setData($data);
        $success = true;

        if ($form->isValid()) {
            $formData = $form->getData();
            $formData['keepIds'] = isset($data['keepIds']) ? $data['keepIds'] : null;
        } else {
            $errors = $this->getFormErrors($form->getMessages(), $formConfig);
        }

        if (!empty($errors))
            $success = false;

        return new JsonModel([
            'success' => $success,
            'errors' => !empty($errors) ? $errors : [],
            'result' => !empty($formData) ? $formData : null
        ]);
    }

    /**
     * Checks the zip file
     * @return JsonModel
     */
    public function importTestAction()
    {
        $pageImportSvc = $this->getServiceLocator()->get('MelisCmsPageImportService');
        $data = get_object_vars($this->getRequest()->getPost());
        $formData = json_decode($data['formData'], true);
        $success = true;

        $zip = $this->openZip($formData['page_tree_import']['tmp_name']);
    
        if (!empty($zip)) {
            $xml = $zip->getFromName('PageExport.xml');
            $zip->close();
            $res = $pageImportSvc->importTest($xml, $formData['keepIds']);

            if (!empty($res['errors'])) {
                $errors = $res['errors'];
            }
        } else {
            $errors[] = 'Invalid Zip File';
        }

        if (!empty($errors))
            $success = false;

        return new JsonModel([
            'success' => $success,
            'errors' => !empty($errors) ? $errors : [],
        ]);
    }

    /**
     * Imports the tree
     * @return JsonModel
     */
    public function importPageAction()
    {
        $pageImportSvc = $this->getServiceLocator()->get('MelisCmsPageImportService');
        $data = get_object_vars($this->getRequest()->getPost());
        $formData = json_decode($data['formData'], true);
        $pageId = $data['pageid'];
        $zip = $this->openZip($formData['page_tree_import']['tmp_name']);
        $success = true;

        if (!empty($zip)) {
            $xml = $zip->getFromName('PageExport.xml');
            $res = $pageImportSvc->importPageTree($pageId, $xml, $formData['keepIds']);

            if (!empty($res['errors'])) {
                $success = $res['success'];
                $errors = $res['errors'];
            } else {
                $zip->extractTo($_SERVER["DOCUMENT_ROOT"] . '/xml');
                $zip->close();
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/xml/media')) {
                    $this->recurse_copy($_SERVER['DOCUMENT_ROOT'] . '/xml/media', $_SERVER['DOCUMENT_ROOT'] . '/media');
                    $this->deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/xml');
                }
            }
        } else {
            $errors[] = 'Unexpected error';
        }

        return new JsonModel([
            'success' => $success,
            'errors' => !empty($errors) ? $errors : [],
            'pagesCount' => $res['pagesCount'] ?? 0
        ]);
    }

    /**
     * Prepares The Zip File By Moving It To The Filesystem + Filter for the input file
     * @param $input
     * @param $form
     */
    private function prepareFile($input, $form) {
        $target = $_SERVER['DOCUMENT_ROOT'] . '/xml';

        if (!is_dir($target))
            mkdir($target, 0777);

        $fileInput = new \Zend\InputFilter\FileInput($input);
        $fileInput->setRequired(true);
        $fileInput->getFilterChain()->attachByName(
            'filerenameupload',
            [
                'target'    => $target,
                'randomize' => false,
                'use_upload_extension' => true,
            ]
        );

        $form->getInputFilter()->add($fileInput);
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
     * Returns The Import Form
     * @return mixed
     */
    public function getImportForm()
    {
        return $this->getForm('meliscms', self::FORM_PARENT_KEY, self::FORM_KEY);
    }

    /**
     * Get User
     * @return mixed
     */
    private function getUser()
    {
        $authSvc = $this->getServiceLocator()->get('MelisCoreAuth');
        return $authSvc->getIdentity();
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
    private function getFormErrors($errors, $formConfig)
    {
        foreach ($errors as $errorKey => $errorValue) {
            foreach ($formConfig['elements'] as $elementKey => $elementValue) {
                if ($elementValue['spec']['name'] == $errorKey && !empty($elementValue['spec']['options']['label'])) {
                    $errors[$elementValue['spec']['options']['label']] = reset($errorValue);
                    unset($errors[$errorKey]);
                }
            }
        }

        return $errors;
    }

    /**
     * Returns Zip Object
     * @param $path
     * @return mixed
     */
    private function openZip($path)
    {
        $zip = new ZipArchive;
        return $zip->open($path) ? $zip : null;
    }

    public function recurse_copy($src,$dst) {
        $dir = opendir($src);

        if (!file_exists($dst))
            mkdir($dst);

        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Function to delete directory/file
     *
     * @param $dir
     * @return bool
     */
    public function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }
}
