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
use Zend\Http\PhpEnvironment\Response as HttpResponse;

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

        if (! empty($errors))
            $success = false;

        return new JsonModel([
            'success' => $success,
            'errors' => ! empty($errors) ? $errors : [],
            'result' => ! empty($formData) ? $formData : null
        ]);
    }

    /**
     * Checks the zip file
     * @return JsonModel
     */
    public function importTestAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $pageImportSvc = $this->getServiceLocator()->get('MelisCmsPageImportService');
        $data = get_object_vars($this->getRequest()->getPost());
        $formData = json_decode($data['formData'], true);
        $success = true;

        $zip = new ZipArchive();
        $res = $zip->open($formData['page_tree_import']['tmp_name']);
    
        if ($res === true) {
            $xml = $zip->getFromName('PageExport.xml');
            $zip->close();

            if (! empty($xml))
                $res = $pageImportSvc->importTest($xml, $formData['keepIds']);
            else
                $errors[] = $translator->translate('tr_melis_cms_page_tree_error_no_page_export_xml');

            if (! empty($res['errors'])) {
                $errors = $res['errors'];
            }
        } else {
            $errors[] = $translator->translate('tr_melis_cms_page_tree_error_invalid_zip_file');
        }

        if (! empty($errors))
            $success = false;

        return new JsonModel([
            'success' => $success,
            'errors' => ! empty($errors) ? $errors : [],
        ]);
    }

    /**
     * Imports the tree
     * @return JsonModel
     */
    public function importPageAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $pageImportSvc = $this->getServiceLocator()->get('MelisCmsPageImportService');
        $data = get_object_vars($this->getRequest()->getPost());
        $formData = json_decode($data['formData'], true);
        $pageId = $data['pageid'];
        $zip = new ZipArchive;
        $res = $zip->open($formData['page_tree_import']['tmp_name']);
        $success = true;
        $hasResources = false;

        if ($res === true) {
            if ($zip->count() > 1)
                $hasResources = true;

            $xml = $zip->getFromName('PageExport.xml');
            $zip->close();
            if (! empty($xml)) {
                if ($hasResources)
                    $res = $pageImportSvc->importPageTree($pageId, $xml, $formData['keepIds'], $formData['page_tree_import']['tmp_name']);
                else
                    $res = $pageImportSvc->importPageTree($pageId, $xml, $formData['keepIds']);
            } else {
                $errors[] = $translator->translate('tr_melis_cms_page_tree_error_no_page_export_xml');
            }

            if (! empty($res['errors'])) {
                $success = $res['success'];
                $errors = $res['errors'];
            } else {
                $this->getEventManager()->trigger('meliscms_page_tree_import_end', $this, [
                    'success' => true,
                    'textTitle' => $translator->translate('tr_melis_cms_page_tree_import_title'),
                    'textMessage' => 'tr_melis_cms_page_tree_log_message',
                    'typeCode' => 'CMS_PAGE_IMPORT',
                    'itemId' => $pageId
                ]);
            }
        } else {
            $errors[] = $translator->translate('tr_melis_cms_page_tree_error_unexpected');
        }

        return new JsonModel([
            'success' => $success,
            'errors' => ! empty($errors) ? $errors : [],
            'pagesCount' => $res['pagesCount'] ?? 0,
            'idsMap' => $res['idsMap'] ?? [],
            'keepIds' => $formData['keepIds'] ?? false
        ]);
    }

    /**
     * Prepares The Zip File By Moving It To The Filesystem + Filter for the input file
     * @param $input
     * @param $form
     */
    private function prepareFile($input, $form) {
        $target = $_SERVER['DOCUMENT_ROOT'] . '/xml';

        if (! is_dir($target))
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
        $translator = $this->getServiceLocator()->get('translator');

        foreach ($errors as $errorKey => $errorValue) {
            foreach ($formConfig['elements'] as $elementKey => $elementValue) {
                if ($elementValue['spec']['name'] == $errorKey && ! empty($elementValue['spec']['name'])) {
                    $errors[$translator->translate('tr_melis_cms_page_tree_import_zip_file')] = reset($errorValue);
                    unset($errors[$errorKey]);
                }
            }
        }

        return $errors;
    }

    public function exportCsvAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $fileName = 'import_new_ids.csv';
        $postData = get_object_vars($this->getRequest()->getPost());
        $data = $postData['idsMap'];
        $separator = ',';

        if ($data) {
            $content = '';

            // clean data. remove ids that were not updated
            foreach ($data as $key => $value) {

                foreach ($value as $oldId => $newId) {
                    if ($oldId == $newId) {
                        unset($data[$key][$oldId]);
                    }
                }

                if (empty($data[$key])) {
                    unset($data[$key]);
                }
            }

            foreach ($data as $key => $value) {
                $content .= $key . $separator;
                $content .= "\r\n";

                $content .= $translator->translate('tr_melis_cms_page_tree_csv_old_id') . $separator;
                $content .= $translator->translate('tr_melis_cms_page_tree_csv_new_id') . $separator;
                $content .= "\r\n";

                foreach ($value as $oldId => $newId) {
                    if ($oldId != $newId) {
                        $content .= (string)$oldId . $separator . (string)$newId . $separator;
                        $content .= "\r\n";
                    }
                }

                $content .= "\r\n";
            }

            $response = new HttpResponse();
            $headers = $response->getHeaders();
            $headers->addHeaderLine('Content-Type', 'text/csv; charset=utf-8');
            $headers->addHeaderLine('Content-Disposition', "attachment; filename=\"" . $fileName . "\"");
            $headers->addHeaderLine('Accept-Ranges', 'bytes');
            $headers->addHeaderLine('Content-Length', strlen($content));
            $headers->addHeaderLine('fileName', $fileName);
            $response->setContent($content);

        }

        return $response;
    }
}
