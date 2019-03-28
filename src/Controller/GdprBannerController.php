<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;


use Zend\Form\Factory;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

/**
 * Allows the customization of the text displayed on the sites
 * to inform users on cookies and data protection rights.
 */
class GdprBannerController extends AbstractActionController
{
    const MODULE_NAME = 'MelisCmsGdprBanner';

    /**
     * @return ViewModel
     */
    public function gdprBannerTabAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * @return ViewModel
     */
    public function headerAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $tool = $this->getTool();
        /** @var \Zend\Form\Form $form */
        $form = $this->getForm('site_filter_form');

        $label = [
            'saveBtn' => $tool->getTranslation('tr_meliscms_common_save'),
            'toolDesc' => $tool->getTranslation('tr_melis_cms_gdpr_banner_desc'),
        ];

        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->form = $form;
        $view->label = $label;

        return $view;
    }

    /**
     * this method will get the meliscore tool
     * @return array|object
     */
    private function getTool()
    {
        $toolSvc = $this->getServiceLocator()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey('MelisCmsGdprBanner', 'MelisCmsGdprBanner');

        return $toolSvc;
    }

    /**
     * @param string $formName
     * @return bool|\Zend\Form\ElementInterface
     */
    private function getForm(string $formName = '')
    {
        $form = false;

        if (!empty($formName)) {
            $melisConfig = $this->getServiceLocator()->get('MelisCoreConfig');
            $formConfig = $melisConfig->getItem(self::MODULE_NAME . '/forms/' . $formName);
            $hasMergeFormConfig = $melisConfig->getOrderFormsConfig(self::MODULE_NAME . '/forms/' . $formName);

            if ($hasMergeFormConfig) {
                $formConfig = $hasMergeFormConfig;
            }

            if (!empty($formConfig)) {
                $factory = new Factory();
                $formMgr = $this->getServiceLocator()->get('FormElementManager');
                $factory->setFormElementManager($formMgr);
                $form = $factory->createForm($formConfig);
            }
        }

        return $form;
    }

    /**
     * @return ViewModel
     */
    public function bannerDetailsAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $tool = $this->getTool();

        /** @var \Zend\Form\Form $form */
        $form = $this->getForm('banner_content_form');

        $melisEngineLangTable = $this->getServiceLocator()->get('MelisEngineTableCmsLang');
        $melisEngineLang = $melisEngineLangTable->fetchAll();
        $languages = $melisEngineLang->toArray();

        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->languages = $languages;
        $view->form = $form;

        return $view;
    }
}
