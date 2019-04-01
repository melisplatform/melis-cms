<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */
namespace MelisCms\Model\Tables\Factory;

use MelisCms\Model\MelisCmsGdprTexts;
use MelisCms\Model\Tables\MelisCmsGdprTextsTable;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Stdlib\Hydrator\ObjectProperty;

class MelisCmsGdprTextsFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sl)
    {
        $hydratingResultSet = new HydratingResultSet(new ObjectProperty(), new MelisCmsGdprTexts());
        $tableGateway = new TableGateway('melis_cms_gdpr_texts', $sl->get('Zend\Db\Adapter\Adapter'), null, $hydratingResultSet);
        
        return new MelisCmsGdprTextsTable($tableGateway);
    }
}