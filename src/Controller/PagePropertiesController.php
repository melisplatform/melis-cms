<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\Form\Factory;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

/**
 * This class renders Melis CMS Page tab properties
 */
class PagePropertiesController extends AbstractActionController
{
    // The form is loaded from the app.form array
    const PagePropertiesAppConfigPath = '/meliscms/forms/meliscms_page_properties';

    /**
     * Makes the rendering of the Page Properties Tab
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPagetabPropertiesAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $pageStyle = null;

        /**
         * Get the config for this form
         */
        $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
        $pageStyleTable = $this->getServiceLocator()->get('MelisEngineTablePageStyle');

        $appConfigForm = $melisMelisCoreConfig->getFormMergedAndOrdered(
            self::PagePropertiesAppConfigPath,
            'meliscms_page_properties',
            $idPage . '_'
        );

        /** Overriding the Page properties form by calling listener */
        $modifiedForm = $this->getEventManager()->trigger(
            'modify_page_properties_form_config',
            $this,
            ['appConfigForm' => $appConfigForm]
        );

        /** Override appConfigForm with the modified value from the last-touch listener */
        $appConfigForm = empty($modifiedForm->last()) ? $appConfigForm : $modifiedForm->last();

        if (!empty($idPage)) {
            // Lang not changeable after creation, config array defines the form in "new" state
            $appConfigForm = $melisMelisCoreConfig->setFormFieldDisabled($appConfigForm, 'plang_lang_id', true);
            $appConfigForm = $melisMelisCoreConfig->setFormFieldRequired($appConfigForm, 'plang_lang_id', false);
        }

        /**
         * Get the data to fill the form
         */
        if (!empty($idPage)) {
            $melisPage = $this->serviceLocator->get('MelisEnginePage');
            $datasPage = $melisPage->getDatasPage($idPage, 'saved');
            $datasPageTree = $datasPage->getMelisPageTree();
            $pageStyle = $pageStyleTable->getEntryByField('pstyle_page_id', $idPage)->current();
        } else
            $datasPageTree = null;

        /**
         * Generate the form through factory and change ElementManager to
         * have access to our custom Melis Elements
         * Bind with datas
         */
        $factory = new Factory();
        $formElements = $this->serviceLocator->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $appConfigForm['attributes']['action'] .= '?idPage=' . $idPage;
        $propertyForm = $factory->createForm($appConfigForm);

        // service for handling Melis Multi Value Input (tags)
        if (!empty($datasPageTree)) {
            // modify the values of the dates to render to its locale
            $container = new Container('meliscore');
            $locale = $container['melis-lang-locale'];
            $melisTranslation = $this->getServiceLocator()->get('MelisCoreTranslation');

            $datasPageTree->page_creation_date = strftime($melisTranslation->getDateFormatByLocate($locale), strtotime($datasPageTree->page_creation_date));
            $datasPageTree->page_edit_date = strftime($melisTranslation->getDateFormatByLocate($locale), strtotime($datasPageTree->page_edit_date));
            $propertyForm->bind($datasPageTree);
        }

        if (!empty($pageStyle)) {
            $propertyForm->setData(array('style_id' => $pageStyle->pstyle_style_id));
        }

        /**
         * Send back the view and add the form config inside
         */
        $view = new ViewModel();
        $view->setVariable('meliscms_page_properties', $propertyForm);
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * This function saves the entry in PageTree if needed
     * It is not the page entry, but as no page entry will be saved if the pagetree entry doesn't exist,
     * this action is closely linked to the PageProperties.
     * It is even checking the properties datas to be saved before saving pagetree
     *
     * SavePageTree uses melis_platform table to fix the id of pages using
     * bands of int defined by platform environment variable
     *
     * Events: meliscms_page_savetree_start / meliscms_page_savetree_end
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function savePageTreeAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $fatherPageId = $this->params()->fromRoute('fatherPageId', $this->params()->fromQuery('fatherPageId', -1));
        $translator = $this->serviceLocator->get('translator');

        $eventDatas = array('idPage' => $idPage, 'fatherPageId' => $fatherPageId);
        $this->getEventManager()->trigger('meliscms_page_savetree_start', null, $eventDatas);

        $melisEngineTablePageTree = $this->getServiceLocator()->get('MelisEngineTablePageTree');

        // Check if the page really exist to avoid ghost datas
        $exist = false;
        if (!empty($idPage)) {
            $datasPageTree = $melisEngineTablePageTree->getEntryById($idPage);
            if ($datasPageTree) {
                $datasPageTree = $datasPageTree->toArray();
                if (count($datasPageTree) > 0)
                    $exist = true;
            }
        }


        /**
         * Get the form properly loaded
         * The page cannot be saved in page tree if at least the properties
         * are not filled correctly.
         */
        $errors = array();
        $propertyForm = $this->getPropertyPageForm($idPage, !$exist);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postValues = $this->params()->fromRoute();
            if (!isset($postValues['page_duplicate_event_flag'])) {
                $postValues = get_object_vars($request->getPost());
            }

            $propertyForm->setData($postValues);

            if ($exist) {
                // Set Page lang id not required
                $propertyForm->getInputFilter()->get('plang_lang_id')->setRequired(false);
            }

            if ($propertyForm->isValid()) {
                /**
                 * Additional Form Validation
                 *  - Raise error for the Selected language, if not active for the Site (via template)
                 */
                $formData = $propertyForm->getData();

                if ($formData['page_tpl_id'] !== '-1') {
                    $siteLangs = $this->getSiteLanguages($formData['page_tpl_id']);

                    // Page language
                    $pageLangId = $formData['plang_lang_id'];
                    if (empty($pageLangId))
                        $pageLangId = $this->getServiceLocator()->get('MelisEngineTablePageLang')
                            ->getEntryById($idPage)->current()->plang_lang_id;

                    if (!in_array($pageLangId, $siteLangs)) {
                        return new JsonModel([
                            'success' => 0,
                            'datas' => [],
                            'errors' => [
                                [
                                    'plang_lang_id' => [
                                        'errorMessage' => $translator->translate('tr_meliscms_page_form_page_p_lang_ko'),
                                        'label' => $translator->translate('tr_meliscms_page_tab_properties_form_Language')
                                    ]
                                ]
                            ]
                        ]);
                    }
                }

                /**
                 * If page does not exist, let's create
                 * If page exist, nothing to do, properties are saved in save-page-properties, handled through event
                 */
                if (!$exist) {
                    // Get the order to be inserted
                    $order = 0;
                    $children = $melisEngineTablePageTree->getPageChildrenByidPage($fatherPageId);
                    if ($children) {
                        $children = $children->toArray();
                        $order = count($children) + 1;
                    }

                    // Get the current page id from platform table
                    $melisModuleName = getenv('MELIS_PLATFORM');
                    $melisEngineTablePlatformIds = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
                    $datasPlatformIds = $melisEngineTablePlatformIds->getPlatformIdsByPlatformName($melisModuleName);
                    $datasPlatformIds = $datasPlatformIds->current();

                    if (!empty($datasPlatformIds)) {

                        /**
                         * Check if we reached the end of the band page ids defined in platform
                         */
                        if ($datasPlatformIds->pids_page_id_current >= $datasPlatformIds->pids_page_id_end) {
                            return new JsonModel(array(
                                'success' => 0,
                                'datas' => array(),
                                'errors' => array(array($translator->translate('tr_meliscms_page_save_error_platform_id_max') => $translator->translate('tr_meliscms_page_save_error_Current page id has reached end of platform band')))
                            ));
                        }

                        // Try/catch to save in case of pagetree id error
                        try {
                            // Save the new entry in page_tree
                            $melisEngineTablePageTree->save(array(
                                'tree_page_id' => $datasPlatformIds->pids_page_id_current,
                                'tree_father_page_id' => $fatherPageId,
                                'tree_page_order' => $order,
                            ));
                            $idPage = $datasPlatformIds->pids_page_id_current;
                        } catch (\Exception $e) {
                            return new JsonModel(array(
                                'success' => 0,
                                'datas' => array(),
                                'errors' => array(array($translator->translate('tr_meliscms_page_save_error_platform_id_used') => $translator->translate('tr_meliscms_page_save_error_Current page id defined in platform is already used')))
                            ));
                        }

                        // save page lang relation
                        $langId = 1;        // default
                        $initial = 0;        // to be changed when functionality create page version lang done, this will set to plang_page_id_initial

                        // Get langId from posted values or put default 1
                        $request = $this->getRequest();
                        if ($request->isPost()) {
                            if (!empty($postValues['plang_lang_id']))
                                $langId = $postValues['plang_lang_id'];
                        }

                        $melisEngineTablePageLang = $this->getServiceLocator()->get('MelisEngineTablePageLang');
                        $melisEngineTablePageLang->save(array(
                            'plang_page_id' => $datasPlatformIds->pids_page_id_current,
                            'plang_lang_id' => $langId,
                            'plang_page_id_initial' => $datasPlatformIds->pids_page_id_current,//$initial,
                        ));

                        // Update current page id in platform
                        $melisEngineTablePlatformIds->save(array(
                            'pids_page_id_current' => $datasPlatformIds->pids_page_id_current + 1,
                        ), $datasPlatformIds->pids_id);
                    }
                }
            } else {
                /**
                 * Form is not valid
                 * Error Messages will generated by saveProperties function
                 */
            }
        }

        $result = array(
            'success' => 1,
            'datas' => array(
                'idPage' => $idPage,
                'isNew' => !$exist,
            ),
            'errors' => array()
        );

        $this->getEventManager()->trigger('meliscms_page_savetree_end', null, $result);

        return new JsonModel($result);
    }

    /**
     * Returns the Page Property Form
     *
     * @param $idPage
     * @param $isNew
     * @return \Zend\Form\ElementInterface
     */
    public function getPropertyPageForm($idPage, $isNew)
    {
        /**
         * Get the config for this form
         */
        $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');

        $appConfigForm = $melisMelisCoreConfig->getFormMergedAndOrdered(
            self::PagePropertiesAppConfigPath,
            'meliscms_page_properties',
            $idPage . '_'
        );

        /** Overriding the Page properties form by calling listener */
        $modifiedForm = $this->getEventManager()->trigger(
            'modify_page_properties_form_config',
            $this,
            ['appConfigForm' => $appConfigForm]
        );

        /** Override appConfigForm with the modified value from the last-touch listener */
        $appConfigForm = empty($modifiedForm->last()) ? $appConfigForm : $modifiedForm->last();

        if ($isNew == false) {
            // Lang not changeable after creation
            $appConfigForm = $melisMelisCoreConfig->setFormFieldDisabled($appConfigForm, 'plang_lang_id', true);
            $appConfigForm = $melisMelisCoreConfig->setFormFieldRequired($appConfigForm, 'plang_lang_id', false);
        }

        /**
         * Generate the form through factory and change ElementManager to
         * have access to our custom Melis Elements
         */

        $factory = new Factory();
        $formElements = $this->serviceLocator->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $propertyForm = $factory->createForm($appConfigForm);

        return $propertyForm;
    }

    /**
     * Returns all active languages in the site (via Template's ID)
     * @param $templateId
     * @return array
     */
    public function getSiteLanguages($templateId = null)
    {
        if (empty($templateId)) {
            return [];
        }

        /**
         * @var \MelisEngine\Model\Tables\MelisTemplateTable $tplTable
         * @var \MelisEngine\Model\Tables\MelisSiteTable $siteTable
         * @var \MelisEngine\Model\Tables\MelisCmsSiteLangsTable $sitelangsTable
         */
        $tplTable = $this->getServiceLocator()->get('MelisEngineTableTemplate');
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $sitelangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');

        $tplData = $tplTable->getEntryById($templateId)->toArray();
        $tplData = reset($tplData);

        $siteData = $siteTable->getEntryById($tplData['tpl_site_id'])->toArray();
        $siteData = reset($siteData);

        $activeLangs = $sitelangsTable->getSiteLanguagesBySiteId($siteData['site_id'], true)->toArray();
        $siteLangs = [];
        foreach ($activeLangs as $language) {
            $siteLangs[] = $language['slang_lang_id'];
        }

        return $siteLangs;
    }

    /**
     * This function saves the page properties form, making an entry in PageSave
     * Events: meliscms_page_saveproperties_start / meliscms_page_saveproperties_end
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function savePropertiesAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $isNew = $this->params()->fromRoute('isNew', $this->params()->fromQuery('isNew', ''));
        $translator = $this->serviceLocator->get('translator');

        $eventDatas = array('idPage' => $idPage, 'isNew' => $isNew);
        $this->getEventManager()->trigger('meliscms_page_saveproperties_start', null, $eventDatas);

        // Get the form properly loaded
        $propertyForm = $this->getPropertyPageForm($idPage, $isNew);

        $melisPageSavedTable = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
        $melisPagePublishedTable = $this->getServiceLocator()->get('MelisEngineTablePagePublished');
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        // Check if post
        $request = $this->getRequest();
        if ($request->isPost()) {
            // Get the current page id from platform table
            $melisModuleName = getenv('MELIS_PLATFORM');
            $melisEngineTablePlatformIds = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
            $datasPlatformIds = $melisEngineTablePlatformIds->getPlatformIdsByPlatformName($melisModuleName);
            $datasPlatformIds = $datasPlatformIds->current();

            if (!empty($datasPlatformIds)) {
                // Get values posted and set them in form
                $postValues = $this->params()->fromRoute();
                if (!isset($postValues['page_duplicate_event_flag'])) {
                    $postValues = get_object_vars($request->getPost());
                    $postValues = $melisTool->sanitizePost($postValues);
                }

                $propertyForm->setData($postValues);

                if (!$isNew) {
                    // Set Page lang id not required
                    $propertyForm->getInputFilter()->get('plang_lang_id')->setRequired(false);
                }

                // Validate the form
                if ($propertyForm->isValid()) {
                    // Get datas validated
                    $datas = $propertyForm->getData();
                    $errors = [];

                    /**
                     * Additional Form Validation
                     *  - Raise error for the Selected language, if not active for the Site (via template)
                     */
                    if ($datas['page_tpl_id'] !== '-1') {
                        $siteLangs = $this->getSiteLanguages($datas['page_tpl_id']);

                        // Page language
                        $pageLangId = $datas['plang_lang_id'];
                        if (empty($pageLangId))
                            $pageLangId = $this->getServiceLocator()->get('MelisEngineTablePageLang')
                                ->getEntryById($idPage)->current()->plang_lang_id;

                        if (!in_array($pageLangId, $siteLangs)) {
                            $errors = [
                                'plang_lang_id' => [
                                    'errorMessage' => $translator->translate('tr_meliscms_page_form_page_p_lang_ko'),
                                    'label' => $translator->translate('tr_meliscms_page_tab_properties_form_Language'),
                                ],
                            ];
                        }
                    }

                    /**
                     * First, let's copy the published table entry
                     * inside the saved table if there's no entry in it.
                     * Kind of special case, the page is not new, it's being edited after being published
                     */
                    if (!$isNew) {
                        $dataSaved = $melisPageSavedTable->getEntryById($idPage);
                        $dataSaved = $dataSaved->toArray();
                        if (count($dataSaved) == 0) {
                            $dataPublished = $melisPagePublishedTable->getEntryById($idPage);
                            $dataPublished = $dataPublished->toArray();
                            if (count($dataPublished) > 0) {
                                $dataPublished = $dataPublished[0];
                                $melisPageSavedTable->save($dataPublished, $idPage);
                            }
                        }
                    } else {
                        // New page, create an empty content
                        $newXmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                        $newXmlContent .= '<document type="MelisCMS" author="MelisTechnology" version="2.0">' . "\n";
                        $newXmlContent .= '</document>';
                        $datas['page_content'] = $newXmlContent;
                    }


                    /**
                     * Datas are all saved in PageSave once validated.
                     * This allow to add fields in the app.form and fields in the db.
                     * It will then works with the new field as long as it's in the same table.
                     *
                     * Useless fields from the form must be unset
                     * Special fields must be set
                     */

                    $success = 0;
                    $language = $datas['plang_lang_id'];
                    $template = $datas['page_tpl_id'];

                    unset($datas['page_submit']);
                    unset($datas['page_creation_date']);
                    unset($datas['plang_lang_id']);
                    unset($datas['style_id']);

                    $datas['page_id'] = $idPage;

                    if (empty($template)) {
                        $errors = array(
                            'page_tpl_id' => array(
                                'errorMessage' => $translator->translate('tr_meliscms_page_form_page_tpl_id_invalid'),
                                'label' => $translator->translate('tr_meliscms_page_tab_properties_form_Template'),
                            ),
                        );
                    }

                    if ($isNew) {
                        $datas['page_creation_date'] = date('Y-m-d H:i:s');

                        if (empty($language)) {
                            $errors = array(
                                'plang_lang_id' => array(
                                    'errorMessage' => $translator->translate('tr_meliscms_page_form_page_p_lang_id_invalid'),
                                    'label' => $translator->translate('tr_meliscms_page_tab_properties_form_Language'),
                                ),
                            );
                        }
                    } else {
                        $datas['page_edit_date'] = date('Y-m-d H:i:s');

                        $melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');
                        $user = $melisCoreAuth->getIdentity();
                        if (!empty($user))
                            $datas['page_last_user_id'] = $user->usr_id;
                    }


                    if (empty($errors)) {
                        $success = 1;
                        $res = $melisPageSavedTable->save($datas, $idPage);
                    }


                    $result = array(
                        'success' => $success,
                        'errors' => array($errors),
                    );

                } else {
                    // Get validation errors
                    $result = array(
                        'success' => 0,
                        'errors' => array($propertyForm->getMessages()),
                    );
                }
            } else {
                $errors = array(
                    'platform_ids' => array(
                        'noPlatformIds' => $translator->translate('tr_meliscms_no_available_platform_ids'),
                        'label' => $translator->translate('tr_meliscms_tool_platform_ids'),
                    ),
                );

                $result = array(
                    'success' => 0,
                    'errors' => array($errors),
                );
            }
        } else {
            $result = array(
                'success' => 0,
                'errors' => array(array('empty' => $translator->translate('tr_meliscms_form_common_errors_Empty datas'))),
            );
        }

        $this->getEventManager()->trigger('meliscms_page_saveproperties_end', null, $result);

        return new JsonModel($result);
    }

}
