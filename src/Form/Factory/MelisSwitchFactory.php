<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Form\Factory; 

use Laminas\Form\Element\Text;
use Psr\Container\ContainerInterface;

/**
 * Melis Cms Switch plugin factory
 */

class MelisSwitchFactory extends Text
{
    public function __invoke(ContainerInterface $container)
    { 
        $element = new Text;        
        return $element;
    }
}

