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

        if ($xmlArray) {
            $this->checkTables($xmlArray, $errors);

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

    /**
     * Iterates all pages of the tree (Main Function)
     * @param $pageid
     * @param $xml
     */
    public function importPageTree($pageid, $xmlString)
    {
        $externalTableMap = $this->getExternalTableMap();
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_tree_start', array_merge($arrayParameters, [ 'externalTableMap' => $externalTableMap ]));

        $simpleXml = simplexml_load_string($xmlString);
        $pages = [];
        $errors = [];
        $externalIdsMap = [];
        $this->getAllPagesRecursivelyAsSimpleXml($simpleXml, $pages);
        $externalTables = $this->simpleXmlToArray($simpleXml->external->tables);
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');

        foreach ($externalTables as $tableName => $tableRows) {
            foreach ($tableRows as $rowKey => $row) {
                if ($tableName == 'melis_cms_template') {
                    $queryString = 'SELECT * FROM `melis_cms_template` WHERE `tpl_site_id` = ? AND `tpl_zf2_layout` = ? AND `tpl_zf2_controller` = ? AND `tpl_zf2_action` = ?';
                    $result = $adapter->query($queryString, [$row['tpl_site_id'], $row['tpl_zf2_layout'], $row['tpl_zf2_controller'], $row['tpl_zf2_action']])->toArray();

                    if (!empty($result)) {
                        // Update array map
                        $externalIdsMap[$tableName][$row['tpl_id']] = $result[0]['tpl_id'];
                    } else {
                        // Insert and update array map
                        $queryString = 'INSERT INTO `melis_cms_template` () VALUES()';
                    }
                } else if ($tableName == 'melis_cms_style') {
                    $queryString = 'SELECT * FROM';
                } else if ($tableName == 'melis_cms_lang') {
                    $queryString = 'SELECT * FROM';
                }
            }
        }

        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_tree_end', $arrayParameters);
    }

    /**
     * Imports a page
     * @param $xml
     */
    public function importPage($xml)
    {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_start', $arrayParameters);


        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_end', $arrayParameters);
    }

    public function importPageRessources()
    {

    }

    private function getExternalTableMap() {
        return [
            'melis_cms_template' => [
                'tpl_site_id', 'tpl_zf2_layout', 'tpl_zf2_controller', 'tpl_zf2_action'
            ],
            'melis_cms_style' => [
                'style_path'
            ],
            'melis_cms_lang' => [
                'lang_cms_locale'
            ],
        ];
    }

    private function  importExternal()
    {

    }

    private function checkTables($xml, &$errors)
    {
        $dbTables = $this->getDbTableNames();
        $pageTables = $xml['page']['tables'];
        $externalTables = $xml['external']['tables'];

        $this->checkPageTables($pageTables, $dbTables, $errors);
        $this->checkExternalTables($externalTables, $dbTables, $errors);
    }

    private function checkPageTables($pageTables, $dbTables, &$errors)
    {
        foreach ($pageTables as $tableName => $tableColumns) {
            if (in_array($tableName, $dbTables)) {
                $this->checkTableColumns($tableName, $tableColumns, $errors);
            } else {
                $errors[] = $tableName . ' does not exist on your db';
            }
        }
    }

    private function checkExternalTables($externalTables, $dbTables, &$errors)
    {
        foreach ($externalTables as $externalTableName => $externalTableColumns) {
            if (in_array($externalTableName, $dbTables)) {
                $this->checkTableColumns($externalTableName, $externalTableColumns['row_0'], $errors);
            } else {
                $errors[] = $externalTableName . ' does not exist on your db';
            }
        }
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

    private function simpleXmlToArray($simpleXml)
    {
        return json_decode(json_encode($simpleXml), TRUE);
    }

    // TODO: separate function for preparing the xml
    private function getAllPagesRecursively($xmlArray, &$pages, $fatherId = null)
    {
        if (isset($xmlArray['page'])) {
            $xmlArray = $xmlArray['page'];
        }

        if (isset($xmlArray[0]))
            $xmlArray = $xmlArray[0];

        if (isset($xmlArray['children'])) {
            $children = $xmlArray['children'];
            unset($xmlArray['children']);
        }

        $xmlArray['fatherId'] = !empty($fatherId) ? $fatherId : -1;
        $pages[] = $xmlArray;

        $pageId = !empty($xmlArray['tables']['melis_cms_page_published']['page_id'])
            ? $xmlArray['tables']['melis_cms_page_published']['page_id']
            : $xmlArray['tables']['melis_cms_page_saved']['page_id'];

        if (!empty($children)) {
            foreach ($children as $key => $child) {
                if (count($children['page']) > 1) {
                    foreach ($child as $childKey => $childContent) {
                        $this->getAllPagesRecursively($childContent, $pages, $pageId);
                    }
                } else {
                    $this->getAllPagesRecursively($child, $pages, $pageId);
                }
            }
        }
    }

    private function getAllPagesRecursivelyAsSimpleXml($xmlArray, &$pages, $fatherId = null)
    {
        if (isset($xmlArray->page)) {
            $xmlArray = $xmlArray->page;
        }

        if (isset($xmlArray[0])) {
            $xmlArray = $xmlArray[0];
        }

        if (isset($xmlArray->children)) {
            $children = $xmlArray->children;
//            unset($clone->children);
        }

        $xmlArray->fatherId = !empty($fatherId) ? $fatherId : -1;
        $pages[] = $xmlArray;
        $pageId = !empty($xmlArray->tables->melis_cms_page_published->page_id)
            ? $xmlArray->tables->melis_cms_page_published->page_id
            : $xmlArray->tables->melis_cms_page_saved->page_id;

        if (!empty($children)) {
            foreach ($children as $key => $child) {
                if (count($children->page) > 1) {
                    foreach ($child as $x => $y) {
                        $this->getAllPagesRecursivelyAsSimpleXml($y, $pages, $pageId);
                    }
                } else {
                    $this->getAllPagesRecursivelyAsSimpleXml($child, $pages, $pageId);
                }
            }
        }
    }

    private function cleanPages(&$pages, &$cleanedPages)
    {
        foreach ($pages as $page) {
            $clone = clone $page;

            if (!empty($clone->children)) {
                unset($clone->children);
                $cleanedPages[] = $clone;
            }
        }
    }

    private function checkPageIds($pages, &$errors)
    {
        foreach ($pages as $page) {
            $tables = !empty($page['tables']) ? $page['tables'] : [];

            foreach ($tables as $tableName => $tableColumns) {
                if (is_array($tableColumns) && !empty($tableColumns)) {
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
