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
use Zend\Session\Container;
use MelisCms\Service\MelisCmsRightsService;

/**
 * This class renders Melis CMS Page
 */
class PageController extends AbstractActionController
{
    /**
     * Renders the page container
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $zoneConfig = $this->params()->fromRoute('zoneconfig', array());

        $this->getEventManager()->trigger('meliscms_render_page_start', $this, array('idPage' => $idPage));

        // Check rights for when it's displayed  directly on page generation
        if ($idPage != 0)
        {
            $melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');
            $melisCmsRights = $this->getServiceLocator()->get('MelisCmsRights');
            $xmlRights = $melisCoreAuth->getAuthRights();
            $isAccessible = $melisCmsRights->isAccessible($xmlRights, MelisCmsRightsService::MELISCMS_PREFIX_PAGES, $idPage);
        }
        else
            $isAccessible = true;

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;
        $view->isAccessible = $isAccessible;

        $view->prefixId = false;
        if ($this->getRequest()->isXmlHttpRequest())
            $view->prefixId = true;

        return $view;
    }

    /**
     * Renders the tab zone bellow actions (Edition/Properties/...)
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPagetabAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $zoneConfig = $this->params()->fromRoute('zoneconfig', array());

        $excludedButtons = array();
        $response        = $this->getEventManager()->trigger('melis_cms_page_tabs_alter', $this, array('idPage' => (int) $idPage));
        foreach($response as $resp) {
            if($resp) {
                if(isset($resp['idPage']) && (
                        (int) $idPage == $resp['idPage']
                    )) {
                    $excludedButtons = $resp['exclude'];
                }
            }
        }

        $view = new ViewModel();
        $view->idPage   = $idPage;
        $view->melisKey = $melisKey;
        $view->exclude  = $excludedButtons;
        return $view;
    }

    /**
     * Renders the zone in which are title and page actions
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageheadAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Renders the title zone
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageheadTitleAction()
    {
        $datasPageTree = array();
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $translator = $this->serviceLocator->get('translator');

        // Getting datas, saved version (if not will bring published datas)
        $melisPage = $this->serviceLocator->get('MelisEnginePage');
        $datasPage = $melisPage->getDatasPage($idPage, 'saved');
        if($datasPage)
            $datasPageTree = $datasPage->getMelisPageTree();

        // Locale
        $container = new Container('meliscore');
        $locale = $container['melis-lang-locale'];
        $melisTranslation = $this->getServiceLocator()->get('MelisCoreTranslation');


        // Getting info on last published page
        $lastPublishedDate = '';
        $publishedName = '';
        $datasPagePublished = $melisPage->getDatasPage($idPage, 'published');
        if (!empty($datasPagePublished))
        {
            $datasPagePublishedTree = $datasPagePublished->getMelisPageTree();
            if (!empty($datasPagePublishedTree->page_edit_date))
            {
                $lastPublishedDate = $datasPagePublishedTree->page_edit_date;

                $lastPublishedDate = strftime($melisTranslation->getDateFormatByLocate($locale), strtotime($lastPublishedDate));
            }

            if (!empty($datasPagePublishedTree->page_last_user_id))
            {
                $melisCoreTableUser = $this->getServiceLocator()->get('MelisCoreTableUser');
                $user = $melisCoreTableUser->getEntryById($datasPagePublishedTree->page_last_user_id);
                if (!empty($user))
                {
                    $user = $user->current();
                    if (!empty($user))
                        $publishedName = $user->usr_firstname . ' ' . $user->usr_lastname;
                    else
                        $publishedName = $translator->translate('tr_meliscore_user_deleted').' ('.$datasPagePublishedTree->page_last_user_id.')';
                }
            }
        }

        // Checking if there's a saved version
        $lastSavedDate = '';
        $savedName = '';


        $melisEngineTablePageSaved = $this->serviceLocator->get('MelisEngineTablePageSaved');
        $datasPageSaved = $melisEngineTablePageSaved->getEntryById($idPage);

        if (!empty($datasPageSaved))
        {
            $datasPageSaved = $datasPageSaved->current();
            if (!empty($datasPageSaved))
            {
                if (empty($datasPageSaved->page_edit_date))
                    $lastSavedDate = $datasPageSaved->page_creation_date;
                else
                    $lastSavedDate = $datasPageSaved->page_edit_date;
                $lastSavedDate = strftime($melisTranslation->getDateFormatByLocate($locale), strtotime($lastSavedDate));
                if (!empty($datasPageSaved->page_last_user_id))
                {
                    $melisCoreTableUser = $this->getServiceLocator()->get('MelisCoreTableUser');
                    $user = $melisCoreTableUser->getEntryById($datasPageSaved->page_last_user_id);
                    if (!empty($user))
                    {
                        $user = $user->current();
                        if (!empty($user))
                            $savedName = $user->usr_firstname . ' ' . $user->usr_lastname;
                    }
                }
            }
        }

        // Page Status
        $published = $translator->translate('tr_meliscms_page_status_last_unpublished_on');

        // Checking Page Current Status
        $melisEngineTablePagePublished = $this->serviceLocator->get('MelisEngineTablePagePublished');
        $datasPagePublished = $melisEngineTablePagePublished->getEntryById($idPage);
        if (!empty($datasPagePublished))
        {
            $datasPagePublished = $datasPagePublished->current();
            if (!empty($datasPagePublished))
            {
                if ($datasPagePublished->page_status == 1)
                    $published = $translator->translate('tr_meliscms_page_status_last_published_on');
            }
        }

        // listener trigger for developers who wants to add more page info
        $infoText = array();
        $response = $this->getEventManager()->trigger('melis_cms_page_head_text', $this, array('idPage' => (int) $idPage));
        foreach($response as $resp) {
            if($resp) {
                if(isset($resp['idPage']) && (
                        (int) $idPage == $resp['idPage']
                    )) {
                    $infoText[] = $resp['text'];
                }
            }
        }

        // trigger to exclude page header buttons
        $excludedButtons = array();
        $response        = $this->getEventManager()->trigger('melis_cms_page_action_buttons_alter', $this, array('idPage' => (int) $idPage));
        foreach($response as $resp) {
            if($resp) {
                if(isset($resp['idPage']) && (
                        (int) $idPage == $resp['idPage']
                    )) {
                    $excludedButtons = $resp['exclude'];
                }
            }
        }

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;
        $view->datas = $datasPageTree;
        $view->lastPublishedDate = $lastPublishedDate;
        $view->publishedName = $publishedName;
        $view->lastSavedDate = $lastSavedDate;
        $view->savedName = $savedName;
        // Page Status
        $view->pageCurrentStatus = $published;
        $view->infoText = $infoText;
        $view->exclude  = $excludedButtons;

        return $view;
    }

    /**
     * Renders the actions buttons zone (Save/Publish/...)
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $page_tpl_id = null;

        $melisPage = $this->serviceLocator->get('MelisEnginePage');
        if(!empty($idPage)){
            $datasPage = $melisPage->getDatasPage($idPage, 'saved');
            $datasPageTree = $datasPage->getMelisPageTree();
            $page_tpl_id = $datasPageTree->page_tpl_id;
        }

        $excludedButtons = array();
        $response        = $this->getEventManager()->trigger('melis_cms_page_action_buttons_alter', $this, array('idPage' => (int) $idPage));
        foreach($response as $resp) {
            if($resp) {
                if(isset($resp['idPage']) && (
                        (int) $idPage == $resp['idPage']
                    )) {
                    $excludedButtons = $resp['exclude'];
                }
            }
        }

        $view 			   = new ViewModel();
        $view->idPage      = $idPage;
        $view->melisKey    = $melisKey;
        $view->page_tpl_id = $page_tpl_id;
        $view->exclude     = $excludedButtons;
        return $view;
    }

    /**
     * Renders the actions buttons New
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionNewAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Renders the actions buttons Delete
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionDeleteAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $melisTree = $this->serviceLocator->get('MelisEngineTree');
        $children = $melisTree->getPageChildren($idPage)->count();
        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;
        $view->children = $children;
        return $view;
    }

    /**
     * Renders the action button Save
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionSaveAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $idFatherPage = $this->params()->fromRoute('idFatherPage', $this->params()->fromQuery('idFatherPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->idFatherPage = $idFatherPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Renders the actions buttons Clear
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionClearAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $melisEngineSavedPage = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
        $savedPageRes = $melisEngineSavedPage->getEntryById($idPage);
        $savedPageData = $savedPageRes->current();

        // Checking Page Current Status
        $melisEngineTablePagePublished = $this->serviceLocator->get('MelisEngineTablePagePublished');
        $datasPagePublished = $melisEngineTablePagePublished->getEntryById($idPage);
        $pagePublishedData = $datasPagePublished->current();

        $confirmMsg = '';
        if (!empty($pagePublishedData))
        {
            // Delete Saved version and use published version
            $confirmMsg = $translator->translate('tr_meliscms_delete_saved_use_publish_version_confirmation_msg');
        }
        else
        {
            // Revert template default datas
            $confirmMsg = $translator->translate('tr_meliscms_delete_saved_use_tpl_default_confirmation_msg');
        }

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;
        $view->confirmMsg = $confirmMsg;
        $view->hasSavedVersion = ($savedPageData) ? true : false;
        return $view;
    }

    /**
     * Renders the action button Publish
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionPublishAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Renders the action button Create
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionCreateAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Renders the action button Unublish
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionUnpublishAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Renders the action button Unublish
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionPublishunpublishAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $published = 0;
        $melisEngineTablePagePublished = $this->serviceLocator->get('MelisEngineTablePagePublished');
        $datasPagePublished = $melisEngineTablePagePublished->getEntryById($idPage);
        if (!empty($datasPagePublished))
        {
            $datasPagePublished = $datasPagePublished->current();
            if (!empty($datasPagePublished))
            {
                if ($datasPagePublished->page_status == 1)
                    $published = 1;
            }
        }

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->currentStatus = $published;
        $view->melisKey = $melisKey;

        return $view;
    }


    /**
     * Renders the action button Notes
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionNotesAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }


    /**
     * Renders the action button View button
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionViewAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $melisTree = $this->serviceLocator->get('MelisEngineTree');
        $link = $melisTree->getPageLink($idPage, true);

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;
        $view->link = $link;

        return $view;
    }

    /**
     * Renders the action button Preview
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionPreviewAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $melisPage = $this->getServiceLocator()->get('MelisEnginePage');
        $datasPage = $melisPage->getDatasPage($idPage, 'saved');
        if($datasPage)
        {
            $datasPageTree = $datasPage->getMelisPageTree();
            $datasTemplate = $datasPage->getMelisTemplate();
        }

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        if(!empty($datasTemplate))
            $view->namespace = $datasTemplate->tpl_zf2_website_folder;
        else
            $view->namespace = '';

        return $view;
    }


    /**
     * Renders the action button See Online
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionSeeonlineAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $melisTree = $this->serviceLocator->get('MelisEngineTree');
        $link = $melisTree->getPageLink($idPage, true);

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;
        $view->link = $link;

        return $view;
    }


    /**
     * Renders the action button change display
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionDisplayAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Renders the action button change display mobile
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionDisplayMobileAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Renders the action button change display tablet
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionDisplayTabletAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Renders the action button change display desktop
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPageactionDisplayDesktopAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Used to check identity of the users to generate the edition page
     */
    public function checkIdentityAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));

        $melisCoreAuth = $this->serviceLocator->get('MelisCoreAuth');
        $user = $melisCoreAuth->getIdentity();

        if (empty($user))
            die;

        $view = new ViewModel();
        $view->idPage = $idPage;

        return $view;
    }

    /**
     * Main page save action
     * This function is to be called to save the page
     * This is the dispatch event function name you must listen to if you want to add a module
     * going to page tab and get it saved when the "Save" button is pressed.
     * Attaching the dispatch event in Module.php, or event meliscms_page_save_start, of your module
     * with a priority of 100 will made it done before the save function returns the results in Json,
     * allowing you to add your specific datas and errors, even writing the whole operation success as false
     * (be careful with ghost data in db)
     *
     * Implementing your sub-save function using Session Container
     * $container['save-page-tmp']['success'] : full success or failure of the save, including others
     * $container['save-page-tmp']['errors'] : array of errors with keys as error types, or field names in forms
     * $container['save-page-tmp']['datas'] : array of datas you want to send back in the Json, like the last insert id
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function savePageAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $fatherPageId = $this->params()->fromRoute('fatherPageId', $this->params()->fromQuery('fatherPageId', ''));
        $translator = $this->serviceLocator->get('translator');

        $eventDatas = array('idPage' => $idPage);
        if(!empty($fatherPageId)){
            $eventDatas['fatherPageId'] = $fatherPageId;
        }

        $this->getEventManager()->trigger('meliscms_page_save_start', $this, $eventDatas);

        // Get the MelisCms Module session as page is saved in it
        $container = new Container('meliscms');

        $success = 0;
        $errors = array();
        $datas = array();

        if ($idPage){
            $logTypeCode = 'CMS_PAGE_UPDATE';
        }else{
            $logTypeCode = 'CMS_PAGE_ADD';
        }

        // Update from the different save actions done
        if (!empty($container['action-page-tmp']))
        {
            if (!empty($container['action-page-tmp']['success']))
                $success = $container['action-page-tmp']['success'];
            if (!empty($container['action-page-tmp']['errors']))
                $errors = $container['action-page-tmp']['errors'];
            if (!empty($container['action-page-tmp']['datas']))
                $datas = $container['action-page-tmp']['datas'];
        }

        // We unset this as it was used temporarily for saving the informations
        // while the sub-save where beeing executed
        unset($container['action-page-tmp']);

        if ($success == 1 && !empty($datas['idPage']))
            $idPage = $datas['idPage'];

        $textTitle = '';
        $textMessage = '';
        $melisPage = $this->serviceLocator->get('MelisEnginePage');
        $page = $melisPage->getDatasPage($idPage, 'saved');

        $pageName = '';
        if ($page && !empty($page->getMelisPageTree()))
        {
            $page = $page->getMelisPageTree();
            $pageName = $page->page_name;
            $textTitle =  $translator->translate('tr_meliscms_page_success_Page')
                . ': ' . $pageName;
        }
        else
            $textTitle =  $translator->translate('tr_meliscms_page_success_Page') . ': '
                . $translator->translate('tr_meliscms_page_success_Page new');

        $pageMelisKey = 'meliscms_page';
        $melisAppConfig =$this->getServiceLocator()->get('MelisCoreConfig');
        $appsConfig = $melisAppConfig->getItem($pageMelisKey);

        if (!empty($appsConfig['conf']['id']))
            $zoneId = $appsConfig['conf']['id'];
        else
            $zoneId = 'id_meliscms_page';

        $data_icon = '';
        if (!empty($page) && !empty($page->page_type))
        {
            if ($page->page_type == 'PAGE')
                $data_icon = 'fa fa-file-o';
            if ($page->page_type == 'SITE')
                $data_icon = 'fa fa-home';
            if ($page->page_type == 'FOLDER')
                $data_icon = 'fa fa-folder-open-o';
        }
        $datas['item_icon'] = $data_icon;
        $datas['item_name'] = $pageName;

        if (!empty($datas['isNew']) && $datas['isNew'])
            $datas['item_zoneid'] = '0_' . $zoneId;
        else
            $datas['item_zoneid'] = $idPage . '_' . $zoneId;
        $datas['item_melisKey'] = $pageMelisKey;


        $pageTxt = '';
        if($idPage == 0)
        {
            $pageTxt = ' '.$translator->translate('tr_meliscms_page_new_page');
        }
        else
        {
            $pageTxt = ' Page "' . $datas['item_name'] . '"';
        }


        if ($success == 1)
        {
            $textMessage = 'tr_meliscms_page_success_Page saved';
        }
        else
        {
            $textMessage = 'tr_meliscms_page_error_Some errors occured while processing the request.';
        }

        // Add labels of errors
        $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');

        $appConfigForm = $melisMelisCoreConfig->getFormMergedAndOrdered(
            PagePropertiesController::PagePropertiesAppConfigPath,
            'meliscms_page_properties',
            $idPage . '_'
        );

        $appConfigForm = $appConfigForm['elements'];

        foreach ($errors as $keyError => $valueError)
        {
            foreach ($appConfigForm as $keyForm => $valueForm)
            {
                if ($valueForm['spec']['name'] == $keyError &&
                    !empty($valueForm['spec']['options']['label']))
                    $errors[$keyError]['label'] = $valueForm['spec']['options']['label'];
            }
        }


        $response = array(
            'success' => $success,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage,
            'errors' => $errors,
            'datas' => $datas,
        );

        $this->getEventManager()->trigger('meliscms_page_save_end', $this, array_merge($response, array('typeCode' => $logTypeCode, 'itemId' => $idPage)));

        // Final Json sent back
        return new JsonModel($response);

    }

    /**
     * Delete Saved and Session of the current Page
     * @return \Zend\View\Model\JsonModel
     */
    public function clearSavedPageAction(){
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $translator = $this->serviceLocator->get('translator');

        $success = 0;
        $errors = array();
        $datas = array();
        $textTitle = 'tr_meliscms_delete_saved_success_title';
        $textMessage = '';

        $melisEngineSavedPage = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
        $savedPageRes = $melisEngineSavedPage->getEntryById($idPage);
        $savedPageData = $savedPageRes->current();

        $this->getEventManager()->trigger('meliscms_page_clear_saved_page_start', $this, array('idPage' => $idPage));
        if (!empty($savedPageData)){

            // Checking Page Current Status
            $melisEngineTablePagePublished = $this->serviceLocator->get('MelisEngineTablePagePublished');
            $datasPagePublished = $melisEngineTablePagePublished->getEntryById($idPage);
            $pagePublishedData = $datasPagePublished->current();

            if (!empty($pagePublishedData)){
                $melisEngineSavedPage->deleteById($idPage);
            }else{
                // Update Save page Content to default value
                // New page, create an empty content
                $newXmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                $newXmlContent .= '<document type="MelisCMS" author="MelisTechnology" version="2.0">' . "\n";
                $newXmlContent .= '</document>';
                $data['page_content'] = $newXmlContent;
                $data['page_edit_date'] = date('Y-m-d H:i:s');
                // Getting current user
                $melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');
                $user = $melisCoreAuth->getIdentity();
                if (!empty($user))
                {
                    $data['page_last_user_id'] = $user->usr_id;
                }
                $melisEngineSavedPage->save($data, $idPage);
            }

            // Get the MelisCms Module session as page is saved in it
            $container = new Container('meliscms');
            if (!empty($container['content-pages'][$idPage])){
                unset($container['content-pages'][$idPage]);
            }

            $textMessage = 'tr_meliscms_delete_saved_success';
            $success = 1;
        }else{
            $textMessage = 'tr_meliscms_delete_no_saved_page';
        }

        $response = array(
            'success' => $success,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage,
            'errors' => $errors,
        );

        $this->getEventManager()->trigger('meliscms_page_clear_saved_page_end', $this, array_merge($response, array('typeCode' => 'CMS_PAGE_CLEAR', 'itemId' => $idPage)));


        return new JsonModel($response);
    }

    /**
     * Move a saved page to the publiched table and change status to 1
     * @return \Zend\View\Model\JsonModel
     */
    public function publishSavedPageAction()
    {

        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $translator = $this->serviceLocator->get('translator');

        $melisPageSavedTable = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
        $melisPagePublishedTable = $this->getServiceLocator()->get('MelisEngineTablePagePublished');

        $dataSaved = $melisPageSavedTable->getEntryById($idPage);
        if ($dataSaved)
        {
            $dataSaved = $dataSaved->toArray();

            if ($dataSaved)
            {
                $dataSaved = $dataSaved[0];

                $dataSaved['page_status'] = 1;
                $dataSaved['page_edit_date'] = date('Y-m-d H:i:s');

                $idPageTmp = $melisPagePublishedTable->save($dataSaved, $idPage);

                $melisPageSavedTable->deleteById($idPage);
            }

            $result = array(
                'success' => 1,
                'errors' => array(),
            );
        }
        else
        {
            $result = array(
                'success' => 0,
                'errors' => array('empty' => array(
                    'empty' => $translator->translate('tr_meliscms_form_common_errors_Empty datas'),
                    'label' => $translator->translate('tr_meliscms_form_common_errors_Datas'))),
            );
        }
        return new JsonModel($result);
    }

    /**
     * Main publish action on which listeners will be attached.
     * Events: meliscms_page_publish_start / meliscms_page_publish_end
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function publishPageAction()
    {
        $idPage = (int) $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $translator = $this->serviceLocator->get('translator');

        $eventDatas = array('idPage' => $idPage);
        $this->getEventManager()->trigger('meliscms_page_publish_start', $this, $eventDatas);

        // Get the MelisCms Module session as page is saved in it
        $container = new Container('meliscms');

        $success = 0;
        $errors = array();
        $datas = array();

        // Update from the different save actions done
        if (!empty($container['action-page-tmp']))
        {
            if (!empty($container['action-page-tmp']['success']))
                $success = $container['action-page-tmp']['success'];
            if (!empty($container['action-page-tmp']['errors']))
                $errors = $container['action-page-tmp']['errors'];
            if (!empty($container['action-page-tmp']['datas']))
                $datas = $container['action-page-tmp']['datas'];
        }

        // We unset this as it was used temporarily for saving the informations
        // while the sub-save where beeing executed
        unset($container['action-page-tmp']);

        $textMessage = '';

        $melisPage = $this->serviceLocator->get('MelisEnginePage');
        $page = $melisPage->getDatasPage($idPage, 'saved');

        if ($page && !empty($page->getMelisPageTree()))
        {
            $page = $page->getMelisPageTree();
            $pageName = $page->page_name;
        }

        // Add labels of errors
        $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');

        $appConfigForm = $melisMelisCoreConfig->getFormMergedAndOrdered(
            PagePropertiesController::PagePropertiesAppConfigPath,
            'meliscms_page_properties',
            $idPage . '_'
        );

        $appConfigForm = $appConfigForm['elements'];

        foreach ($errors as $keyError => $valueError)
        {
            foreach ($appConfigForm as $keyForm => $valueForm)
            {
                if ($valueForm['spec']['name'] == $keyError &&
                    !empty($valueForm['spec']['options']['label']))
                    $errors[$keyError]['label'] = $valueForm['spec']['options']['label'];
            }
        }

        if(!empty($errors)) {
            $success = 0;
        }

        $pageMelisKey = 'meliscms_page';
        $melisAppConfig =$this->getServiceLocator()->get('MelisCoreConfig');
        $appsConfig = $melisAppConfig->getItem($pageMelisKey);

        if (!empty($appsConfig['conf']['id']))
            $zoneId = $appsConfig['conf']['id'];
        else
            $zoneId = 'id_meliscms_page';

        $data_icon = '';
        if (!empty($page) && !empty($page->page_type))
        {
            if ($page->page_type == 'PAGE')
                $data_icon = 'fa fa-file-o';
            if ($page->page_type == 'SITE')
                $data_icon = 'fa fa-home';
            if ($page->page_type == 'FOLDER')
                $data_icon = 'fa fa-folder-open-o';
        }
        $datas['item_icon'] = $data_icon;
        $datas['item_name'] = $pageName;


        $pageTxt = '"' . $datas['item_name'] . '"';
        if ($success == 1) {
            $textMessage = 'tr_meliscms_page_success_Page published';
        }
        else {
            $textMessage = 'tr_meliscms_page_error_Some errors occured while processing the request. Please find details bellow.';
        }

        if (!empty($datas['isNew']) && $datas['isNew'])
            $datas['item_zoneid'] = '0_' . $zoneId;
        else
            $datas['item_zoneid'] = $idPage . '_' . $zoneId;
        $datas['item_melisKey'] = $pageMelisKey;

        $response = array(
            'success' => $success,
            'textTitle' => $translator->translate('tr_meliscms_page_actions_Publish').' Page ' . $pageTxt,
            'textMessage' => $textMessage,
            'errors' => $errors,
            'datas' => $datas,
        );

        $this->getEventManager()->trigger('meliscms_page_publish_end', $this, array_merge($response, array('typeCode' => 'CMS_PAGE_PUBLISH', 'itemId' => $idPage)));


        // Final Json sent back
        return new JsonModel($response);

    }

    /**
     * Unpublish action
     * @return \Zend\View\Model\JsonModel
     */
    public function unpublishPublishedPageAction()
    {
        $idPage = (int) $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $translator = $this->getServiceLocator()->get('translator');
        $melisPagePublishedTable = $this->getServiceLocator()->get('MelisEngineTablePagePublished');

        $dataPublished = $melisPagePublishedTable->getEntryById($idPage);
        if ($dataPublished)
        {
            $dataPublished = $dataPublished->toArray();
            if (count($dataPublished) > 0)
            {

                $melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');

                $userAuthDatas =  $melisCoreAuth->getStorage()->read();
                $userId = (int) $userAuthDatas->usr_id;

                $melisPagePublishedTable->save(array(
                    'page_status' => 0,
                    'page_edit_date' => date('Y-m-d H:i:s'),
                    'page_last_user_id' => $userId
                ), $idPage);

                $result = array(
                    'success' => 1,
                    'datas' => array('idPage' => $idPage, 'isNew' => 0),
                    'errors' => array(),

                );
            }
            else
            {
                $result = array(
                    'success' => 0,
                    'datas' => array('idPage' => $idPage, 'isNew' => 0),
                    'errors' => array('empty' => array(
                        'empty' => $translator->translate('tr_meliscms_form_common_errors_Empty datas'),
                        'label' => $translator->translate('tr_meliscms_form_common_errors_Datas'))),
                );
            }
        }
        else
        {
            $result = array(
                'success' => 0,
                'datas' => array('idPage' => $idPage, 'isNew' => 0),
                'errors' => array('empty' => array(
                    'empty' => $translator->translate('tr_meliscms_form_common_errors_Empty datas'),
                    'label' => $translator->translate('tr_meliscms_form_common_errors_Datas'))),
            );
        }

        return new JsonModel($result);
    }

    /**
     * Main unpublish action which is to be hooked up for adding actions.
     * Events: meliscms_page_unpublish_start / meliscms_page_unpublish_end
     * @return \Zend\View\Model\JsonModel
     */
    public function unpublishPageAction()
    {
        $idPage = (int) $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $translator = $this->serviceLocator->get('translator');

        $eventDatas = array('idPage' => $idPage);
        $this->getEventManager()->trigger('meliscms_page_unpublish_start', $this, $eventDatas);


        // Get the MelisCms Module session as page is saved in it
        $container = new Container('meliscms');

        $success = 0;
        $errors = array();
        $datas = array();

        // Update from the different save actions done
        if (!empty($container['action-page-tmp']))
        {
            if (!empty($container['action-page-tmp']['success']))
                $success = $container['action-page-tmp']['success'];
            if (!empty($container['action-page-tmp']['errors']))
                $errors = $container['action-page-tmp']['errors'];
            if (!empty($container['action-page-tmp']['datas']))
                $datas = $container['action-page-tmp']['datas'];
        }

        // We unset this as it was used temporarily for saving the informations
        // while the sub-save where beeing executed
        unset($container['action-page-tmp']);

        $textMessage = '';
        $melisPage = $this->serviceLocator->get('MelisEnginePage');
        $page = $melisPage->getDatasPage($idPage, 'saved');


        if ($page && !empty($page->getMelisPageTree()))
        {
            $page = $page->getMelisPageTree();
            $pageName = $page->page_name;
            $textTitle =  $translator->translate('tr_meliscms_page_success_Page') . ': ' . $pageName;
        }
        if ($success == 1) {
            $textMessage = $translator->translate('tr_meliscms_page_success_Page unpublished');
        }
        else {
            $textMessage = $translator->translate('tr_meliscms_page_error_Some errors occured while processing the request. Please find details bellow.');
        }

        $datas['idPage'] = $idPage;
        $datas['isNew']  = 0;

        $response = array(
            'success' => $success,
            'textTitle' => $translator->translate('tr_meliscms_page_actions_Unpublish').' Page ' . $idPage,
            'textMessage' => $textMessage,
            'errors' => $errors,
            'datas' => array($datas),
        );

        $this->getEventManager()->trigger('meliscms_page_unpublish_end', $this, array_merge($response, array('typeCode' => 'CMS_PAGE_UNPUBLISH', 'itemId' => $idPage)));

        // Final Json sent back
        return new JsonModel($response);
    }

    /**
     * Delete a page action
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function deletePageTreeAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $translator = $this->serviceLocator->get('translator');
        $datasPage = null;

        $datas = array('idPage' => $idPage);
        $this->getEventManager()->trigger('meliscms_page_delete_page_start', $this, $datas);
        // Check if there's an id of page given
        if (empty($idPage))
        {
            $result = array(
                'success' => 0,
                'datas' => array($datas),
                'textTitle' => 'textTitle',
                'textMessage' => 'textMessage',
                'errors' => array('noid' => array(
                    'noid' => $translator->translate('tr_meliscms_page_error_No id found'),
                    'label' => $translator->translate('tr_meliscms_page_error_No id'))),
            );
        }
        else
        {
            // Check page exists for real
            $melisPage = $this->serviceLocator->get('MelisEnginePage');
            $page = $melisPage->getDatasPage($idPage, 'saved');

            if (empty($page->getMelisPageTree()))
            {
                $result = array(
                    'success' => 0,
                    'datas' => array($datas),
                    'textTitle' => 'textTitle',
                    'textMessage' => 'textMessage',
                    'errors' => array('noid' => array(
                        'noid' => $translator->translate('tr_meliscms_page_error_Page doesn\'t exist'),
                        'label' => $translator->translate('tr_meliscms_page_error_No id'))),
                );
            }
            else
            {
                // Get the children
                $melisTree = $this->serviceLocator->get('MelisEngineTree');
                $children = $melisTree->getPageChildren($idPage);
                $children = $children->toArray();
                $datasPage = $page->getMelisPageTree();

                if (count($children) == 0)
                {
                    $pageOrder =  $datasPage->tree_page_order;
                    $fatherPageId = $datasPage->tree_father_page_id;

                    // Get the children for update after deleting
                    $children = $melisTree->getPageChildren($fatherPageId);
                    $children = $children->toArray();

                    // Deleting the page
                    $tablePageTree = $this->getServiceLocator()->get('MelisEngineTablePageTree');
                    $tablePageTree->deleteById($idPage);

                    $tablePagePublished = $this->getServiceLocator()->get('MelisEngineTablePagePublished');
                    $tablePagePublished->deleteById($idPage);

                    $tablePageSaved = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
                    $tablePageSaved->deleteById($idPage);

                    $tablePageLang = $this->getServiceLocator()->get('MelisEngineTablePageLang');
                    $tablePageLang->deleteByField('plang_page_id', $idPage);


                    // updating pages order after deleting
                    $cpt = 1;
                    foreach ($children as $child)
                    {
                        if ($child['tree_page_id'] == $idPage)
                            continue;

                        $tablePageTree->save(array('tree_page_order' => $cpt), $child['tree_page_id']);
                        $cpt++;
                    }

                    $result = array(
                        'success' => 1,
                        'datas' => array($datas),
                        'textTitle' => 'textTitle',
                        'textMessage' => 'textMessage',
                        'errors' => array(),
                    );
                }
                else
                {
                    $result = array(
                        'success' => 0,
                        'datas' => array($datas),
                        'textTitle' => 'textTitle',
                        'textMessage' => 'textMessage',
                        'errors' => array('children' => array(
                            'children' => $translator->translate('tr_meliscms_page_error_You cannot delete a page having children pages'),
                            'label' => $translator->translate('tr_meliscms_page_error_Children pages'))),
                    );
                }
            }

        }

        $pageName = '';
        if (!empty($datasPage))
            $pageName = $datasPage->page_name;
        $textTitle = $translator->translate('tr_meliscms_page_success_Page') . ': ' . $pageName;
        if ($result['success'] == 1)
            $textMessage = $translator->translate('tr_meliscms_page_success_Page deleted');
        else
            $textMessage = $translator->translate('tr_meliscms_page_error_Some errors occured while processing the request. Please find details bellow.');

        $result['textTitle'] = $textTitle;
        $result['textMessage'] = $textMessage;

        $this->getEventManager()->trigger('meliscms_page_delete_page_end', $this, $result);

        return new JsonModel($result);
    }

    /**
     * Main delete page function
     * Events: meliscms_page_delete_start / meliscms_page_delete_end
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function deletePageAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $translator = $this->serviceLocator->get('translator');

        $eventDatas = array('idPage' => $idPage);
        $this->getEventManager()->trigger('meliscms_page_delete_start', $this, $eventDatas);

        // Get the MelisCms Module session as page is saved in it
        $container = new Container('meliscms');

        $success = 0;
        $errors = array();
        $datas = array();

        // Update from the different save actions done
        if (!empty($container['action-page-tmp']))
        {
            if (!empty($container['action-page-tmp']['success']))
                $success = $container['action-page-tmp']['success'];
            if (!empty($container['action-page-tmp']['errors']))
                $errors = $container['action-page-tmp']['errors'];
            if (!empty($container['action-page-tmp']['datas']))
                $datas = $container['action-page-tmp']['datas'];
        }

        // We unset this as it was used temporarily for saving the informations
        // while the sub-save where beeing executed
        unset($container['action-page-tmp']);

        $textTitle = '';
        $textMessage = '';
        $melisPage = $this->serviceLocator->get('MelisEnginePage');
        $page = $melisPage->getDatasPage($idPage, 'saved');
        $datas['idPage'] = $idPage;

        if ($page && !empty($page->getMelisPageTree()))
        {
            $page = $page->getMelisPageTree();
            $pageName = $page->page_name;
            $textTitle =  $translator->translate('tr_meliscms_page_success_Page delete') . ': ' . $pageName;
        }
        if ($success == 1) {
            $textTitle = ' Page ' . $idPage. ' '.$translator->translate('tr_meliscms_page_success_Page_deleted');
            $textMessage = 'tr_meliscms_page_success_Page deleted_success';
        }
        else {
            $textTitle = $translator->translate('tr_meliscms_page_success_Page_deleted2').' Page ' . $idPage;
            $textMessage = 'tr_meliscms_page_error_Some errors occured while processing the request. Please find details bellow.';
        }

        $response = array(
            'success' => $success,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage,
            'errors' => $errors,
            'datas' => array($datas),
        );

        $this->getEventManager()->trigger('meliscms_page_delete_end', $this, array_merge($response, array('typeCode' => 'CMS_PAGE_DELETE', 'itemId' => $idPage)));

        // Final Json sent back
        return new JsonModel($response);
    }

    /**
     * This function moves a page in the treeview
     * Events: meliscms_page_move_start / meliscms_page_move_end
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function movePageAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $oldFatherIdPage = $this->params()->fromRoute('oldFatherIdPage', $this->params()->fromQuery('oldFatherIdPage', ''));
        $newFatherIdPage = $this->params()->fromRoute('newFatherIdPage', $this->params()->fromQuery('newFatherIdPage', ''));
        $newPositionIdPage = $this->params()->fromRoute('newPositionIdPage', $this->params()->fromQuery('newPositionIdPage', ''));
        $translator = $this->serviceLocator->get('translator');

        $eventDatas = array(
            'idPage' => $idPage,
            'oldFatherIdPage' => $oldFatherIdPage,
            'newFatherIdPage' => $newFatherIdPage,
            'newPositionIdPage' => $newPositionIdPage,
        );
        $this->getEventManager()->trigger('meliscms_page_move_start', $this, $eventDatas);

        $success = 1;

        // First let's get the list of children of the new father's page
        $melisTree = $this->serviceLocator->get('MelisEngineTree');
        $children = $melisTree->getPageChildren($newFatherIdPage);
        $children = $children->toArray();


        $pageTree = array();
        foreach ($children As $val)
        {
            if ($val['tree_page_id'] != $idPage)
            {
                array_push($pageTree, $val['tree_page_id']);
            }
        }

        // Inserting the page id with new order
        array_splice($pageTree, ($newPositionIdPage - 1), 0, $idPage);

        $tablePageTree = $this->getServiceLocator()->get('MelisEngineTablePageTree');
        foreach ($pageTree As $key => $val)
        {
            $tablePageTree->save(array(
                'tree_father_page_id' => $newFatherIdPage,
                'tree_page_order' => ($key + 1)
            ), $val);
        }

        if ($newFatherIdPage != $oldFatherIdPage)
        {
            // Now we update the children of the old father page id, for their page order now that
            // the page has been deleted
            $melisTree = $this->serviceLocator->get('MelisEngineTree');
            $children = $melisTree->getPageChildren($oldFatherIdPage);
            $children = $children->toArray();
            $cpt = 1;
            foreach ($children as $child)
            {
                $tablePageTree->save(array('tree_page_order' => $cpt), $child['tree_page_id']);
                $cpt++;
            }
        }

        $textTitle = '';
        $textMessage = '';
        if ($success == 1)
        {
            $melisPage = $this->serviceLocator->get('MelisEnginePage');
            $page = $melisPage->getDatasPage($idPage, 'saved');

            if ($page && !empty($page->getMelisPageTree()))
            {
                $page = $page->getMelisPageTree();
                $pageName = $page->page_name;
                $textTitle =  $translator->translate('tr_meliscms_page_success_Page'). ': ' . $pageName;
                $textMessage = 'tr_meliscms_page_success_Page moved';
            }
        }

        $response = array(
            'success' => $success,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage,
            'errors' => array(''),
        );

        $this->getEventManager()->trigger('meliscms_page_move_end', $this, array_merge($response, array('typeCode' => 'CMS_PAGE_MOVE', 'itemId' => $idPage)));

        return new JsonModel($response);
    }

    /**
     * This function returns whether or not a user has access to the "save", "delete", "publish", "unpublish"
     * action buttons in the interface. Allowing to expand the rights to php saving functions, and updating
     * treeview right menu
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function isActionActiveAction()
    {
        $actionwanted = $this->params()->fromQuery('actionwanted', '');

        $melisCmsRights = $this->getServiceLocator()->get('MelisCmsRights');
        $active = $melisCmsRights->isActionButtonActive($actionwanted);

        $success = 1;
        $result = array(
            'success' => $success,
            'active' => $active
        );

        return new JsonModel($result);
    }

    /**
     * Checks if an action (save/publish) is allowed by using the availability of
     * buttons (save/etc)
     * @return \Zend\View\Model\JsonModel
     */
    public function pageActionsRightCheckAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $actionwanted = $this->params()->fromRoute('actionwanted', $this->params()->fromQuery('actionwanted', ''));
        $translator = $this->serviceLocator->get('translator');

        $melisCmsRights = $this->getServiceLocator()->get('MelisCmsRights');
        $active = $melisCmsRights->isActionButtonActive($actionwanted);

        $textTitle = '';
        $textMessage = '';
        if ($active == 0)
        {
            $melisPage = $this->serviceLocator->get('MelisEnginePage');
            $page = $melisPage->getDatasPage($idPage, 'saved');

            if ($page && !empty($page->getMelisPageTree()))
            {
                $page = $page->getMelisPageTree();
                $pageName = $page->page_name;
                $textTitle =  $translator->translate('tr_meliscms_page_success_Page')
                    . ': ' . $pageName;
                $errorMessage = $translator->translate('tr_meliscms_page_error_Your rights don\'t allow you to modify this page');
            }
            else
            {
                $textTitle =  $translator->translate('tr_meliscms_page_success_Page') . ': ' . $idPage;
                $errorMessage = $translator->translate('tr_meliscms_page_error_Your rights don\'t allow you to modify this page');
            }

            $result = array(
                'success' => 0,
                'textTitle' => '',
                'textMessage' => '',
                'errors' => array(array('rights' => $errorMessage, 'label' => $translator->translate('tr_meliscms_page_error_lebel_Rights'))),
            );
        }
        else
        {
            $result = array(
                'success' => 1,
                'textTitle' => '',
                'textMessage' => '',
                'errors' => array(),
            );
        }

        return new JsonModel($result);
    }

    public function renderPageModalAction()
    {
        $id = $this->params()->fromQuery('id');
        $view = new ViewModel();
        $melisKey = $this->params()->fromQuery('melisKey', '');
        $view->melisKey = $melisKey;
        $view->id = $id;
        $view->setTerminal(true);
        return $view;
    }

    public function renderPageTreeModalAction()
    {
        $view = new ViewModel();
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $view->melisKey = $melisKey;
        return $view;
    }
    
    public function renderPageTreeIdSelectorModalAction()
    {
        $view = new ViewModel();
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $view->melisKey = $melisKey;
        return $view;
    }

    public function renderInputTreeModalAction()
    {
        $id = $this->params()->fromQuery('pageTreeInputId');
        $view = new ViewModel();
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $view->melisKey = $melisKey;
        $view->pageTreeInputId = $id;
        return $view;
    }

    public function searchTreePagesAction()
    {
        $result = array();
        $pageSvc = $this->getServiceLocator()->get('MelisEnginePage');
        $treeSvc = $this->getServiceLocator()->get('MelisEngineTree');

        if($this->getRequest()->isPost()) {
            $postValues = get_object_vars($this->getRequest()->getPost());

            $publishedPages = $pageSvc->searchPage($postValues['value'], 'published');
            $savedPages = $pageSvc->searchPage($postValues['value'], 'saved');

            $pages = array_merge($publishedPages, $savedPages);

            foreach($pages as $page){

                $tmp = '';
                $tmp = $page->tree_page_id;
                $pageId = $page->tree_page_id;
                $fatherPage = $treeSvc->getPageFather($pageId)->toArray();

                while(!empty($fatherPage)){

                    if(!empty($fatherPage[0]['tree_page_id'])){
                        $tmp = $fatherPage[0]['tree_father_page_id']. '/'. $tmp;

                        $fatherPage = $treeSvc->getPageFather($fatherPage[0]['tree_father_page_id'])->toArray();

                    }else{
                        break;
                    }

                }

                $result[] = $tmp;
            }
            $result = array_unique($result);
            sort($result, SORT_STRING | SORT_FLAG_CASE | SORT_NATURAL);
        }

        return new JsonModel($result);
    }

    public function getPageLinkAction()
    {
        $link = array();
        $idPage = $this->params()->fromQuery('idPage', '');
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $melisTree = $this->serviceLocator->get('MelisEngineTree');
        $link['link'] = $melisTree->getPageLink($idPage);

        return new JsonModel($link);
    }

    private function updateUrlPage($idPage)
    {
        $melisTree = $this->serviceLocator->get('MelisEngineTree');
        $tablePageDefaultUrls = $this->getServiceLocator()->get('MelisEngineTablePageDefaultUrls');

        $link = $melisTree->getPageLink($idPage);
        $tablePageDefaultUrls->save(
            array(
                'purl_page_id' => $idPage,
                'purl_page_url' => $link
            ),
            $idPage
        );

        if ($link != '/') // No need to update if it's homepage, it doesn't change the link
        {
            $childrenPages = $melisTree->getPageChildren($idPage, 0);
            foreach($childrenPages as $page)
            {
                $this->updateUrlPage($page->tree_page_id);
            }
        }
    }

    public function updateDefaultUrlsAction()
    {
        $idPage = $this->params()->fromQuery('idPage', '');
        if($idPage) {
            $this->updateUrlPage($idPage);
        }
    }
}
