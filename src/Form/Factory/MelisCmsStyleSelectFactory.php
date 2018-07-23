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
        /**
         * @var \Zend\ServiceManager\ServiceLocatorInterface $serviceManager
         */
		$serviceManager = $formElementManager->getServiceLocator();
		$request = $serviceManager->get('Request');
		$idPage = (int) $request->getQuery('idPage', $request->getPost('idPage', ''));

        /**
         * @var \MelisEngine\Model\Tables\MelisCmsStyleTable $styleTable
         */
		$styleTable = $serviceManager->get('MelisEngineTableStyle');
		$styles = $styleTable->getEntryByField('style_site_id', $idPage);

		$valueoptions = array();
		
		$max = $styles->count();
		for ($i = 0; $i < $max; $i++)
		{
			$style = $styles->current();

            if(true === (bool) $style->style_status) {
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