<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Form\Factory;

use Laminas\ServiceManager\ServiceManager;
use MelisCore\Form\Factory\MelisSelectFactory;

/**
 * This class creates a select box for melis languages
 */
class MelisCmsLanguageSelectFactory extends MelisSelectFactory
{
	protected function loadValueOptions(ServiceManager $serviceManager)
	{
		
		$tableLang = $serviceManager->get('MelisEngineTableCmsLang');
		$languages = $tableLang->fetchAll();
		$siteLangNames = null;

		//during page creation, language options should only include the languages that were set for the given site
		$request = $serviceManager->get('request');
		$idPage = $request->getQuery('idPage');
		$idFatherPage = $request->getQuery('idFatherPage');

		if (empty($idPage) && $idFatherPage) {
			//get site id using the father page id
		    $melisPage = $serviceManager->get('MelisEnginePage');
	        $datasPage = $melisPage->getDatasPage($idFatherPage, 'published');  
	        if ($datasPage->getMelisTemplate()) {
	        	$siteId = $datasPage->getMelisTemplate()->tpl_site_id;

				$siteLangsTable = $serviceManager->get('MelisEngineTableCmsSiteLangs');
		        $siteLangs = $siteLangsTable->getSiteLangs(null, $siteId, null, true)->toArray();	

		        if ($siteLangs) {
		        	$siteLangNames = array_column($siteLangs,'lang_cms_name');
		        }		        
	        }			
		}

		$valueoptions = [];
		$max = $languages->count();
		for ($i = 0; $i < $max; $i++) {
			$tpl = $languages->current();

			if ( (!empty($siteLangNames) && in_array($tpl->lang_cms_name, $siteLangNames)) || empty($siteLangNames) ) {
				$valueoptions[$tpl->lang_cms_id] = $tpl->lang_cms_name;				
			}	

			$languages->next();
		}
		
		return $valueoptions;
	}

}