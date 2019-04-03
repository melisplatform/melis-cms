<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller\Plugin;


use MelisEngine\Controller\Plugin\MelisTemplatingPlugin;
use Zend\Form\Factory;
use Zend\View\Model\ViewModel;

/**
 * This plugin implements the business logic of the
 * "latestBlog" plugin.
 *
 * Please look inside app.plugins.php for possible awaited parameters
 * in front and back function calls.
 *
 * front() and back() are the only functions to create / update.
 * front() generates the website view
 *
 * Configuration can be found in $pluginConfig / $pluginFrontConfig / $pluginBackConfig
 * Configuration is automatically merged with the parameters provided when calling the plugin.
 * Merge detects automatically from the route if rendering must be done for front or back.
 *
 * How to call this plugin without parameters:
 * $plugin = $this->MelisCmsGdprBannerPlugin();
 * $pluginView = $plugin->render();
 *
 * How to call this plugin with custom parameters:
 * $plugin = $this->MelisCmsGdprBannerPlugin();
 * $parameters = array(
 *      'template_path' => 'MySiteTest/cms/gdprBanner'
 * );
 * $pluginView = $plugin->render($parameters);
 *
 * How to add to your controller's view:
 * $view->addChild($pluginView, 'gdprBanner');
 *
 * How to display in your controller's view:
 * echo $this->gdprBanner;
 *
 *
 */
class MelisCmsGdprBannerPlugin extends MelisTemplatingPlugin
{
    public function __construct($updatesPluginConfig = array())
    {
        parent::__construct($updatesPluginConfig);

        $this->configPluginKey = 'MelisCmsGdprBanner';
        $this->pluginXmlDbKey = 'MelisCmsGdprBanner';
    }

    /**
     * This function gets the datas and create an array of variables
     * that will be associated with the child view generated.
     */
    public function front()
    {
        $locale = 'en_EN';
        $data = $this->getFormData();
        $translator = $this->getServiceLocator()->get('translator');
        /**
         * Get site & language from the page id
         * @var \MelisEngine\Service\MelisPageService $pageService
         */
        $langId = null;
        $siteId = null;
        $pageId = $data['pageId']?? null;
        if (!empty($pageId)) {
            $pageService = $this->getServiceLocator()->get('MelisEnginePage');
            $pageData = $pageService->getDatasPage($pageId)->getMelisPageTree();
            if (!empty($pageData)) {
                $langId = $pageData->lang_cms_id?? $langId;
                $locale = $pageData->lang_cms_locale;
                if (!empty($pageData->page_tpl_id)) {
                    /** @var \MelisEngine\Model\Tables\MelisTemplateTable $tplTable */
                    $tplTable = $this->getServiceLocator()->get('MelisEngineTableTemplate');
                    $tplData = $tplTable->getEntryById($pageData->page_tpl_id)->toArray();
                    if (!empty($tplData [0]['tpl_site_id'])) {
                        $siteId = $tplData [0]['tpl_site_id'];
                    }
                }
            }
        }

        /**
         * Get banner content for this site & language
         * @var \MelisCms\Service\MelisCmsGdprService $bannerService
         */
        $bannerService = $this->getServiceLocator()->get('MelisCmsGdprService');
        $bannerContents = $bannerService->getGdprBannerText($siteId, $langId)->toArray();
        if (!empty($bannerContents[0])) {
            $bannerContents = $bannerContents[0]['mcgdpr_text_value'];
        }

        $labels = [
            'pluginLoaded' => $translator->translate('tr_melis_cms_gdpr_banner_plugin_loaded'),
            'agree' => $translator->translate('tr_melis_cms_gdpr_banner_plugin_agree_' . $locale),
        ];

        $viewVariables = [
            'labels' => $labels,
            'pluginId' => $data['id'],
            'renderMode' => $this->renderMode,
            'bannerContents' => $bannerContents,
        ];

        return $viewVariables;
    }

    /**
     * Returns the data to populate the form inside the modals when invoked
     * @return array|bool|null
     */
    public function getFormData()
    {
        $data = parent::getFormData();

        return $data;
    }

    /**
     * This function generates the form displayed when editing the parameters of the plugin
     */
    public function createOptionsForms()
    {
        // construct form
        $factory = new Factory();
        $formElements = $this->getServiceLocator()->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $formConfig = $this->pluginBackConfig['modal_form'];
        $tool = $this->getServiceLocator()->get('translator');

        $response = [];
        $render = [];
        if (!empty($formConfig)) {
            foreach ($formConfig as $formKey => $config) {
                $form = $factory->createForm($config);
                $request = $this->getServiceLocator()->get('request');
                $parameters = $request->getQuery()->toArray();

                if (!isset($parameters['validate'])) {
                    $form->setData($this->getFormData());
                    $viewModelTab = new ViewModel();
                    $viewModelTab->setTemplate($config['tab_form_layout']);
                    $viewModelTab->modalForm = $form;
                    $viewModelTab->formData = $this->getFormData();

                    $viewModelTab->labels = [
                        'noPropsMsg' => $tool->translate('tr_melis_cms_gdpr_banner_plugin_empty_props'),
                    ];

                    $viewRender = $this->getServiceLocator()->get('ViewRenderer');
                    $html = $viewRender->render($viewModelTab);
                    array_push($render, [
                            'name' => $config['tab_title'],
                            'icon' => $config['tab_icon'],
                            'html' => $html
                        ]
                    );
                } else {
                    // validate the forms and send back an array with errors by tabs
                    $post = get_object_vars($request->getPost());
                    $success = false;
                    $form->setData($post);

                    if ($form->isValid()) {
                        $success = true;
                        array_push($response, [
                            'name' => $this->pluginBackConfig['modal_form'][$formKey]['tab_title'],
                            'success' => $success,
                        ]);
                    } else {
                        $errors = $form->getMessages();

                        foreach ($errors as $keyError => $valueError) {
                            foreach ($config['elements'] as $keyForm => $valueForm) {
                                if ($valueForm['spec']['name'] == $keyError && !empty($valueForm['spec']['options']['label'])) {
                                    $errors[$keyError]['label'] = $valueForm['spec']['options']['label'];
                                }
                            }
                        }

                        array_push($response, [
                            'name' => $this->pluginBackConfig['modal_form'][$formKey]['tab_title'],
                            'success' => $success,
                            'errors' => $errors,
                            'message' => '',
                        ]);
                    }
                }
            }
        }

        if (!isset($parameters['validate'])) {
            return $render;
        } else {
            return $response;
        }
    }

    /**
     * This method will decode the XML in DB to make it in the form of the plugin config file
     * so it can overide it. Only front key is needed to update.
     * The part of the XML corresponding to this plugin can be found in $this->pluginXmlDbValue
     */
    public function loadDbXmlToPluginConfig()
    {
        $configValues = array();

        $xml = simplexml_load_string($this->pluginXmlDbValue);
        if ($xml) {
            if (!empty($xml->template_path)) {
                $configValues['template_path'] = (string)$xml->template_path;
            }
        }

        return $configValues;
    }

    /**
     * This method saves the XML version of this plugin in DB, for this pageId
     * Automatically called from savePageSession listenner in PageEdition
     */
    public function savePluginConfigToXml($parameters)
    {
        $xmlValueFormatted = '';

        // template_path is mendatory for all plugins
        if (!empty($parameters['template_path']))
            $xmlValueFormatted .= "\t\t" . '<template_path><![CDATA[' . $parameters['template_path'] . ']]></template_path>';

        // Something has been saved, let's generate an XML for DB
        $xmlValueFormatted = "\t" . '<' . $this->pluginXmlDbKey . ' id="' . $parameters['melisPluginId'] . '">' .
            $xmlValueFormatted .
            "\t" . '</' . $this->pluginXmlDbKey . '>' . "\n";

        return $xmlValueFormatted;
    }
}
