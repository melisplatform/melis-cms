<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;


use Zend\Form\Factory;
use Zend\Form\Form;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

/**
 * Allows the customization of the text displayed on the sites
 * to inform users on cookies and data protection rights.
 */
class GdprBannerController extends AbstractActionController
{
    const MODULE_NAME = 'MelisCmsGdprBanner';
    const LOG_UPDATE = 'UDPATE';

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
        /** @var Form $form */
        $form = $this->getForm('site_filter_form');
        $form->get('mcgdprbanner_site_id')->setLabel($form->get('mcgdprbanner_site_id')->getLabel() . ' *');

        $label = [
            'saveBtn' => $tool->getTranslation('tr_meliscms_common_save'),
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
        $siteId = $this->params()->fromQuery('siteId', null);

        $bannerContents = [];
        if (!empty($siteId)) {
            /** @var \MelisCms\Service\MelisCmsGdprService $bannerSvc */
            $bannerSvc = $this->getServiceLocator()->get('MelisCmsGdprService');
            $result = $bannerSvc->getGdprBannerText((int)$siteId)->toArray();
            if (!empty($result)) {
                foreach ($result as $content) {
                    $bannerContents[$content['mcgdpr_text_lang_id']] = [
                        'mcgdpr_text_id' => $content['mcgdpr_text_id'],
                        'mcgdpr_text_value' => $content['mcgdpr_text_value'],
                    ];
                }
            }
        }

        /** @var Form $form */
        $form = $this->getForm('banner_content_form');

        $melisEngineLangTable = $this->getServiceLocator()->get('MelisEngineTableCmsLang');
        $melisEngineLang = $melisEngineLangTable->fetchAll();
        $languages = $melisEngineLang->toArray();

        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->languages = $languages;
        $view->bannerContentform = $form;
        $view->bannerContents = $bannerContents;

        return $view;
    }

    /**
     * Saves banner contents
     * @return JsonModel
     */
    public function saveBannerAction()
    {
        $tool = $this->getTool();
        $request = $this->getRequest();
        $logItemId = 0;
        $response = [
            'success' => false,
            'textTitle' => $tool->getTranslation('tr_melis_core_gdpr'),
            'textMessage' => $tool->getTranslation('tr_melis_cms_gdpr_banner_save_ko'),
            'errors' => []
        ];

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            //$data = $this->getServiceLocator()->get('MelisCoreTool')->sanitizePost($data);

            /**
             * Validate Site & Content
             * @var Form $siteForm
             * @var Form $contentForm
             */
            $siteForm = $this->getForm('site_filter_form');
            $siteId = [];
            if (!empty($data['filters']['siteId'][0]['value'])) {
                $siteForm->setData([
                    $data['filters']['siteId'][0]['name'] => $data['filters']['siteId'][0]['value']
                ]);
                $siteId = $data['filters']['siteId'][0]['value'];
            } else {
                $siteForm->setData($siteId);
            }

            if (!$siteForm->isValid()) {
                $errors = $this->formatErrorMsg($siteForm);
                $response['errors'] = array_merge($response['errors'], $errors);
            }

            $contentForm = $this->getForm('banner_content_form');
            $bannerContents = $data['bannerContent'];
            foreach ($bannerContents as $langId => $bannerContent) {
                $contentChecker = clone $contentForm;

                $contentChecker->setData([$bannerContent[0]['name'] => $bannerContent[0]['value']]);
                if (!$contentChecker->isValid()) {
                    $errors = $this->formatErrorMsg($contentChecker);
                    $response['errors'] = array_merge($response['errors'], $errors);
                }
            }

            if (empty($response['errors'])) {
                /** @var \MelisCms\Service\MelisCmsGdprService $bannerService */
                $bannerService = $this->getServiceLocator()->get('MelisCmsGdprService');
                /** Save languages with non-empty content */
                foreach ($bannerContents as $langId => $bannerContent) {
                    /** Save the content */
                    $id = (int)$bannerContent[0]['value'];
                    $content = $bannerContent[1]['value'];

                    if ($id > 0) {
                        /** Update */
                        if (empty($content)) {
                            /** Delete the corresponding row for this language & site from the database */
                            $result = $bannerService->deleteBannerById($id);
                        } else {
                            $result = $bannerService->saveBanner($id, $content, $siteId, $langId);
                        }
                    } else {
                        /** Add */
                        $result = $bannerService->saveBanner(null, $content, $siteId, $langId);
                    }

                    if ($result > 0) {
                        $logItemId = $result;
                        $response['success'] = true;
                        $response['textMessage'] = $tool->getTranslation('tr_melis_cms_gdpr_banner_save_ok');
                    }
                }
            }
        }

        // add to flash messenger
        $this->getEventManager()->trigger(
            'meliscms_gdpr_save_banner_end',
            $this,
            array_merge(
                $response,
                ['typeCode' => self::LOG_UPDATE, 'itemId' => $logItemId]
            )
        );

        return new JsonModel($response);
    }

    /**
     * Formats the form errors to a melis-notification-friendly format
     * @param Form|null $form
     * @return array
     */
    private function formatErrorMsg(Form $form = null)
    {
        $formattedErrors = [];
        $formErrors = $form->getMessages();
        if (empty($formErrors)) {
            return $formattedErrors;
        } else {
            foreach ($formErrors as $fieldName => $fieldErrors) {
                $formattedErrors[$fieldName] = $fieldErrors;
                $formattedErrors[$fieldName]['label'] = $form->get($fieldName)->getLabel();
            }

            return $formattedErrors;
        }
    }
}
