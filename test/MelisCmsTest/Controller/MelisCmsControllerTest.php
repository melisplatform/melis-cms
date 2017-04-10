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
    /**
     * Get getMelisCmsPageSaved table
     * @return mixed
     */
    private function getMelisCmsPageSaved()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }
    /**
     * Get getMelisCmsPageSeo table
     * @return mixed
     */
    private function getMelisCmsPageSeo()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }
    /**
     * Get getMelisCmsPageTree table
     * @return mixed
     */
    private function getMelisCmsPageTree()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }
    /**
     * Get getMelisCmsPlatformIds table
     * @return mixed
     */
    private function getMelisCmsPlatformIds()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }
    /**
     * Get getMelisCmsSite table
     * @return mixed
     */
    private function getMelisCmsSite()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }
    /**
     * Get getMelisCmsSite301 table
     * @return mixed
     */
    private function getMelisCmsSite301()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }
    /**
     * Get getMelisCmsSite404 table
     * @return mixed
     */
    private function getMelisCmsSite404()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }
    /**
     * Get getMelisCmsSiteDomain table
     * @return mixed
     */
    private function getMelisCmsSiteDomain()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }
    /**
     * Get getMelisCmsTemplate table
     * @return mixed
     */
    private function getMelisCmsTemplate()
    {
        $conf = $this->sm->getPhpUnitTool()->getTable('MelisCms', __METHOD__);
        return $this->sm->getTableMock(new $conf['model'], $conf['model_table'], $conf['db_table_name'], $this->method);
    }


    public function getPayload($method)
    {
        return $this->sm->getPhpUnitTool()->getPayload('MelisCms', $method);
    }

    /**
     * START ADDING YOUR TESTS HERE
     */

    public function testBasicMelisCmsTestSuccess()
    {
        $this->assertEquals("equalvalue", "equalvalue");
    }

    public function testBasicMelisCmsTestError()
    {
        $this->assertEquals("supposed-to", "display-an-error");
    }



}

