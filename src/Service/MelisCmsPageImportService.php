<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;
use Zend\Db\Metadata\Metadata;

class MelisCmsPageImportService extends MelisCoreGeneralService
{
    public function importTest($xmlString, $keepPrimaryId = false)
    {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_test_file_start', $arrayParameters);

        $errors = [];
        $success = true;
        $xmlArray = $this->xmlToArray($arrayParameters['xmlString'], $errors);

//        $test = simplexml_load_string($xmlString);
//        $arrr = [];
//        print_r($test->page->tables->melis_cms_page_published->page_content->asXML());
//        $this->checkAll($test, $arrr);

        if ($xmlArray) {
            $this->checkTables($xmlArray, $errors);
            $this->checkExternalTables();

            if ($keepPrimaryId) {
                $pages = [];
                $this->getAllPagesRecursively($xmlArray, $pages);
                $this->checkPageIds($pages,$errors);
            }
        }

        if (!empty($errors))
            $success = false;

        $arrayParameters['result'] = [
            'success' => $success,
            'errors' => $errors
        ];

        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_test_file_end', $arrayParameters);
        return $arrayParameters['result'];
    }

    public function importPageTree()
    {

    }

    public function importPage()
    {

    }

    public function importPageRessources()
    {

    }

    private function checkTables($xml, &$errors)
    {
        $tableNames = $this->getDbTableNames();
        $tables = $xml['page']['tables'];
        $externalTables = $xml['external']['tables'];

        foreach ($tables as $tableName => $tableColumns) {
            if (in_array($tableName, $tableNames)) {
                $this->checkTableColumns($tableName, $tableColumns, $errors);
            } else {
                $errors[] = $tableName . ' does not exist on your db';
            }
        }

        foreach ($externalTables as $externalTableName => $externalTableColumns) {
            if (in_array($externalTableName, $tableNames)) {
                $this->checkTableColumns($externalTableName, $externalTableColumns['row_0'], $errors);
            } else {
                $errors[] = $externalTableName . ' does not exist on your db';
            }
        }
    }

    private function checkExternalTables()
    {

    }

    private function checkTableColumns($tableName, $tableColumns, &$errors)
    {
        $columns = $this->getTableColumns($tableName);

        foreach ($tableColumns as $columnName => $columnValue) {
            if (!in_array($columnName, $columns)) {
                $errors[] = $columnName . ' does not exist on table ' . $tableName;
            }
        }
    }

    /**
     * TODO: make adapter initialization only once rather than calling it everytime
     */
    private function getDbTableNames()
    {
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $metadata = new Metadata($adapter);
        return $metadata->getTableNames();
    }

    private function getTableColumns($tableName)
    {
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $metadata = new Metadata($adapter);
        return $metadata->getColumnNames($tableName);
    }

    private function getTableConstraints($tableName)
    {
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $metadata = new Metadata($adapter);
        return $metadata->getConstraints($tableName);
    }
    /**
     * TODO: make adapter initialization only once rather than calling it everytime
     */

    /**
     * Converts Xml String To Array
     * @param $xmlString
     * @return mixed
     */
    private function xmlToArray($xmlString, &$errors)
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($xmlString);
        if ($xml) {
            $json = json_encode($xml);
            return json_decode($json, TRUE);
        } else {
            $errors = 'Invalid XML file';
            return false;
        }
    }

    private function getAllPagesRecursively($array, &$pages)
    {
        if (isset($array['page'])) {
            $array = $array['page'];
        }

        if (isset($array[0]))
            $array = $array[0];

        if (!empty($array['children'])) {
            $children = $array['children'];
            unset($array['children']);
        }

        if (isset($array['tables']))
            $array = $array['tables'];


//        if (isset($array['page'])) {
//            if (!empty($array['page']['children'])) {
//                $children = $array['page']['children'];
//                unset($array['page']['children']);
//            }
//
//            if (isset($array['external']))
//                unset($array['external']);
//        }

//        if (isset($array[0])) {
//            $children = $array[0]['children'];
//            unset($array[0]['children']);
//        }

//        if (isset($array['children'])) {
//            $children = $array['children'];
//            unset($array['children']);
//        }

        $pages[] = $array;

        if (!empty($children)) {
            foreach ($children as $key => $child) {
                if (count($child) > 1) {
                    foreach ($child as $x => $y) {
                        $this->getAllPagesRecursively($y, $pages);
                    }
                } else {
                    $this->getAllPagesRecursively($child, $pages);
                }
            }
        }
    }

    private function checkPageIds($pages, &$errors)
    {
        foreach ($pages as $page) {
            $tables = $this->getPageTables($page);

            foreach ($tables as $tableName => $tableColumns) {
                $primaryColumn = $this->getTablePrimaryColumn($tableName);
                $columnData = $tableColumns[$primaryColumn];

                $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
                $result = $adapter->query('SELECT * FROM `' . $tableName . '` WHERE `' . $primaryColumn . '` = ?', [$columnData])->toArray();

                if (!empty($result)) {
                    $errors[] = 'Primary ID ' . $columnData . ' of table ' . $tableName . ' is already used.';
                }
            }
        }
    }

    private function getPageTables($page)
    {
        if (isset($page['page']['tables']))
            $tables = $page['page']['tables'];
        if (isset($page['tables']))
            $tables = $page['tables'];
        if (!isset($page['page']['tables']) && !isset($page['tables']))
            $tables = $page;

        return $tables;
    }

    private function getTablePrimaryColumn($tableName)
    {
        $constraints = $this->getTableConstraints($tableName);

        foreach ($constraints as $constraint) {
            if ($constraint->isPrimaryKey()) {
                $primaryColumn = $constraint->getColumns();

                if (is_array($primaryColumn)) {
                    $primaryColumn = $primaryColumn[0];
                }
            }
        }

        return !empty($primaryColumn) ? $primaryColumn : null;
    }






    private function checkAll($xml, &$arrr)
    {
        $test = clone $xml;

        if (isset($xml->page)) {
            $children = $test->page->children;
            unset($xml->page->children);
        }

//        if (isset($xml[0])) {
//            $children = $test[0]->children;
//            unset($xml[0]->children);
//        }

        if (isset($xml->children)) {
            $children = $test->children;
            unset($xml->children);
        }

        $arrr[] = clone $xml;

        if (!empty($children)) {
            foreach ($children as $key => $child) {
                if (count($child) > 1) {
                    foreach ($child as $x => $y) {
                        $this->checkAll($y, $arrr);
                    }
                } else {
                    $this->checkAll($child, $arrr);
                }
            }
        }
    }
}
