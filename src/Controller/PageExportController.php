<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCms\Service\MelisCmsPageExportService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

/**
 * This class renders Melis CMS Page export
 */
class PageExportController extends AbstractActionController
{

    /**
     * @return ViewModel
     */
    public function renderPageExportModalAction()
    {
        //get the request
        $request = $this->getRequest();
        $getValues = get_object_vars($request->getQuery());

        //get user info
        $auth = $this->getServiceLocator()->get('MelisCoreAuth');
        $user = $auth->getIdentity();

        $userTable = $this->getServiceLocator()->get('MelisCoreTableUser');
        $userData = $userTable->getEntryByField('usr_id', $user->usr_id)->current();


        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');

        // tell the Tool what configuration in the app.tools.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tree_sites_tool');
        //prepare the page export form
        $form = $melisTool->getForm('meliscms_tree_sites_export_page_form');

        //set the selected page id
        $form->get('selected_page_id')->setValue($getValues['pageId']);

        $melisKey = $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);
        $view = new ViewModel();
        $view->setTerminal(false);
        $view->melisKey  = $melisKey;
        $view->exportForm = $form;
        $view->isAdmin = $userData->usr_admin;
        return $view;
    }

    /**
     * @return \Laminas\Http\Response\Stream|JsonModel
     */
    public function exportPageAction()
    {
        $translator = $this->getServiceLocator()->get('translator');

        $result = [
            'success' => true,
            'message' => '',
            'error' => ''
        ];
        //get the request
        $request = $this->getRequest();
        $data = get_object_vars($request->getPost());

        //prepare the services need for page export
        /** @var MelisCmsPageExportService $pageExportService */
        $pageExportService = $this->getServiceLocator()->get('MelisCmsPageExportService');

        if(!empty($data['selected_page_id']) && $request->isPost()){
            $pageId = $data['selected_page_id'];
            //check whether we are going to export the page resources
            $exportResources = false;
            if(!empty($data['export_page_resources'])){
                $exportResources = true;
            }

            if(!empty($data['page_export_type'])){
                //export the page and it's sub pages
                if($data['page_export_type'] == 1){
                    $export = $pageExportService->exportPageTree($pageId, true, $exportResources);
                }else{
                    //export page only (don't include sub pages)
                    $export = $pageExportService->exportPageTree($pageId, false, $exportResources);
                }

                /**
                 * if success generate zip file
                 */
                if($export['success']){
                    $rootFolder = $_SERVER["DOCUMENT_ROOT"];
                    //create the temporary folder
                    if(is_writable($rootFolder)){
                        $folderPath = $rootFolder.'/PageExport';
                        if(!file_exists($folderPath)) {
                            mkdir($folderPath, 0777);
                        }
                        /**
                         * insert all the files to the
                         * created folder
                         */
                        //manager resources
                        $pageExportService->manageResources($folderPath, $export['resources']);
                        //create the xml file
                        $xmlFileName = 'PageExport.xml';
                        $xml = new \DOMDocument();
                        $xml->preserveWhiteSpace = false;
                        $xml->formatOutput = true;
                        //remove the xml declaration
                        $xmlRes = preg_replace( "/<\?xml.+?\?>/", "", $export['xml']);
                        $xml->loadXml($xmlRes);
                        $xml->save($folderPath.'/'.$xmlFileName);
                        /**
                         * convert the folder to zip
                         */

                        $pageName = $this->getPageName($pageId);
                        $zipFileName = date('Ymd-His').'_PageExport_' . $pageName . '.zip';

                        if($pageExportService->zipFolder($folderPath, $zipFileName)){
                            $zipPath = $_SERVER['DOCUMENT_ROOT'].'/'.$zipFileName;
                            //after we zip the folder, remove the folder
                            if($pageExportService->deleteDirectory($folderPath)){
                                /**
                                 * process to download the zip
                                 */
                                $response = new \Laminas\Http\Response\Stream();
                                $response->setStream(fopen($zipPath, 'r'));
                                $response->setStatusCode(200);

                                $headers = new \Laminas\Http\Headers();
                                $headers->addHeaderLine('Content-Type', 'application/zip; charset=utf-8')
                                    ->addHeaderLine('Content-Disposition', 'attachment; filename="' . $zipFileName . '"')
                                    ->addHeaderLine('Content-Length', filesize($zipPath))
                                    ->addHeaderLine('fileName', $zipFileName);
                                $response->setHeaders($headers);

                                $this->getEventManager()->trigger('meliscms_page_tree_export_end', $this, [
                                    'success' => true,
                                    'textTitle' => 'tr_melis_cms_tree_export_title',
                                    'textMessage' => 'tr_melis_cms_tree_export_log_message',
                                    'typeCode' => 'CMS_PAGE_EXPORT',
                                    'itemId' => $pageId
                                ]);

                                return $response;
                            }else{
                                //problem on deleting the temporary folder
                                $result['success'] = false;
                                $result['message'] = [
                                    'error' => $translator->translate('tr_melis_cms_tree_export_error_problem_deleting_temp'),
                                    'label' => $translator->translate('tr_melis_cms_tree_export_page')
                                ];
                            }
                        }else{
                            //cannot convert the folder to zip
                            $result['success'] = false;
                            $result['message'] = [
                                'error' => $translator->translate('tr_melis_cms_tree_export_error_converting_zip'),
                                'label' => $translator->translate('tr_melis_cms_tree_export_page')
                            ];
                        }
                    }else{
                        //temporary folder is not writable
                        $result['success'] = false;
                        $result['message'] = [
                            'error' => $translator->translate('tr_melis_cms_tree_export_error_temp_not_writable'),
                            'label' => $translator->translate('tr_melis_cms_tree_export_page')
                        ];
                    }
                }else{
                    //failed exporting page
                    $result['success'] = false;
                    $result['message'] = [
                        'error' => $translator->translate('tr_melis_cms_tree_export_error_failed'),
                        'label' => $translator->translate('tr_melis_cms_tree_export_page')
                    ];
                }
            }
        }

        $result['success'] = false;
        $result['message'] = [
            'error' => $translator->translate('tr_melis_cms_tree_export_failed'),
            'label' => $translator->translate('tr_melis_cms_tree_export_page')
        ];

        return new JsonModel($result);
    }

    private function getPageName($pageId)
    {
        $pagePublishedTable = $this->getServiceLocator()->get('MelisEngineTablePagePublished');
        $page = $pagePublishedTable->getPublishedSitePagesById($pageId)->toArray();

        if (! empty($page)) {
            $pageName = $page[0]['page_name'];
        } else {
            $pageSavedTable = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
            $pageName = $pageSavedTable->getSavedSitePagesById($pageId)->toArray()[0]['page_name'];
        }

        return substr($pageName, 0, 20);
    }
}