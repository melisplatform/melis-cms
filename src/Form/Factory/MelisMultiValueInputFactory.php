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

class MelisMultiValueInputFactory extends Text
{
    public function __invoke(ContainerInterface $container, $targetName)
    {
        $element = new Text;
        // added melis-multi-val-input for multiple input
        $element->setAttribute('data-tags', '');
        $element->setAttribute('class', 'melis-multi-val-input');
        
        return $element;
    }
}