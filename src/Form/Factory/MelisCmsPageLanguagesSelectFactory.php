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
 * This class creates a select box for melis page create languages version
 */
class MelisCmsPageLanguagesSelectFactory extends MelisSelectFactory
{
	protected function loadValueOptions(ServiceManager $serviceManager)
	{
		$request = $serviceManager->get('request');
		$idPage = $request->getQuery('idPage');
		
		$pageLangTbl = $serviceManager->get('MelisEngineTablePageLang');
		$pageLang = $pageLangTbl->getEntryByField('plang_page_id', $idPage)->current();
		
		$pagesLang = [];
		if (!empty($pageLang)) {
		    $pageInitialId = $pageLang->plang_page_id_initial;
		    $pageLang = $pageLangTbl->getEntryByField('plang_page_id_initial', $pageInitialId);
		    
		    foreach ($pageLang As $val) {
		        array_push($pagesLang, $val->plang_lang_id);
		    }
		}
		
		$tableLang = $serviceManager->get('MelisEngineTableCmsLang');
		$languages = $tableLang->fetchAll();
		
		$valueoptions = [];
		foreach ($languages As $val) {
		    if (!in_array($val->lang_cms_id, $pagesLang)) {
		        $valueoptions[$val->lang_cms_locale] = $val->lang_cms_name;
		    }
		}
		
		return $valueoptions;
	}
}