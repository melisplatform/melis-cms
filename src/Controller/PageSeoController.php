<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use MelisCore\Controller\AbstractActionController;

/**
 * This class renders Melis CMS Page tab properties
 */
class PageSeoController extends AbstractActionController
{
	// The form is loaded from the app.form array
	const PageSeoAppConfigPath = '/meliscms/forms/meliscms_page_seo';
	
	/**
	 * Makes the rendering of the Page Properties Tab
	 * @return \Laminas\View\Model\ViewModel
	 */
	public function renderPagetabSeoAction()
	{
		$idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
		$melisKey = $this->params()->fromRoute('melisKey', '');
		
		$seoForm = $this->getSeoPageForm($idPage);
		
		/**
		 * Get the data to fill the form
		 */
		if (!empty($idPage))
		{
			$melisTablePageSeo = $this->getServiceManager()->get('MelisEngineTablePageSeo');
			$datasPageSeo = $melisTablePageSeo->getEntryById($idPage);
		}
		else
			$datasPageSeo = null;
		
		if (!empty($datasPageSeo))
		{
			$datasPageSeo = $datasPageSeo->current();
			if (!empty($datasPageSeo))
				$seoForm->bind($datasPageSeo);
		}
		
		/**
		 * Send back the view and add the form config inside
		*/
		$view = new ViewModel();
		$view->setVariable('meliscms_page_seo', $seoForm);
		$view->idPage = $idPage;
		$view->melisKey = $melisKey;
	
		return $view;
	}
	
	/**
	 * This function creates the Page Property Form and sends it back
	 *
	 * @param int $idPage
	 * @param bool $isNew
	 * @return \Laminas\Form\Form
	 */
	public function getSeoPageForm($idPage)
	{
		$pathAppConfigForm = self::PageSeoAppConfigPath;
		 
		/**
		 * Get the config for this form
		 */
		$melisMelisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');
		$appConfigForm = $melisMelisCoreConfig->getItem($pathAppConfigForm, $idPage . '_');
		
		
		/**
		 * Generate the form through factory and change ElementManager to
		 * have access to our custom Melis Elements
		 * Bind with datas
		 */
		$factory = new \Laminas\Form\Factory();
		$formElements = $this->getServiceManager()->get('FormElementManager');
		$factory->setFormElementManager($formElements);
		$appConfigForm['attributes']['action'] .= '?idPage=' . $idPage;
		$seoForm = $factory->createForm($appConfigForm);
	
		return $seoForm;
	}

    /**
     * Return seo keywords
     * @param pageId integer
     */
    public function getSeoKeywordsByPageId($pageId)
    {
        $pageId = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $data = array();

        $seoKeywords = $pageId->getSeoKeywords($pageId);
        $data        = $seoKeywords;

        return $data;
    }

	/**
	 * This function saves the page seo form
	 * @return \Laminas\View\Model\JsonModel
	 */
	public function saveSeoAction()
	{
		$idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
		$translator = $this->getServiceManager()->get('translator');
		
		$eventDatas = array('idPage' => $idPage);
		$this->getEventManager()->trigger('meliscms_page_saveseo_start', null, $eventDatas);
		
		// Get the form properly loaded
		$seoForm = $this->getSeoPageForm($idPage);

		$melisTablePageSeo = $this->getServiceManager()->get('MelisEngineTablePageSeo');

		// Check if post
		$request = $this->getRequest();
		if ($request->isPost())
		{
            // Get values posted and set them in form
            $postValues = get_object_vars($request->getPost());

            if (!empty($postValues['pseo_url'])) {
                $postValues['pseo_url'] =  $this->cleanURL($postValues['pseo_url']);
            }

            if (!empty($postValues['pseo_canonical'])) {
                $postValues['pseo_canonical'] =  $this->cleanURL($postValues['pseo_canonical']);
            }

            $seoForm->setData($postValues);

            // Validate the form
			if ($seoForm->isValid())
			{
				// Get datas validated
				$datas = $seoForm->getData();
				
				$allEmpty = true;
				foreach ($datas as $data)
				{
					if (!empty($data))
					{
						$allEmpty = false;
						break;
					}
				}
				
				$success = 1;
				$datas['pseo_id'] = $idPage;
				
				if (substr($datas['pseo_url'], 0, 1) == '/')
					$datas['pseo_url'] = substr($datas['pseo_url'], 1, strlen($datas['pseo_url']));
				if (substr($datas['pseo_url_redirect'], 0, 1) == '/')
					$datas['pseo_url_redirect'] = substr($datas['pseo_url_redirect'], 1, strlen($datas['pseo_url_redirect']));
				if (substr($datas['pseo_url_301'], 0, 1) == '/')
					$datas['pseo_url_301'] = substr($datas['pseo_url_301'], 1, strlen($datas['pseo_url_301']));

				if (!$allEmpty)	
				{
					// Check for unicity of the URL declared
					if (!empty($datas['pseo_url']))
					{
						$datasPageSeo = $melisTablePageSeo->getEntryByField('pseo_url', $datas['pseo_url']);
						if (!empty($datasPageSeo))
						{
							$datasPageSeo = $datasPageSeo->current();
							if (!empty($datasPageSeo))
							{
								// Not this page of course
								if ($datasPageSeo->pseo_id != $idPage)
								{
									$pageNameDuplicate = '';
									$melisPage = $this->getServiceManager()->get('MelisEnginePage');
									$datasPage = $melisPage->getDatasPage($datasPageSeo->pseo_id, 'saved');
									$pageTree = $datasPage->getMelisPageTree();
									if (!empty($pageTree))
										$pageNameDuplicate = ' ' . $pageTree->page_name . ' (' . $datasPageSeo->pseo_id . ')';
									
									// This URL is already used somewhere else
									return new JsonModel(array(
											'success' => 0,
											'datas' => array(),
											'errors' => array(
															array(
																	'pseo_url' => $translator->translate('tr_meliscms_page_save_error_SEO Url already used on') . $pageNameDuplicate,
																	'label' => $translator->translate('tr_meliscms_page_save_error_label_SEO Url')
																)
															)
									));;
								}
							}
						}
					}
					
					// Cleaning special char and white spaces on SEO Url
					$enginePage = $this->getServiceManager()->get('MelisEngineTree');
					$datas['pseo_url'] = $enginePage->cleanString(mb_strtolower($datas['pseo_url']));
					// Checking for spaces
					if (preg_match('/\s/', $datas['pseo_url']))
					{
					    $datas['pseo_url'] = str_replace(" ", "", $datas['pseo_url']);
					}
					
				    $res = $melisTablePageSeo->save($datas, $idPage);
				}
				else
				{
					// All field are empty, let's delete the entry
					$melisTablePageSeo->deleteById($idPage);
				}
	
				$result = array(
						'success' => $success,
						'errors' => array(),
				);

			}
			else
			{
				// Add labels of errors
				$errors = $seoForm->getMessages();
				$melisMelisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');
				$appConfigForm = $melisMelisCoreConfig->getItem(PageSeoController::PageSeoAppConfigPath, $idPage . '_');
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
				
				// Get validation errors
				$result = array(
						'success' => 0,
						'errors' => array($errors),
				);

			}
		}
		else
		{
			$result = array(
					'success' => 0,
					'errors' => array(array('empty' => $translator->translate('tr_meliscms_form_common_errors_Empty datas'))),
			);
		}

		$this->getEventManager()->trigger('meliscms_page_saveseo_end', null, $result);
		
		return new JsonModel($result);
	}
	
	/**
	 * Deletes the SEO entry of the page
	 * Events: meliscms_page_deleteseo_start / meliscms_page_deleteseo_end
	 * 
	 * @return \Laminas\View\Model\JsonModel
	 */
	public function deletePageSeoAction()
	{
		$idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
		$translator = $this->getServiceManager()->get('translator');

		$eventDatas = array('idPage' => $idPage);
		$this->getEventManager()->trigger('meliscms_page_deleteseo_start', null, $eventDatas);
		
		$melisTablePageSeo = $this->getServiceManager()->get('MelisEngineTablePageSeo');
		$melisTablePageSeo->deleteById($idPage);
		
		$result = array(
				'success' => 1,
				'errors' => array(),
		);
		
		$this->getEventManager()->trigger('meliscms_page_deleteseo_end', null, $result);
		
		return new JsonModel($result);
	}

    /**
     * Rids the URL from special characters
     * @param string $url
     * @return mixed
     */
    private function cleanURL(string $url = '')
    {
        $url = str_replace(' ', '-', $url); 				// Replaces all spaces with hyphens
        $url = preg_replace('/[^A-Za-z0-9\/\-]+/', '-', $url);	// Replaces special characters with hyphens

        // remove "/" prefix on generated URL
        if (substr($url, 0, 1) == '/') {
            return preg_replace('/\//', '', $url, 1);
        }

        return $url;
    }
}
