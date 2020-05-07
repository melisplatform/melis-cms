<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Metadata\Metadata;

class MelisCmsPageExportService extends MelisCoreGeneralService
{
    /**
     * Default required table list to export page
     * @var array
     */
    protected $defaultTableList = [  //table name service              table page id field name
                                    'MelisEngineTablePageLang'      => 'plang_page_id',
                                    'MelisEngineTablePagePublished' => 'page_id',
                                    'MelisEngineTablePageSaved'     => 'page_id',
                                    'MelisEngineTablePageSeo'       => 'pseo_id',
                                    'MelisEngineTablePageStyle'     => 'pstyle_page_id'
                                  ];

    /**
     *Default external tables needed to export page
     * @var array
     */
    protected $defaultExternalTables = [
                                            'MelisEngineTableStyle',
                                            'MelisEngineTableTemplate',
                                            'MelisEngineTableCmsLang'
                                       ];

    protected $defaultExternalTablesList = [
        'melis_cms_template' => 'templates',
        'melis_cms_style' => 'styles',
        'melis_cms_lang' => 'langs'
    ];

    /**
     * Function to export page
     *
     * @param $pageId
     * @param bool $includeSubPages
     * @param bool $exportPageResources
     * @return mixed
     */
    public function exportPageTree($pageId, $includeSubPages = false, $exportPageResources = false)
    {
        ini_set('memory_limit', '-1');
        $result = [
            'success' => false,
            'raw_err_message' => '',
            'xml' => '',
            'resources' => [],
        ];
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        // Sending service start event
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_export_start', $arrayParameters);

        $pages = [];
        $templates = [];
        $styles = [];
        $langs = [];
        $pageCount = 0;

        $this->getAllPageIds(
            $arrayParameters['pageId'],
            $pages,
            $templates,
            $styles,
            $langs,
            $pageCount,
            $arrayParameters['includeSubPages']
        );

        try {

            /**
             * prepare the root xml
             */
            $rootNode = $this->createXmlNode('xml');
            /**
             * prepare the page xml node
             */
            $pageNode = $this->createXmlNode('page');
            /**
             * get main page tables xml
             */
            $pageSource = new \DOMDocument();
            $pageSource->loadXml($this->exportPage($arrayParameters['pageId']));
            //include the page tables inside the page node
            $pageNode['domNode']->appendChild($pageNode['domInstance']->importNode($pageSource->documentElement, true));
            /**
             * check if we are going to extract the sub pages
             * of the page
             */
            if($arrayParameters['includeSubPages']){
                $subPagesXml = new \DOMDocument();
                $subPagesXml->loadXML($this->processSubPagesExport($pageId));
                $pageNode['domNode']->appendChild($pageNode['domInstance']->importNode($subPagesXml->documentElement, true));
            }
            $rootNode['domNode']->appendChild($rootNode['domInstance']->importNode($pageNode['domInstance']->documentElement, true));
            /**
             * process the extraction of the external page tables
             */
            $externalNode = $this->createXmlNode('external');
            $externalSource = new \DOMDocument();
            $externalSource->loadXml($this->exportPageExternalTables([
                'templates' => $templates,
                'styles' => $styles,
                'langs' => $langs
            ]));
            //include the page tables inside the page node
            $externalNode['domNode']->appendChild($externalNode['domInstance']->importNode($externalSource->documentElement, true));
            $rootNode['domNode']->appendChild($rootNode['domInstance']->importNode($externalNode['domInstance']->documentElement, true));
            //save the generated xml
            $result['xml'] = htmlspecialchars_decode($rootNode['domInstance']->saveXML(), ENT_XML1);

            /**
             * check if we need to export the page resources
             */
            if($arrayParameters['exportPageResources']){
                $result['resources'] = $this->exportPageResources($pageId, $arrayParameters['includeSubPages']);
            }
            $result['success'] = true;
        } catch (\Exception $ex) {
            $result['success'] = false;
            $result['raw_err_message'] = $ex->getMessage();
        }

        $result['pageCount'] = $pageCount;
        $arrayParameters['results'] = $result;
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_export_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    /**
     * Function to export the tables of the page,
     * the external tables are not included in the return
     * 
     * @param $pageId
     * @return mixed - return an xml (<tables>...</tables>)
     */
    public function exportPage($pageId)
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        // Sending service start event
        $arrayParameters = $this->sendEvent('melis_cms_page_export_start', $arrayParameters);

        try{
            //prepare the xml
            $tblXml = new \SimpleXMLElement('<tables/>');

            /**
             * process the extracting of table data
             * and put in xml
             */
            foreach($this->getTableServiceList() as $tblServiceName => $pageIdFieldName){
                $tblGtWay = $this->getServiceLocator()->get($tblServiceName);
                $tblData = $tblGtWay->getEntryByField($pageIdFieldName, $pageId)->current();
                if(!empty($tblData)){
                    $this->arrayToXml($tblData, $tblXml, $tblGtWay->getTableGateway()->getTable());
                }
            }
            /**
             * construct xml and format
             */
            $tblXmlDoc = new \DOMDocument();
            $tblXmlDoc->loadXML($this->removeXmlDeclaration(htmlspecialchars_decode($tblXml->asXML(), ENT_XML1)));

            //return results
            $arrayParameters['results'] = $tblXmlDoc->saveXML();
        }catch(\Exception $ex){
            exit($ex->getMessage());
        }

        $arrayParameters = $this->sendEvent('melis_cms_page_export_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    /**
     * Function to extract external table
     * datas
     *
     * @return mixed - return xml (<tables>...</tables>)
     */
    public function exportPageExternalTables($externalIds = [])
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        // Sending service start event
        $arrayParameters = $this->sendEvent('melis_cms_page_export_external_table_start', $arrayParameters);
        try{
            $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
            /**
             * prepare the table tag
             */
            $tblXml = new \SimpleXMLElement('<tables/>');
            /**
             * process the extracting of external tables
             * and put in xml
             */
            foreach($this->defaultExternalTablesList as $tableName => $arrayKey) {
                if (! empty($externalIds[$arrayKey])) {
                    $tblGtWay = new TableGateway($tableName, $adapter);
                    $primaryCol = $this->getTablePrimaryColumn($tableName);

                    $where = new Where();
                    $where->in($primaryCol, $externalIds[$arrayKey]);
                    $results = $tblGtWay->select($where)->toArray();

                    if (!empty($results)) {
                        $this->arrayToXml($results, $tblXml, $tableName);
                    }
                }
            }
            /**
             * construct xml and format
             */
            $tblXmlDoc = new \DOMDocument();
            $tblXmlDoc->loadXML($this->removeXmlDeclaration(htmlspecialchars_decode($tblXml->asXML(), ENT_XML1)));

            //return results
            $arrayParameters['results'] = $tblXmlDoc->saveXML();
        }catch(\Exception $ex){
            exit($ex->getMessage());
        }

        $arrayParameters = $this->sendEvent('melis_cms_page_export_external_table_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    /**
     * Process the exporting of the sub pages of
     * the selected page
     *
     * @param $pageId
     * @param $children
     * @return mixed
     */
    private function processSubPagesExport($pageId, $children = array()){

        $childrenNode = $this->createXmlNode('children');

        $pageTreeService = $this->getServiceLocator()->get('MelisEngineTree');
        if(empty($children)) {
            $children = $pageTreeService->getPageChildren($pageId);
        }

        foreach($children as $idx => $child) {
            /**
             * process the parent page
             */
            $pageData = htmlspecialchars_decode($this->exportPage($child['tree_page_id']), ENT_XML1);
            $pageData = $this->removeXmlDeclaration($pageData);
            $pageSource = new \DOMDocument();
            $pageSource->loadXml($pageData);

            $pageNode = $this->createXmlNode('page');
            $pageNode['domNode']->appendChild($pageNode['domInstance']->importNode($pageSource->documentElement, true));

            /**
             * process the sub pages of the page
             */
            $subChildren = $pageTreeService->getPageChildren($child['tree_page_id']);
            if(!empty($subChildren)){
                $childrenData = $this->processSubPagesExport($child['tree_page_id'], $subChildren);
                $pageData = htmlspecialchars_decode($childrenData, ENT_XML1);
                $pageData = $this->removeXmlDeclaration($pageData);
                $pageSource = new \DOMDocument();
                $pageSource->loadXml($pageData);
                //add the children data to its parent page
                $pageNode['domNode']->appendChild($pageNode['domInstance']->importNode($pageSource->documentElement, true));
            }

            $childrenNode['domNode']->appendChild($childrenNode['domInstance']->importNode($pageNode['domInstance']->documentElement, true));
        }
        return $childrenNode['domInstance']->saveXml();
    }

    /**
     * Export the ressources of the page
     *
     * @param $pageId
     * @param bool $includeSubPages
     * @return mixed
     */
    public function exportPageResources($pageId, $includeSubPages = false)
    {
        $pageRessources = [];
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        // Sending service start event
        $arrayParameters = $this->sendEvent('melis_cms_page_export_ressources_start', $arrayParameters);
        try {
            /**
             * get the resources of the selected page
             */
            $published = $this->getServiceLocator()->get('MelisEngineTablePagePublished');
            $saved = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
            $pagePubContent = $published->getEntryByField('page_id', $arrayParameters['pageId'])->current();
            $pageSavedContent = $saved->getEntryByField('page_id', $arrayParameters['pageId'])->current();

            //get the content
            $pagePubContent = (!empty($pagePubContent->page_content)) ? $pagePubContent->page_content : '';
            $pageSavedContent = (!empty($pageSavedContent->page_content)) ? $pageSavedContent->page_content : '';
            //combine the content to extract the resources
            $content = $pagePubContent.$pageSavedContent;
            //get resources
            $this->getResources($content, $pageRessources);
            /**
             * check if we are going to extract the resources of the sub pages
             */
            if ($arrayParameters['includeSubPages']) {
                $pageRessources = array_merge($pageRessources, $this->extractPageChildResources($arrayParameters['pageId']));
            }
            $arrayParameters['results'] = array_unique($pageRessources);

        }catch (\Exception $ex){

        }

        $arrayParameters = $this->sendEvent('melis_cms_page_export_ressources_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    /**
     * Extract the children ressources
     *
     * @param $pageId
     * @return array
     */
    private function extractPageChildResources($pageId)
    {
        $resources = [];
        $pageTreeService = $this->getServiceLocator()->get('MelisEngineTree');
        $children = $pageTreeService->getPageChildren($pageId);
        foreach($children as $data){
            $pageContent = $data['page_content'];
            //check if page content is also available on page saved
            if(!empty($data['s_page_content'])){
                $pageContent .= $data['s_page_content'];
            }
            $this->getResources($pageContent, $resources);
            $resources = array_merge($resources, $this->extractPageChildResources($data['tree_page_id']));
        }
        return $resources;
    }

    /**
     * Get resources
     *
     * @param $pageContent
     * @param $resources
     */
    private function getResources($pageContent, &$resources)
    {
        $imageMatches = null;
        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $pageContent, $imageMatches);
        if (!empty($imageMatches)) {
            unset($imageMatches[0]);
        }
        $videoMatches = null;
        preg_match_all('/< *source[^>]*src *= *["\']?([^"\']*)/i', $pageContent, $videoMatches);
        if (!empty($videoMatches)) {
            unset($videoMatches[0]);
        }
        $audioMatches = null;
        preg_match_all('/< *audio[^>]*src *= *["\']?([^"\']*)/i', $pageContent, $audioMatches);
        if (!empty($audioMatches)) {
            unset($audioMatches[0]);
        }

        $matches = array_merge($imageMatches, $videoMatches, $audioMatches);

        foreach($matches as $list){
            foreach($list as $val) {
                if (
                    strpos($val, 'img') === false ||
                    strpos($val, 'source') === false ||
                    strpos($val, 'audio')
                ) {
                    //get only whats in the media folder
                    if (strpos($val, 'media') !== false) {
                        if (!in_array($val, $resources)) {
                            $val = str_replace('%20', ' ', $val);
                            array_push($resources, $val);
                        }
                    }
                }
            }
        }
    }

    /**
     * Function to zip a folder
     *
     * @param $folderPath
     * @param $zipFolderName
     * @return bool
     */
    public function zipFolder($folderPath, $zipFolderName)
    {
        $zipFileName = $_SERVER["DOCUMENT_ROOT"] . '/' . $zipFolderName;
        // Initialize archive object

        $zip = new \ZipArchive();
        $zip->open($zipFileName, $zip::CREATE | $zip::OVERWRITE);

        $this->addFolderToZip($folderPath.'/', $zip, '');
        // Zip archive will be created only after closing object
        return $zip->close();
    }

    /**
     * Function to delete directory/file
     *
     * @param $dir
     * @return bool
     */
    public function deleteDirectory($path) {
        $dir = opendir($path);

        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $path . '/' . $file;
                if ( is_dir($full) ) {
                    $this->deleteDirectory($full);
                } else {
                    unlink($full);
                }
            }
        }

        closedir($dir);
        return rmdir($path);
    }

    /**
     * Function to save/copy the page
     * resources to the temporary folder
     *
     * @param $folderPath
     * @param $resources - an array of file path
     */
    public function manageResources($folderPath, $resources)
    {
        $docRoot = $_SERVER["DOCUMENT_ROOT"];
        /**
         * make sure the temporary folder
         * is already created
         */
        if(!file_exists($folderPath)) {
            mkdir($folderPath, 0777);
        }

        /**
         * check if folder is writable
         */
        if(is_writable($folderPath)){
            foreach($resources as $path){
                $info = pathinfo($path);

                if(!empty($info['dirname'])){
                    $fileNewPath = $folderPath.$info['dirname'];

                    /**
                     * create the folder path if not exist
                     */
                    if(!file_exists($fileNewPath)){
                        mkdir($fileNewPath, 0777, true);
                    }
                    /**
                     * copy the file to the temporary folder
                     */
                    if(is_writable($fileNewPath)){
                        if (file_exists($docRoot.$path))
                            copy($docRoot.$path, $fileNewPath.'/'.$info['basename']);
                    }
                }
            }
        }
    }

    /**
     * @param $tblServiceName - Table Service Name. ex: MelisEngineTablePageLang
     * @param $pageIdFieldName - Page Id Field name in the table. ex: plang_page_id
     */
    public function addTableService($tblServiceName, $pageIdFieldName)
    {
        $this->defaultTableList[$tblServiceName] = $pageIdFieldName;
    }

    /**
     * @return array
     */
    private function getTableServiceList()
    {
        return $this->defaultTableList;
    }

    /**
     * @param $tblServiceName - Table Service Name. ex: MelisEngineTablePageLang
     */
    public function addExternalTableService($tblServiceName)
    {
        array_push($this->defaultExternalTables, $tblServiceName);
    }

    /**
     * @return array
     */
    private function getExternalTableServiceList()
    {
        return $this->defaultExternalTables;
    }

    /**
     * @param $data
     * @param $xml
     * @param $table
     */
    private function arrayToXml( $data, &$xml, $table = null) {
        if(!empty($table)) {
            $tblXml = $xml->addChild($table);
        }else{
            $tblXml = $xml;
        }

        foreach( $data as $key => $value ) {
            if( is_numeric($key) ){
                $key = 'row_'.$key; //dealing with <0/>..<n/> issues
            }
            if( is_array($value) ) {
                $subnode = $tblXml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $tblXml->addChild($key, htmlspecialchars($value));
            }
        }
    }

    /**
     * @param $nodeName
     * @return array
     */
    private function createXmlNode($nodeName)
    {
        $domInstance = new \DOMDocument();
        $domInstance->preserveWhiteSpace = false;
        $domInstance->formatOutput = true;
        $domNode = $domInstance->appendChild($domInstance->createElement($nodeName));

        return ['domInstance' => $domInstance, 'domNode' => $domNode];
    }

    private function removeXmlDeclaration($xml){
        $xml = preg_replace( "/<\?xml.+?\?>/", "", $xml);
        return $xml;
    }

    private function addFolderToZip($dir, $zipArchive, $zipdir = '')
    {
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                //Add the directory
                if (!empty($zipdir))
                    $zipArchive->addEmptyDir($zipdir);

                // Loop through all the files
                while (($file = readdir($dh)) !== false) {
                    //If it's a folder, run the function again!
                    if (!is_file($dir . $file)) {
                        // Skip parent and root directories
                        if (($file !== ".") && ($file !== "..")) {
                            $this->addFolderToZip($dir . $file . "/", $zipArchive, $zipdir . $file . "/");
                        }
                    } else {
                        // Add the files
                        $zipArchive->addFile($dir . $file, $zipdir . $file);
                    }
                }
            }
        }
    }

    private function getAllPageIds($pageId, &$pages, &$templates, &$styles, &$langs, &$pageCount, $includeSubPages) {
        $pageTreeTable = $this->getServiceLocator()->get('MelisEngineTablePageTree');
        $pageSavedTable = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
        $type = '';

        if (! empty($pageSavedTable->getSavedSitePagesById($pageId)->toArray()))
            $type = 'saved';

        $pages[] = $pageId;

        $pageData = $pageTreeTable->getFullDatasPage($pageId, $type)->toArray();

        if (!empty($pageData))
            $pageData = $pageData[0];

        if (! empty($pageData['page_tpl_id']))
            if (! in_array($pageData['page_tpl_id'], $templates))
                $templates[] = $pageData['page_tpl_id'];

        if (! empty($pageData['style_id']))
            if (! in_array($pageData['style_id'], $styles))
                $styles[] = $pageData['style_id'];

        if (! empty($pageData['lang_cms_id']))
            if (! in_array($pageData['lang_cms_id'], $langs))
                $langs[] = $pageData['lang_cms_id'];

        $pageCount++;

        if ($includeSubPages) {

            $pageTreeService = $this->getServiceLocator()->get('MelisEngineTree');
            $children = $pageTreeService->getPageChildren($pageId)->toArray();

            foreach ($children as $id => $child) {
                $subChildren = $pageTreeService->getPageChildren($child['tree_page_id']);

                if (!empty($subChildren)) {
                    $this->getAllPageIds($child['tree_page_id'], $pages, $templates, $styles, $langs, $pageCount, $includeSubPages);
                } else {
                    $pages[] = $child['tree_page_id'];
                    $tplId = $child['page_tpl_id'] ?? $child['s_page_tpl_id'];
                    $stylId = $child['style_id'];
                    $lngId = $child['lang_cms_id'];

                    if (!empty($tplId))
                        if (!in_array($tplId, $templates))
                            $templates[] = $tplId;

                    if (!empty($stylId))
                        if (!in_array($stylId, $styles))
                            $styles[] = $stylId;

                    if (!empty($lngId))
                        if (!in_array($lngId, $langs))
                            $langs[] = $lngId;

                    $pageCount++;
                }
            }
        }
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
}
