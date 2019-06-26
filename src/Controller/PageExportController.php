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

    public function exportPageAction()
    {
        //get the request
        $request = $this->getRequest();
        $data = get_object_vars($request->getPost());
        
        $pageExportService = $this->getServiceLocator()->get('MelisCmsPageExportService');
        return $data;
    }
}
