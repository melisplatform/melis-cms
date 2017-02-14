<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCmsTest\Controller;

use MelisCore\ServiceManagerGrabber;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
class MelisCmsControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = false;
    protected $sm;
    protected $method = 'save';

    public function setUp()
    {
        $this->sm  = new ServiceManagerGrabber();
    }

        /**
     * Get getMelisCmsLang table
     * @return mixed
     */
    private function getMelisCmsLang()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }
    /**
     * Get getMelisPageComment table
     * @return mixed
     */
    private function getMelisPageComment()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }
    /**
     * Get getMelisPageLang table
     * @return mixed
     */
    private function getMelisPageLang()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }
    /**
     * Get getMelisCmsPagePublished table
     * @return mixed
     */
    private function getMelisCmsPagePublished()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }


    public function getPayload($method)
    {
        return $this->sm->getPhpUnitTool()->getPayload('{{moduleName}}', $method);
    }


    public function testBasicMelisCmsTestSuccess()
    {
        $this->assertEquals("equalvalue", "equalvalue");
    }

    public function testBasicMelisCmsTestError()
    {
        $this->assertEquals("supposed-to", "display-an-error");
    }

    /**
     * START ADDING YOUR TESTS HERE
     */


}

