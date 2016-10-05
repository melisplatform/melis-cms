<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Form\Factory;

use Zend\ServiceManager\ServiceLocatorInterface;
use MelisCore\Form\Factory\MelisSelectFactory;

/**
 * Template select factory to fill the template list
 */
class TemplateSelectFactory extends MelisSelectFactory
{
	protected function loadValueOptions(ServiceLocatorInterface $formElementManager)
	{
		$serviceManager = $formElementManager->getServiceLocator();

		$translator = $serviceManager->get('translator');
		
		$tableTemplate = $serviceManager->get('MelisEngineTableTemplate');
		$templates = $tableTemplate->getSortedTemplates();

		$valueoptions = array();
		
		$valueoptions[-1] = $translator->translate('tr_meliscms_page_tab_properties_form_Template_None');
		
		$max = $templates->count();
		for ($i = 0; $i < $max; $i++)
		{
			$tpl = $templates->current();
			$valueoptions[$tpl->tpl_id] = $tpl->tpl_name . ' (' . $tpl->tpl_id . ')';
			$templates->next();
		}

		return $valueoptions;
	}

}