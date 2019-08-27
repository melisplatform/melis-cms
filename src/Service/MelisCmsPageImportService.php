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

    /**
     * Checks the xml of the zip file
     * @param $xmlString
     * @param bool $keepPrimaryId
     * @return mixed
     */
    public function importTest($xmlString, $keepPrimaryId = false)
    {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_test_file_start', $arrayParameters);

        $errors = [];
        $success = true;
        $xmlArray = $this->xmlToArray($arrayParameters['xmlString'], $errors);

        if ($xmlArray) {
            // TODO: check static tables not only the root page because there is a chance that it will not have all table
            $this->checkTables($xmlArray, $errors);

            if ($keepPrimaryId) {
                $pages = [];
                $this->getAllPagesRecursively($xmlArray, $pages);
                // TODO: check all page ids of pages for published and saved if there are no duplicate
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

        $simpleXml = simplexml_load_string($arrayParameters['xmlString']);
        $pages = [];
        $this->getAllPagesRecursivelyAsSimpleXml($simpleXml, $pages);
        $externalTables = $this->simpleXmlToArray($simpleXml->external->tables);
        $externalIdsMap = [];
        $fatherIdsMap = [];
        $pageIdsMap = [];
        $errors = [];

        $this->updateExternalTables($externalTables, $externalIdsMap);
        $this->updatePageExternalIds($pages, $externalIdsMap);

        $pages[0]->fatherId = $pageid;

        // create fatherId map
        foreach ($pages as $page) {
            $fatherId = (string) $page->fatherId;
            $fatherIdsMap[$fatherId] = $fatherId;
        }

        foreach ($pages as &$page) {
            $id = (string) $page->tables->melis_cms_page_published->page_id ?? (string) $page->tables->melis_cms_page_saved->page_id;
            $page->fatherId = $fatherIdsMap[(string) $page->fatherId];
            $pageId = $this->importPage($page->asXml());

            if (array_key_exists($id, $fatherIdsMap)) {
                $fatherIdsMap[$id] = $pageId;
            }
        }

        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_tree_end', $arrayParameters);
        // RETURN WILL BE ERRORS & STATUS
    }

    /**
     * Imports a page
     * @param $xml
     * @return page id
     */
    public function importPage($xmlString, $keepIds = null)
    {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_start', $arrayParameters);

        $simpleXml = simplexml_load_string($arrayParameters['xmlString']);
        $tables = $simpleXml->tables;
        $tablesArray = $this->simpleXmlToArray($tables);
        $fatherId = $this->simpleXmlToArray($simpleXml)['fatherId'];
        $pageId = 0;

        $this->reorderPageTables($tablesArray, $tables);
        $pageId = $this->savePage($tablesArray, $fatherId);

        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_end', $arrayParameters);
        return $pageId;
    }

    public function importPageResources()
    {

    }

    private function importExternal()
    {

    }

    private function importExternalTables()
    {

    }

    private function getCurrentPageId()
    {
        $corePlatformTbl = $this->getServiceLocator()->get('MelisCoreTablePlatform');
        $corePlatform = $corePlatformTbl->getEntryByField('plf_name', getenv('MELIS_PLATFORM'))->current();
        $corePlatformId = $corePlatform->plf_id;
        $cmsPlatformIdsTbl = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
        $pagePlatformIds = $cmsPlatformIdsTbl->getEntryById($corePlatformId)->current();
        return !empty($pagePlatformIds->pids_page_id_current) ? $pagePlatformIds->pids_page_id_current : null;
    }

    private function savePage($tablesArray, $fatherId, $keepIds = null)
    {
        $pageId = null;
        $pageCurrentId = $this->getCurrentPageId();

        $pageTree = array(
            'tree_father_page_id' => $fatherId
        );

        foreach ($tablesArray as $tableName => $tableValue) {
            if ($tableName === 'melis_cms_page_published') {
                if ($pageCurrentId != $tablesArray[$tableName]['page_id']) {
                    $pageId = $pageCurrentId;
                    $tablesArray[$tableName]['page_id'] = $pageId;
                }
            }
        }

        if (!empty($pageId)) {
            unset($tablesArray['melis_cms_page_lang']['plang_id']);
            $tablesArray['melis_cms_page_lang']['plang_page_id'] = $pageId;
            $tablesArray['melis_cms_page_lang']['plang_page_id_initial'] = $pageId;
        }

        // clean empty values
        foreach ($tablesArray as $tableName => $tableValue) {
            foreach ($tableValue as $column => $value) {
                if (empty($value)) {
                    unset($tablesArray[$tableName][$column]);
                }
            }
        }

        $pageSrv = $this->getServiceLocator()->get('MelisCmsPageService');
        $newPageId = $pageSrv->savePage(
            $pageTree,
            $tablesArray['melis_cms_page_published'] ?? [],
            $tablesArray['melis_cms_page_saved'] ?? [],
            $tablesArray['melis_cms_page_seo'] ?? [],
            $tablesArray['melis_cms_page_lang'] ?? [],
            $tablesArray['melis_cms_page_style'] ?? [],
            $pageId
        );

        return $newPageId;
    }

    /**
     * Updates external ids
     * @param array $externalTables
     */
    private function updateExternalTables($externalTables, &$externalIdsMap)
    {
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');

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
    }

    private function updatePageExternalIds(&$pages, &$externalIdsMap)
    {
        foreach ($pages as $page) {
            // check and update page ids
            if (! empty($page->melis_cms_page_lang->plang_id)) {
                if (array_key_exists($page->melis_cms_page_lang->plang_id, $externalIdsMap['melis_cms_lang'])) {
                    $page->melis_cms_page_lang->plang_id = $externalIdsMap['melis_cms_lang'][$page->melis_cms_page_lang->plang_id];
                }
            }

            // update published
            if (! empty($page->melis_cms_page_published->page_tpl_id)) {
                if (array_key_exists($page->melis_cms_page_published->page_tpl_id, $externalIdsMap['melis_cms_template'])) {
                    $page->melis_cms_page_published->page_tpl_id = $externalIdsMap['melis_cms_template'][$page->melis_cms_page_published->page_tpl_id];
                }
            }

            // update saved
            if (! empty($page->melis_cms_page_saved->page_tpl_id)) {
                if (array_key_exists($page->melis_cms_page_saved->page_tpl_id, $externalIdsMap['melis_cms_template'])) {
                    $page->melis_cms_page_saved->page_tpl_id = $externalIdsMap['melis_cms_template'][$page->melis_cms_page_saved->page_tpl_id];
                }
            }

            // update page style
            if (! empty($page->melis_cms_page_style->pstyle_style_id)) {
                if (array_key_exists($page->melis_cms_page_style->pstyle_style_id, $externalIdsMap['melis_cms_style'])) {
                    $page->melis_cms_page_style->pstyle_style_id = $externalIdsMap['melis_cms_style'][$page->melis_cms_page_style->pstyle_style_id];
                }
            }

            //update page seo
        }
    }

    private function reorderPageTables(&$tablesArray, $tables)
    {
        foreach ($tablesArray as $tableName => $tableValue) {
            $tablesArray[$tableName] = $tables->$tableName;

            if (!empty($tablesArray[$tableName]->page_content)) {
                $tablesArray[$tableName]->page_content = $tablesArray[$tableName]->page_content->asXml();
            }

            $tablesArray[$tableName] = $this->simpleXmlToArray($tablesArray[$tableName]);
        }

        $tablesArrayTmp = [];
        if (! empty($tablesArray['melis_cms_page_published'])) {
            $tablesArrayTmp['melis_cms_page_published'] = $tablesArray['melis_cms_page_published'];
        } if (! empty($tablesArray['melis_cms_page_saved'])) {
            $tablesArrayTmp['melis_cms_page_saved'] = $tablesArray['melis_cms_page_saved'];
        } if (! empty($tablesArray['melis_cms_page_lang'])) {
            $tablesArrayTmp['melis_cms_page_lang'] = $tablesArray['melis_cms_page_lang'];
        } if (! empty($tablesArray['melis_cms_page_style'])) {
            $tablesArrayTmp['melis_cms_page_style'] = $tablesArray['melis_cms_page_style'];
        } if (! empty($tablesArray['melis_cms_page_seo'])) {
            $tablesArrayTmp['melis_cms_page_seo'] = $tablesArray['melis_cms_page_seo'];
        }

        // add other tables
        foreach ($tablesArray as $tableName => $tableValue) {
            if (! array_key_exists($tableName, $tablesArrayTmp)) {
                $tablesArrayTmp[$tableName] = $tableValue;
            }
        }

        $tablesArray = $tablesArrayTmp;
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
