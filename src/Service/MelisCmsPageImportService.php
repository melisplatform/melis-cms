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
use Zend\Db\TableGateway\TableGateway;

class MelisCmsPageImportService extends MelisCoreGeneralService
{
    protected $logs = [];
    protected $keepIds = false;

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
    public function importPageTree($pageid, $xmlString, $keepIds = null)
    {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_tree_start', $arrayParameters);

        $this->keepIds = $keepIds;
        $simpleXml = simplexml_load_string($arrayParameters['xmlString']);
        $pages = [];
        $errors = [];
        $externalIdsMap = [];
        $this->getAllPagesRecursivelyAsSimpleXml($simpleXml, $pages);
        $externalTables = $this->simpleXmlToArray($simpleXml->external->tables);
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');

        $fatherIds = [];

        // add page order. needed for the page tree
        foreach ($pages as $page) {
            $fatherId = (int) $page->fatherId;

            if (! array_key_exists($fatherId, $fatherIds)) {
                $fatherIds[$fatherId] = 1;
            } else {
                $fatherIds[$fatherId] = $fatherIds[$fatherId] + 1;
            }

            $page->order = $fatherIds[$fatherId];
        }

        // update external table ids
        foreach ($externalTables as $tableName => $tableRows) {
            foreach ($tableRows as $rowKey => $row) {
                if ($tableName === 'melis_cms_template') {
                    foreach ($row as $columnName => $columnValue) {
                        if (empty($columnValue)) {
                            unset($row[$columnName]);
                        }
                    }

                    $templateTbl = $this->getServiceLocator()->get('MelisEngineTableTemplate');
                    $queryString = 'SELECT * FROM `melis_cms_template` WHERE `tpl_site_id` = ? AND `tpl_zf2_layout` = ? AND `tpl_zf2_controller` = ? AND `tpl_zf2_action` = ?';
                    $result = $adapter->query($queryString, [$row['tpl_site_id'], $row['tpl_zf2_layout'], $row['tpl_zf2_controller'], $row['tpl_zf2_action']])->toArray();

                    if (!empty($result)) {
                        $externalIdsMap[$tableName][$row['tpl_id']] = $result[0]['tpl_id'];
                    } else {
                        $templateId = $templateTbl->save($row);
                        $externalIdsMap[$tableName][$row['tpl_id']] = $row['tpl_id'];
                    }
                } if ($tableName === 'melis_cms_style') {
                    $data = $row;
                    unset($data['style_id']);
                    $styleTbl = $this->getServiceLocator()->get('MelisEngineTableStyle');
                    $style = $styleTbl->getEntryByField('style_path')->current();

                    if (!empty($style)) {
                        $externalIdsMap[$tableName][$row['style_id']] = $style->style_id;
                    } else {
                        $styleId = $styleTbl->save($data, $row['style_id']);
                        $externalIdsMap[$tableName][$row['style_id']] = $styleId;
                    }
                } if ($tableName === 'melis_cms_lang') {
                    $data = $row;
                    unset($data['lang_cms_id']);
                    $langTbl = $this->getServiceLocator()->get('MelisEngineTableCmsLang');
                    $lang = $langTbl->getEntryByField('lang_cms_locale', $row['lang_cms_locale'])->current();

                    if (! empty($lang)) {
                        $externalIdsMap[$tableName][$row['lang_cms_id']] = $lang->lang_cms_id;
                    } else {
                        $langId = $langTbl->save($data, $row['lang_cms_id']);
                        $externalIdsMap[$tableName][$row['lang_cms_id']] = $langId;
                    }
                }
            }
        }

        // update external ids on page
        foreach ($pages as $page) {
            // update page lang
            if (! empty($page->melis_cms_page_lang->plang_id)) {
                if (array_key_exists($page->melis_cms_page_lang->plang_id, $externalIdsMap['melis_cms_lang'])) {
                    $page->melis_cms_page_lang->plang_id = $externalIdsMap['melis_cms_lang'][$page->melis_cms_page_lang->plang_id];
                }
            }

            // update published
            if (! empty($page->melis_cms_page_published->page_tpl_id)) {
                if (array_key_exists($page->melis_cms_page_published->page_tpl_id, $externalIdsMap['melis_cms_template'])) {
                    $page->melis_cms_page_published->page_tpl_id = $externalTables['melis_cms_template'][$page->melis_cms_page_published->page_tpl_id];
                }
            }

            // update saved
            if (! empty($page->melis_cms_page_saved->page_tpl_id)) {
                if (array_key_exists($page->melis_cms_page_saved->page_tpl_id, $externalIdsMap['melis_cms_template'])) {
                    $page->melis_cms_page_saved->page_tpl_id = $externalTables['melis_cms_template'][$page->melis_cms_page_saved->page_tpl_id];
                }
            }

            // update page style
            if (! empty($page->melis_cms_page_style->pstyle_style_id)) {
                if (array_key_exists($page->melis_cms_page_style->pstyle_style_id, $externalIdsMap['melis_cms_style'])) {
                    $page->melis_cms_page_style->pstyle_style_id = $externalTables['melis_cms_style'][$page->melis_cms_page_style->pstyle_style_id];
                }
            }

            //update page seo

            // UPDATE EVERYTHING HERE
            $page->fatherId = $pageid;
            $page->order = 2;

            $this->importPage($page->asXml());
        }

        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_tree_end', $arrayParameters);
    }

    /**
     * Imports a page
     * @param $xml
     * @return page id
     */
    public function importPage($xmlString)
    {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_start', $arrayParameters);

        $simpleXml = simplexml_load_string($arrayParameters['xmlString']);
        $tables = $simpleXml->tables;
        $tablesArray = $this->simpleXmlToArray($tables);
        $fatherId = $simpleXml->fatherId;

        foreach ($tablesArray as $tableName => $tableValue) {
            $tablesArray[$tableName] = $tables->$tableName;

            if (!empty($tablesArray[$tableName]->page_content)) {
                $tablesArray[$tableName]->page_content = $tablesArray[$tableName]->page_content->asXml();
            }

            $tablesArray[$tableName] = $this->simpleXmlToArray($tablesArray[$tableName]);
        }

        foreach ($tablesArray as $tableName => $tableValue) {
            $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
            $tableGateway = new TableGateway($tableName, $adapter);
            $primaryCol = $this->getTablePrimaryColumn($tableName);
            $entry = $tableGateway->select([$primaryCol => $tableValue[$primaryCol]])->toArray();

            if ($tableName == 'melis_cms_page_published') {

            } else if ($tableName == 'melis_cms_page_saved') {

            } else if ($tableName == 'melis_cms_page_lang') {

            } else if ($tableName == 'melis_cms_page_seo') {

            } else if ($tableName == 'melis_cms_page_style') {

            }

            print_r($tableValue[$primaryCol]);
            exit;

            try {
                $tableGateway->insert($tableValue);
            } catch (\Exception $ex) {
                print_r($ex->getMessage());
                exit;
            }
        }

        // save page tree
//        $pageTreeTbl = $this->getServiceLocator()->get('MelisEngineTableP ageTree');
//        $pageTreeTbl->save([
//            'tree_page_id' => $tablesArray['melis_cms_page_published']['page_id'] || $tab,
//            'tree_father_page_id',
//            'tree_page_order'
//        ]);


//        foreach ($tables as $value) {
//            print_r($value);
//            exit;
//
//            $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
//            $tableGateway = new TableGateway($name, $adapter);
//
//            print_r($tableGateway->select());
//            exit;
//        }

        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_end', $arrayParameters);
    }

    public function importPageResources()
    {

    }

    private function getExternalTableMap()
    {
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

    private function importExternalTables()
    {

    }

    private function checkTables($xml, &$errors)
    {
        $this->addLog('checking tables');

        $dbTables = $this->getDbTableNames();
        $pageTables = $xml['page']['tables'];
        $externalTables = $xml['external']['tables'];

        $this->checkPageTables($pageTables, $dbTables, $errors);
        $this->checkExternalTables($externalTables, $dbTables, $errors);
    }

    private function checkPageTables($pageTables, $dbTables, &$errors)
    {
        $this->addLog('checking page tables');
        foreach ($pageTables as $tableName => $tableColumns) {
            if (in_array($tableName, $dbTables)) {
                $this->addLog($tableName . ' is existing in the db');
                $this->checkTableColumns($tableName, $tableColumns, $errors);
            } else {
                $this->addLog($tableName . ' does not exist in the db!!!');
                $errors[] = $tableName . ' does not exist on your db';
            }
        }
    }

    private function checkExternalTables($externalTables, $dbTables, &$errors)
    {
        $this->addLog('checking external tables');
        foreach ($externalTables as $externalTableName => $externalTableColumns) {
            if (in_array($externalTableName, $dbTables)) {
                $this->addLog($externalTableName . ' is exists in the db');
                $this->checkTableColumns($externalTableName, $externalTableColumns['row_0'], $errors);
            } else {
                $this->addLog($externalTableName . ' does not exist in the db');
                $errors[] = $externalTableName . ' does not exist on your db';
            }
        }
    }

    private function checkTableColumns($tableName, $tableColumns, &$errors)
    {
        $this->addLog('checking ' . $tableName . ' columns');
        $columns = $this->getTableColumns($tableName);

        foreach ($tableColumns as $columnName => $columnValue) {
            if (!in_array($columnName, $columns)) {
                $this->addLog($columnName . ' does not exist in the ' . $tableName . '!!!');
                $errors[] = $columnName . ' does not exist on table ' . $tableName;
            } else {
                $this->addLog($columnName . ' exists in the ' . $tableName);
            }
        }
    }

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

    /**
     * Converts Simple Xml to Array
     * @param $simpleXml
     * @return mixed
     */
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

    /**
     * Checks ids for page
     * @param $pages
     * @param $errors
     */
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

    public function addLog($log)
    {
        $this->logs[] = $log;
    }

    public function getLogs()
    {
        return $this->logs;
    }
}
