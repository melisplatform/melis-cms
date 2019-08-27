<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\Http\PhpEnvironment\Response as HttpResponse;
use MelisCms\Service\MelisCmsPageExportService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

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
        $view->isAdmin = $user->usr_admin;
        return $view;
    }

    /**
     * @return \Zend\Http\Response\Stream|JsonModel
     */
    public function exportPageAction()
    {
        $result = [
            'success' => false,
            'message' => 'tr_melis_cms_tree_export_failed',
            'error' => ''
        ];
        //get the request
        $request = $this->getRequest();
        $data = get_object_vars($request->getPost());

        //prepare the services need for page export
        /** @var MelisCmsPageExportService $pageExportService */
        $pageExportService = $this->getServiceLocator()->get('MelisCmsPageExportService');

        $translator = $this->getServiceLocator()->get('translator');

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
                        $zipFileName = date('Y_m_d').'_PageExport.zip';
                        if($pageExportService->zipFolder($folderPath, $zipFileName)){
                            $zipPath = $folderPath.'/../'.$zipFileName;
                            //after we zip the folder, remove the folder
                            if($pageExportService->deleteDirectory($folderPath)){
                                /**
                                 * process to download the zip
                                 */
                                $response = new \Zend\Http\Response\Stream();
                                $response->setStream(fopen($zipPath, 'r'));
                                $response->setStatusCode(200);

                                $headers = new \Zend\Http\Headers();
                                $headers->addHeaderLine('Content-Type', 'application/zip; charset=utf-8')
                                    ->addHeaderLine('Content-Disposition', 'attachment; filename="' . $zipFileName . '"')
                                    ->addHeaderLine('Content-Length', filesize($zipPath))
                                    ->addHeaderLine('fileName', $zipFileName);
                                $response->setHeaders($headers);

                                return $response;
                            }else{
                                //problem on deleting the temporary folder
                                print_r('1');
                            }
                        }else{
                            //cannot convert the folder to zip
                            print_r('2');
                        }
                    }else{
                        //temporary folder is not writable
                        print_r('3');
                    }
                }else{
                    //failed exporting page
                    print_r('4');
                }
            }
        }
        $result['message'] = $translator->translate($result['message']);
        return new JsonModel($result);
    }
}