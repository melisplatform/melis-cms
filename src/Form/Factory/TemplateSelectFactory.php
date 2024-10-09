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
 * Template select factory to fill the template list
 */
class TemplateSelectFactory extends MelisSelectFactory
{
	protected function loadValueOptions(ServiceManager $serviceManager)
	{
		$translator = $serviceManager->get('translator');
        $request = $serviceManager->get('Request');
        $idPage = (int) $request->getQuery('idPage', $request->getPost('idPage', ''));

        //page id is empty, maybe its a new page, we try to look for its father page id
        if(empty($idPage)) {
            $idPage = (int) $request->getQuery('fatherPageId', $request->getPost('fatherPageId', ''));
            $idPage = empty($idPage) ? (int) $request->getQuery('idFatherPage', $request->getPost('idFatherPage', '')) : $idPage;
        }

        $engineTree = $serviceManager->get('MelisEngineTree');
        $pageFatherInfo = $engineTree->getSiteByPageId($idPage);
        if (empty($pageFatherInfo))
            //try form page saved
            $pageFatherInfo = $engineTree->getSiteByPageId($idPage, '');
        //get site id
        $siteId = $pageFatherInfo->site_id;

		$tableTemplate = $serviceManager->get('MelisEngineTableTemplate');
		$templates = $tableTemplate->getSortedTemplates()->toArray();

		$valueoptions = array();
		
		$valueoptions[-1] = $translator->translate('tr_meliscms_page_tab_properties_form_Template_None');

		// Get sites
//        $sites = $serviceManager->get('MelisEngineTableSite')->fetchAll()->toArray();
        /**
         * Load templates for those who are related to the page site
         */
        $site = $serviceManager->get('MelisEngineTableSite')->getEntryById($siteId)->current();
        if(!empty($site)) {
            foreach ($templates as $key => $tpl) {
                if ($tpl['tpl_site_id'] == $site->site_id) {
                    $valueoptions[$tpl['tpl_id']] = $site->site_name . ' - ' . $tpl['tpl_name'] . ' (' . $tpl['tpl_id'] . ')';
                }
            }
        }
//        $siteNames = [];
//        if (!empty($sites)) {
//            foreach ($sites as $site) {
//                $siteLabel = $site['site_label'] ?? $site['site_name'];
//                $siteNames[$site['site_id']] = $siteLabel;
//            }
//        }
//
//		$max = $templates->count();
//		for ($i = 0; $i < $max; $i++) {
//			$tpl = $templates->current();
//			if(array_key_exists($tpl->tpl_site_id, $siteNames))
//			    $valueoptions[$tpl->tpl_id] = $siteNames[$tpl->tpl_site_id] . ' - ' . $tpl->tpl_name . ' (' . $tpl->tpl_id . ')';
//			else
//                $valueoptions[$tpl->tpl_id] = $tpl->tpl_name . ' (' . $tpl->tpl_id . ')';
//			$templates->next();
//		}

		return $valueoptions;
	}

}