<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Form\Factory; 

use Zend\Form\Element\Text;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

/**
 * Melis Cms Switch plugin factory
 */

class MelisSwitchFactory extends Text implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $formElementManager)
    { 
        $element = new Text;        
        
        return $element;
    }
}

