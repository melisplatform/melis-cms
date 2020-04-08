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
		
		$tableTemplate = $serviceManager->get('MelisEngineTableTemplate');
		$templates = $tableTemplate->getSortedTemplates();

		$valueoptions = array();
		
		$valueoptions[-1] = $translator->translate('tr_meliscms_page_tab_properties_form_Template_None');

		// Get sites
        $sites = $serviceManager->get('MelisEngineTableSite')->fetchAll()->toArray();
        $siteNames = [];
        if (!empty($sites)) {
            foreach ($sites as $site) {
                $siteLabel = $site['site_label'] ?? $site['site_name'];
                $siteNames[$site['site_id']] = $siteLabel;
            }
        }

		$max = $templates->count();
		for ($i = 0; $i < $max; $i++) {
			$tpl = $templates->current();
			if(array_key_exists($tpl->tpl_site_id, $siteNames))
			    $valueoptions[$tpl->tpl_id] = $siteNames[$tpl->tpl_site_id] . ' - ' . $tpl->tpl_name . ' (' . $tpl->tpl_id . ')';
			else
                $valueoptions[$tpl->tpl_id] = $tpl->tpl_name . ' (' . $tpl->tpl_id . ')';
			$templates->next();
		}

		return $valueoptions;
	}

}