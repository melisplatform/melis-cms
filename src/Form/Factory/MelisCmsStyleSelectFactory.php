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
 * Cms Platfrom Ids select factory
 * This will return available platform the have not added to Cms Platform IDs
 */
class MelisCmsStyleSelectFactory extends MelisSelectFactory
{
	protected function loadValueOptions(ServiceLocatorInterface $formElementManager)
	{
		$serviceManager = $formElementManager->getServiceLocator();
		$request = $serviceManager->get('Request');
		$idPage = $request->getQuery('idPage', $request->getPost('idPage', ''));
		
		$styleTable = $serviceManager->get('MelisEngineTableStyle');
		$styles = $styleTable->fetchAll();
		
		$request = $serviceManager->get('Request');
		$valueoptions = array();
		
		$max = $styles->count();
		for ($i = 0; $i < $max; $i++)
		{
			$style = $styles->current();

            if(true === (bool) $style->status) {
                $valueoptions[] = array(
                    'label' => $style->style_name,
                    'value' => $style->style_id,
                    'attributes' => array(
                        'data-link' => $style->style_path,
                    )
                );
            }

			
			$styles->next();
		}

		return $valueoptions; 
	}

}