<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service;

use Laminas\Db\Metadata\Metadata;
use Laminas\Db\TableGateway\TableGateway;
use MelisCore\Service\MelisGeneralService;
use ZipArchive;

class MelisCmsPageImportService extends MelisGeneralService
{
    protected $keepIds = false;
    protected $defaultTableList = [
        'melis_cms_page_published',
        'melis_cms_page_saved',
        'melis_cms_page_lang',
        'melis_cms_page_style',
        'melis_cms_page_seo'
    ];
    protected $xmlString;

    /**
     * Checks the content of the xml. tables, columns, ids
     * @param $xmlString
     * @param bool $keepPrimaryId
     * @return mixed
     */
    public function importTest($xmlString, $keepPrimaryId = false)
    {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_test_file_start', $arrayParameters);
        $this->xmlString = $xmlString;

        $errors = [];
        $success = true;
        $xmlArray = $this->xmlToArray($arrayParameters['xmlString'], $errors);

        if ($xmlArray) {
            $this->check($xmlArray, $arrayParameters['keepPrimaryId'], $errors);
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
    public function importPageTree($pageid, $xmlString, $keepIds = null, $xmlPath = null)
    {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_tree_start', $arrayParameters);

        $simpleXml = simplexml_load_string($arrayParameters['xmlString']);
        $pages = [];
        $this->getAllPagesRecursivelyAsSimpleXml($simpleXml, $pages);
        $externalTables = $this->simpleXmlToArray($simpleXml->external->tables);
        $idsMap = [];
        $externalIdsMap = [];
        $fatherIdsMap = [];
        $pagesIdMap = [];
        $errors = [];
        $success = true;
        $firstPage = 0;

        $db = $this->getServiceManager()->get('Laminas\Db\Adapter\Adapter');
        $con = $db->getDriver()->getConnection();
        $con->beginTransaction();

        try {
            $this->updateExternalTables($externalTables, $externalIdsMap);
            $this->updatePageExternalIds($pages, $externalIdsMap);


            $pages[0]->fatherId = $arrayParameters['pageid'];

            // create fatherId map
            foreach ($pages as $page) {
                $fatherId = (string) $page->fatherId;
                $fatherIdsMap[$fatherId] = $fatherId;
            }

            foreach ($pages as &$page) {
                $id = (string) $page->tables->melis_cms_page_published->page_id;

                if (empty ($id)) {
                    $id = (string) $page->tables->melis_cms_page_saved->page_id;
                }

                $page->fatherId = $fatherIdsMap[(string)$page->fatherId];
                $pageId = $this->importPage($page->asXml(), $arrayParameters['keepIds']);

                if (empty ($firstPage))
                    $firstPage = $pageId;

                $pagesIdMap[$id] = $pageId;

                if (!$arrayParameters['keepIds']) {
                    if (array_key_exists($id, $fatherIdsMap)) {
                        $fatherIdsMap[$id] = $pageId;
                    }
                }

                if (empty($pageId)) {
                    $success = false;
                }
            }

            $idsMap = $externalIdsMap;
            $idsMap['page_ids'] = $pagesIdMap;

            // clean data. remove ids that were not updated
            foreach ($idsMap as $key => $value) {
                foreach ($value as $oldId => $newId) {
                    if ($oldId == $newId) {
                        unset($idsMap[$key][$oldId]);
                    }
                }

                if (empty($idsMap[$key])) {
                    unset($idsMap[$key]);
                }
            }

            if ($arrayParameters['xmlPath']) {
                $this->importPageResources($arrayParameters['xmlPath']);
            }

            $con->commit();
        } catch (\Exception $ex) {
            $success = false;
            $errors[] = $ex->getMessage();
            $con->rollback();
        }

        $arrayParameters['result'] = [
            'success' => $success,
            'errors' => $errors,
            'pagesCount' => count($pages),
            'idsMap' => $idsMap,
            'firstPage' => $firstPage
        ];

        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_tree_end', $arrayParameters);

        return $arrayParameters['result'];
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

        $this->reorderPageTables($tablesArray, $tables);
        $pageId = $this->savePage($tablesArray, $fatherId, $arrayParameters['keepIds']);
        $arrayParameters['result'] = $pageId;

        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_end', $arrayParameters);
        return $arrayParameters['result'];
    }

    public function importPageResources($xmlPath)
    {
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_resources_start', $arrayParameters);

        $zip = new ZipArchive;
        $res = $zip->open($arrayParameters['xmlPath']);
        $result = true;

        if ($res === true) {
            $zip->extractTo($_SERVER['DOCUMENT_ROOT'] . '/xml');
            $zip->close();

            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/xml/media')) {
                $this->recurse_copy($_SERVER['DOCUMENT_ROOT'] . '/xml/media', $_SERVER['DOCUMENT_ROOT'] . '/media');
                $this->deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/xml');
            }
        } else {
            $result = false;
        }

        $arrayParameters['result'] = $result;
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_import_import_page_resources_end', $arrayParameters);

        return $arrayParameters['result'];
    }

    private function getCurrentPageId()
    {
        $corePlatformTbl = $this->getServiceManager()->get('MelisCoreTablePlatform');
        $corePlatform = $corePlatformTbl->getEntryByField('plf_name', getenv('MELIS_PLATFORM'))->current();
        $corePlatformId = $corePlatform->plf_id;
        $cmsPlatformIdsTbl = $this->getServiceManager()->get('MelisEngineTablePlatformIds');
        $pagePlatformIds = $cmsPlatformIdsTbl->getEntryById($corePlatformId)->current();
        return !empty($pagePlatformIds->pids_page_id_current) ? $pagePlatformIds->pids_page_id_current : null;
    }

    private function savePage($tablesArray, $fatherId, $keepIds = null)
    {
        $adapter = $this->getServiceManager()->get('Laminas\Db\Adapter\Adapter');
        $pageTreeTbl = $this->getServiceManager()->get('MelisEngineTablePageTree');
        $pageTree = array(
            'tree_father_page_id' => $fatherId
        );

        if ($keepIds) {
            $corePlatformTbl = $this->getServiceManager()->get('MelisCoreTablePlatform');
            $corePlatform = $corePlatformTbl->getEntryByField('plf_name', getenv('MELIS_PLATFORM'))->current();
            $corePlatformId = $corePlatform->plf_id;
            $cmsPlatformIdsTbl = $this->getServiceManager()->get('MelisEngineTablePlatformIds');

            $pageId = $tablesArray['melis_cms_page_published']['page_id'] ?? $tablesArray['melis_cms_page_saved']['page_id'];
            $nextPageId = $pageId + 1;

            if ($fatherId != -1)
            {
                $pageParentPages = $pageTreeTbl->getEntryByField('tree_father_page_id', $pageTree['tree_father_page_id'])->count() + 1;
                $pageTree['tree_page_order'] = ($pageParentPages) ? $pageParentPages : 1;
            } else {
                $pageTree['tree_father_page_id'] = -1;
                $pageTree['tree_page_order'] = 1;
            }

            $pageTree['tree_page_id'] = $pageId;

            // clean empty values
            foreach ($tablesArray as $tableName => $tableValue) {
                foreach ($tableValue as $column => $value) {
                    if (is_string($value) && strlen($value) == 0) {
                        unset($tablesArray[$tableName][$column]);
                    }

                    if (is_array($value) && empty($value)) {
                        unset($tablesArray[$tableName][$column]);
                    }
                }
            }

            $pageSrv = $this->getServiceManager()->get('MelisCmsPageService');
            $pageTreeTbl->save($pageTree);
            $cmsPlatformIdsTbl->save(array('pids_page_id_current' => $nextPageId), $corePlatformId);

            if (!empty($tablesArray['melis_cms_page_published']))
                $pageSrv->savePagePublished($tablesArray['melis_cms_page_published']);

            if (!empty($tablesArray['melis_cms_page_saved']))
                $pageSrv->savePageSaved($tablesArray['melis_cms_page_saved']);

            if (!empty($tablesArray['melis_cms_page_seo']))
                $pageSrv->savePageSeo($tablesArray['melis_cms_page_seo']);

            if (!empty($tablesArray['melis_cms_page_lang']))
                $pageSrv->savePageLang($tablesArray['melis_cms_page_lang']);

            if (!empty($tablesArray['melis_cms_page_style']))
                $pageSrv->savePageStyle($tablesArray['melis_cms_page_style']);

            foreach ($tablesArray as $tableName => $tableValue) {
                if (!in_array($tableName, $this->getDefaultTableList())) {
                    $table = new TableGateway($tableName, $adapter);
                    $table->insert($tablesArray[$tableName]);
                }
            }

            return $pageId;
        } else {
            $pageId = $this->getCurrentPageId();

            if (!empty ($tablesArray['melis_cms_page_published'])) {
                $tablesArray['melis_cms_page_published']['page_id'] = $pageId;
            }

            if (!empty ($tablesArray['melis_cms_page_saved'])) {
                $tablesArray['melis_cms_page_saved']['page_id'] = $pageId;
            }

            if (!empty ($tablesArray['melis_cms_page_seo'])) {
                $tablesArray['melis_cms_page_seo']['pseo_id'] = $pageId;
            }

            if (!empty($tablesArray['melis_cms_page_style'])) {
                unset($tablesArray['melis_cms_page_style']['pstyle_id']);
            }

            if (!empty($pageId)) {
                unset($tablesArray['melis_cms_page_lang']['plang_id']);
                $tablesArray['melis_cms_page_lang']['plang_page_id'] = $pageId;
                $tablesArray['melis_cms_page_lang']['plang_page_id_initial'] = $pageId;
            }

            // clean empty values
            foreach ($tablesArray as $tableName => $tableValue) {
                foreach ($tableValue as $column => $value) {
                    if (is_string($value) && strlen($value) == 0) {
                        unset($tablesArray[$tableName][$column]);
                    }

                    if (is_array($value) && empty($value)) {
                        unset($tablesArray[$tableName][$column]);
                    }
                }
            }

            $pageSrv = $this->getServiceManager()->get('MelisCmsPageService');
            $pageSrv->savePage(
                $pageTree,
                $tablesArray['melis_cms_page_published'] ?? [],
                $tablesArray['melis_cms_page_saved'] ?? [],
                $tablesArray['melis_cms_page_seo'] ?? [],
                $tablesArray['melis_cms_page_lang'] ?? [],
                $tablesArray['melis_cms_page_style'] ?? [],
                null
            );

            foreach ($tablesArray as $tableName => $tableValue) {
                if (!in_array($tableName, $this->getDefaultTableList())) {
                    foreach ($tableValue as $column => $value) {
                        if (strpos($column, 'page_id')) {
                            $tablesArray[$tableName][$column] = $pageId;
                        }
                    }

                    $table = new TableGateway($tableName, $adapter);
                    $table->insert($tablesArray[$tableName]);
                }
            }

            return $pageId;
        }
    }

    /**
     * Updates the external tables
     * @param $externalTables
     * @param $externalIdsMap
     */
    private function updateExternalTables($externalTables, &$externalIdsMap)
    {
        $adapter = $this->getServiceManager()->get('Laminas\Db\Adapter\Adapter');

        foreach ($externalTables as $tableName => $tableRows) {
            foreach ($tableRows as $rowKey => $row) {
                if ($tableName === 'melis_cms_template') {
                    foreach ($row as $columnName => $columnValue) {
                        if (empty($columnValue)) {
                            unset($row[$columnName]);
                        }
                    }

                    $corePlatformTbl = $this->getServiceManager()->get('MelisCoreTablePlatform');
                    $corePlatform = $corePlatformTbl->getEntryByField('plf_name', getenv('MELIS_PLATFORM'))->current();
                    $corePlatformId = $corePlatform->plf_id;
                    $cmsPlatformIdsTbl = $this->getServiceManager()->get('MelisEngineTablePlatformIds');
                    $platformIds = $cmsPlatformIdsTbl->getEntryById($corePlatformId)->current();
                    $currentTempId = $platformIds->pids_tpl_id_current;

                    $data = $row;
                    $data['tpl_id'] = $currentTempId;

                    $templateTbl = $this->getServiceManager()->get('MelisEngineTableTemplate');
                    $queryString = 'SELECT * FROM `melis_cms_template` WHERE `tpl_site_id` = ? AND `tpl_zf2_layout` = ? AND `tpl_zf2_controller` = ? AND `tpl_zf2_action` = ?';
                    $result = $adapter->query($queryString, [$row['tpl_site_id'], $row['tpl_zf2_layout'], $row['tpl_zf2_controller'], $row['tpl_zf2_action']])->toArray();

                    if (!empty($result)) {
                        $externalIdsMap[$tableName][$row['tpl_id']] = $result[0]['tpl_id'];
                    } else {
                        $templateTbl->save($data);
                        $cmsPlatformIdsTbl->save(array('pids_tpl_id_current' => ++$currentTempId), $corePlatformId);
                        $externalIdsMap[$tableName][$row['tpl_id']] = $data['tpl_id'];
                    }
                } else if ($tableName === 'melis_cms_style') {
                    $data = $row;
                    unset($data['style_id']);
                    $styleTbl = $this->getServiceManager()->get('MelisEngineTableStyle');
                    $style = $styleTbl->getEntryByField('style_path', $row['style_path'])->current();

                    if (!empty($style)) {
                        $externalIdsMap[$tableName][$row['style_id']] = $style->style_id;
                    } else {
                        $styleId = $styleTbl->save($data);
                        $externalIdsMap[$tableName][$row['style_id']] = $styleId;
                    }
                } else if ($tableName === 'melis_cms_lang') {
                    $data = $row;
                    unset($data['lang_cms_id']);
                    $langTbl = $this->getServiceManager()->get('MelisEngineTableCmsLang');
                    $lang = $langTbl->getEntryByField('lang_cms_locale', $row['lang_cms_locale'])->current();

                    if (! empty($lang)) {
                        $externalIdsMap[$tableName][$row['lang_cms_id']] = $lang->lang_cms_id;
                    } else {
                        $langId = $langTbl->save($data);
                        $externalIdsMap[$tableName][$row['lang_cms_id']] = $langId;
                    }
                } else {
                    $data = $row;
                    $table = new TableGateway($tableName, $adapter);
                    $table->insert($data);
                }
            }
        }
    }

    /**
     * Updates the external ids for the pages
     * @param $pages
     * @param $externalIdsMap
     */
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

    /**
     * Reorders the list of the page tables
     * @param $tablesArray
     * @param $tables
     */
    private function reorderPageTables(&$tablesArray, $tables)
    {
        foreach ($tablesArray as $tableName => $tableValue) {
            $tablesArray[$tableName] = $tables->$tableName;

            if (!empty($tablesArray[$tableName]->page_content)) {
                $domtree = new \DOMDocument('1.0', 'UTF-8');
                $domtree->loadXML((string) $tablesArray[$tableName]->page_content);
                $tablesArray[$tableName]->page_content = $domtree->saveXML();
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

    /**
     * Checks the contents of the xml
     * @param $xml
     * @param $keepIds
     * @param $errors
     */
    private function check($xml, $keepIds, &$errors)
    {
        $dbTables = $this->getDbTableNames();
        $externalTables = $xml['external']['tables'];

        $pages = [];
        $this->getAllPagesRecursively($xml, $pages);
        $this->checkAllPageTables($pages, $dbTables, $keepIds, $errors);
        $this->checkExternalTables($externalTables, $dbTables, $errors);
    }

    /**
     * @param $pages
     * @param $dbTables
     * @param $keepIds
     * @param $errors
     */
    private function checkAllPageTables($pages, $dbTables, $keepIds, &$errors)
    {
        $translator = $this->getServiceManager()->get('translator');

        foreach ($pages as $page) {
            $tables = !empty($page['tables']) ? $page['tables'] : [];

            foreach ($tables as $tableName => $columns) {
                if (is_array($columns) && !empty($columns)) {
                    if ($this->checkTable($tableName, $dbTables)) {
                        $this->checkTableColumns($tableName, $columns, $errors);

                        if ($keepIds) {
                            $this->checkIds($tableName, $columns, $errors);
                        }
                    } else {
                        $column = array_keys($columns)[0];
                        $lineNo = $this->getXmlLineNumber($this->xmlString, $tableName, $columns[$column]);
                        $lineText = $translator->translate('tr_melis_cms_page_tree_import_line') . ' ' . $lineNo . ': ';
                        $errors[] = $lineText . sprintf(
                            $translator->translate('tr_melis_cms_page_tree_import_table_does_not_exist'),
                            $tableName
                        );
                    }
                }
            }
        }
    }

    /**
     * Will check if the table is existing in the database
     * @param $tableName
     * @param $dbTables
     * @return bool
     */
    private function checkTable($tableName, $dbTables)
    {
        if (in_array($tableName, $dbTables))
            return true;

        return false;
    }

    /**
     * Checks if the
     * @param $externalTables
     * @param $dbTables
     * @param $errors
     */
    private function checkExternalTables($externalTables, $dbTables, &$errors)
    {
        $translator = $this->getServiceManager()->get('translator');

        foreach ($externalTables as $externalTableName => $externalTableColumns) {
            if (in_array($externalTableName, $dbTables)) {
                $this->checkTableColumns($externalTableName, $externalTableColumns['row_0'], $errors);
            } else {
                $column = array_keys($externalTableColumns)[0];
                $lineNo = $this->getXmlLineNumber($this->xmlString, $externalTables, $externalTableColumns[$column]);
                $lineText = $translator->translate('tr_melis_cms_page_tree_import_line') . ' ' . $lineNo . ': ';
                $errors[] = $lineText . sprintf(
                    $translator->translate('tr_melis_cms_page_tree_import_table_does_not_exist'),
                    $externalTableName
                );
            }
        }
    }

    /**
     * Checks for the columns of the table that is listed in the xml is existing on the database
     * @param $tableName
     * @param $tableColumns
     * @param $errors
     */
    private function checkTableColumns($tableName, $tableColumns, &$errors)
    {
        $translator = $this->getServiceManager()->get('translator');
        $columns = $this->getTableColumns($tableName);

        foreach ($tableColumns as $columnName => $columnValue) {
            if (in_array($columnName, $columns)) {

            } else {
                $errors[] = sprintf(
                    $translator->translate('tr_melis_cms_page_tree_import_column_does_not_exist'),
                    $columnName,
                    $tableName
                );
            }
        }
    }

    /**
     * Checks for the entries on the xml if it is already existing in the database
     * @param $tableName
     * @param $columns
     * @param $errors
     */
    private function checkIds($tableName, $columns, &$errors)
    {
        $adapter = $this->getServiceManager()->get('Laminas\Db\Adapter\Adapter');
        $translator = $this->getServiceManager()->get('translator');

        $tableGateway = new TableGateway($tableName, $adapter);
        $primaryColumn = $this->getTablePrimaryColumn($tableName);
        $result = $tableGateway->select([$primaryColumn => $columns[$primaryColumn]])->current();

        if (! empty($result)) {
            $lineNo = $this->getXmlLineNumber($this->xmlString, $tableName, $columns[$primaryColumn], $primaryColumn);
            $lineText = $translator->translate('tr_melis_cms_page_tree_import_line') . ' ' . $lineNo . ': ';
            $errors[] = $lineText . sprintf(
                $translator->translate('tr_melis_cms_page_tree_import_primary_already_used'),
                $columns[$primaryColumn],
                $tableName
            );
        }

    }

    /**
     * Returns all the tables from the database
     * @return string[]
     */
    private function getDbTableNames()
    {
        $adapter = $this->getServiceManager()->get('Laminas\Db\Adapter\Adapter');
        $metadata = new Metadata($adapter);
        return $metadata->getTableNames();
    }

    /**
     * Returns the columns of a table
     * @param $tableName
     * @return string[]
     */
    private function getTableColumns($tableName)
    {
        $adapter = $this->getServiceManager()->get('Laminas\Db\Adapter\Adapter');
        $metadata = new Metadata($adapter);
        return $metadata->getColumnNames($tableName);
    }

    /**
     * Returns constraints of a table
     * @param $tableName
     * @return \Laminas\Db\Metadata\Object\ConstraintObject[]
     */
    private function getTableConstraints($tableName)
    {
        $adapter = $this->getServiceManager()->get('Laminas\Db\Adapter\Adapter');
        $metadata = new Metadata($adapter);
        return $metadata->getConstraints($tableName);
    }

    /**
     * Returns the primary column of a table
     * @param $tableName
     * @return mixed|null
     */
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
        if ($xml !== FALSE) {
            $json = json_encode($xml);
            return json_decode($json, TRUE);
        } else {
            $errors[] = 'Invalid XML structure';
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

    /**
     * Will recursively get all pages and list them from top to bottom
     * @param $xmlArray
     * @param $pages
     * @param null $fatherId
     */
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

        if (!empty ($xmlArray['tables'])) {
            $pageId = !empty($xmlArray['tables']['melis_cms_page_published']['page_id'])
                ? $xmlArray['tables']['melis_cms_page_published']['page_id']
                : $xmlArray['tables']['melis_cms_page_saved']['page_id'];
        } else {
            $pageId = !empty($xmlArray['melis_cms_page_published']['page_id'])
                ? $xmlArray['melis_cms_page_published']['page_id']
                : $xmlArray['melis_cms_page_saved']['page_id'];
        }

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

    /**
     * Will recursively get all pages and list them from top to bottom
     * @param $xmlArray
     * @param $pages
     * @param null $fatherId
     */
    private function getAllPagesRecursivelyAsSimpleXml($xmlArray, &$pages, $fatherId = null)
    {
        if (isset($xmlArray->page)) {
            $xmlArray = $xmlArray->page;
        }

        if (isset($xmlArray[0])) {
            $xmlArray = $xmlArray[0];
        }

        if (isset($xmlArray->children) && ! empty($xmlArray->children)) {
            $children = $xmlArray->children;
        }

        $xmlArray->fatherId = !empty($fatherId) ? $fatherId : -1;
        $pages[] = $xmlArray;

        if (!empty ($xmlArray->tables)) {
            $pageId = !empty($xmlArray->tables->melis_cms_page_published->page_id)
                ? $xmlArray->tables->melis_cms_page_published->page_id
                : $xmlArray->tables->melis_cms_page_saved->page_id;
        } else {
            $pageId = !empty($xmlArray->melis_cms_page_published->page_id)
                ? $xmlArray->melis_cms_page_published->page_id
                : $xmlArray->melis_cms_page_saved->page_id;
        }

        if (isset($children)) {
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
     * Returns list of default tables
     * @return array
     */
    private function getDefaultTableList()
    {
        return $this->defaultTableList;
    }

    /**
     * Will delete a directory recursively
     * @param $dir
     * @return bool
     */
    public function deleteDirectory($dir) {
        if (! file_exists($dir))
            return true;

        if (! is_dir($dir))
            return unlink($dir);

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..')
                continue;

            if (! $this->deleteDirectory($dir . '/' . $item))
                return false;
        }

        return rmdir($dir);
    }

    /**
     * Will copy a directory and it's contents
     * @param $src
     * @param $dst
     */
    public function recurse_copy($src, $dst) {
        $dir = opendir($src);

        if (! file_exists($dst))
            mkdir($dst);

        while (false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                } else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    /**
     * Returns the line number on which the string is located in the xml
     * @param $xmlString
     * @param $key
     * @param $search
     * @param null $additionalKey
     * @return int
     */
    private function getXmlLineNumber($xmlString, $key, $search, $additionalKey = null)
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xmlString);
        $xpath = new \DOMXPath($doc);

        if ($additionalKey) {
            $expression = sprintf("//%s//%s[contains(., %s)]", $key, $additionalKey, $search);
        } else {
            $expression = sprintf("//%s[contains(., %s)]", $key, $search);
        }

        $item = $xpath->evaluate($expression);
        $lineNo = 0;

        if (! empty($item)) {
            $lineNo = $item->item(0)->getLineNo();
        }

        return $lineNo;
    }
}
