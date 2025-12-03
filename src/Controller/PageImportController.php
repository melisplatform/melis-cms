<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use MelisCore\Controller\MelisAbstractActionController;
use ZipArchive;
use Laminas\Http\PhpEnvironment\Response as HttpResponse;

class PageImportController extends MelisAbstractActionController
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
        $pageId = $this->params()->fromQuery('pageId', null);
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

        if (!$max_size)
            $max_size = '2M'; // set 2M default as value  if not set

        $userTable = $this->getServiceManager()->get('MelisCoreTableUser');
        $userData = $userTable->getEntryByField('usr_id', $this->getUser()->usr_id)->current();

        $view = new ViewModel();
        $view->setTerminal(false);
        $view->melisKey  = $this->params()->fromQuery('melisKey', null);;
        $view->importForm = $this->getImportForm();
        $view->isAdmin = $userData->usr_admin;
        $view->pageId = $pageId;
        $view->max_size = $this->asBytes($max_size);
        $view->pageName = $this->getPageName($pageId);
        return $view;
    }

    public function checkImportFormAction()
    {
        $data = array_merge($this->getRequest()->getPost()->toArray(), $this->params()->fromFiles());
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
        $translator = $this->getServiceManager()->get('translator');
        $pageImportSvc = $this->getServiceManager()->get('MelisCmsPageImportService');
        $data = $this->getRequest()->getPost()->toArray();
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
        $translator = $this->getServiceManager()->get('translator');
        $pageImportSvc = $this->getServiceManager()->get('MelisCmsPageImportService');
        $data = $this->getRequest()->getPost()->toArray();
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
                    'textTitle' => 'tr_melis_cms_page_tree_import_title',
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
            'keepIds' => $formData['keepIds'] ?? false,
            'firstPage' => $res['firstPage'] ?? 0
        ]);
    }

    /**
     * Prepares The Zip File By Moving It To The Filesystem + Filter for the input file
     * @param $input
     * @param $form
     */
    private function prepareFile($input, $form)
    {
        $target = $_SERVER['DOCUMENT_ROOT'] . '/xml';

        if (! is_dir($target))
            mkdir($target, 0777);

        $fileInput = new \Laminas\InputFilter\FileInput($input);
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
        $toolSvc = $this->getServiceManager()->get('MelisCoreTool');
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
        $authSvc = $this->getServiceManager()->get('MelisCoreAuth');
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
        $melisCoreConfigSvc = $this->getServiceManager()->get('MelisCoreConfig');
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
        $translator = $this->getServiceManager()->get('translator');

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
        $translator = $this->getServiceManager()->get('translator');
        $fileName = 'import_new_ids.csv';
        $postData = $this->getRequest()->getPost()->toArray();
        $data = $postData['idsMap'];
        $separator = ',';
        $arrSize = count($data);
        $count = 0;

        if ($data) {
            $content = '';

            foreach ($data as $key => $value) {
                $count++;

                if (! empty($value)) {
                    $content .= $this->getKey($key);
                    $content .= "\r\n";

                    $content .= $translator->translate('tr_melis_cms_page_tree_csv_old_id') . $separator;
                    $content .= $translator->translate('tr_melis_cms_page_tree_csv_new_id');
                    $content .= "\r\n";
                    $size = count($value);
                    $aCount = 0;

                    foreach ($value as $oldId => $newId) {
                        $aCount++;

                        if ($oldId != $newId) {
                            $content .= (string)$oldId . $separator . (string)$newId;

                            if ($count == $arrSize) {
                                if ($aCount < $size) {
                                    $content .= "\r\n";
                                }
                            } else {
                                $content .= "\r\n";
                            }
                        }
                    }

                    if ($count < $arrSize)
                        $content .= $separator . "\r\n";
                }
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

    // get bytes
    private function asBytes($size)
    {
        $size = trim($size);
        $s = ['g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10];
        return intval($size) * ($s[strtolower(substr($size, -1))] ?: 1);
    }

    private function getPageName($pageId)
    {
        $pagePublishedTable = $this->getServiceManager()->get('MelisEngineTablePagePublished');
        $page = $pagePublishedTable->getPublishedSitePagesById($pageId)->toArray();

        if (! empty($page)) {
            $pageName = $page[0]['page_name'];
        } else {
            $pageSavedTable = $this->getServiceManager()->get('MelisEngineTablePageSaved');
            $pageName = $pageSavedTable->getSavedSitePagesById($pageId)->toArray()[0]['page_name'];
        }

        return substr($pageName, 0, 20);
    }

    private function getKey($key)
    {
        if ($key == 'melis_cms_template') {
            $newKey = 'template';
        } else if ($key == 'melis_cms_lang') {
            $newKey = 'language';
        } else if ($key == 'melis_cms_style') {
            $newKey = 'style';
        } else if ($key == 'page_ids') {
            $newKey = 'page ID';
        } else {
            return $key;
        }

        return $newKey;
    }
}
