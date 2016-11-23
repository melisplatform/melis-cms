<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter as DbAdapter;

/**
 * Diagnostic controller.
 * Associated with /config/diagnostic.config.php
 * All tests run during diagnostics are found here
 */
class DiagnosticController extends AbstractActionController
{
    private $odbAdapter;

    /**
     * This will just make a simple test if this function is properly working
     */
    public function basicTestAction()
    {
        $request = $this->params()->fromRoute('payload', $this->params()->fromQuery('payload', null));

        $label = null;
        $success = 0;
        $response = array();
        $error = null;

        if($request) {
            $label = $request['label'];
            $success = 1;
            $response = array(
                'requestedModuleToTest' => $request['module'],
            );
        }
        else {
            $response = array(
                'requestedModuleToTest' => 'unknown',
            );
        }

        return new JsonModel(array(
            'label' => $label,
            'success' => $success,
            'result' => $response,
            'error' => $error
        ));
    }

    public function fileCreationTestAction()
    {
        $request = $this->params()->fromRoute('payload', $this->params()->fromQuery('payload', null));

        $label = null;
        $success = 0;
        $response = array();
        $error = null;
        if($request) {
            $file = $request['file'];
            $path = $request['path'];
            $label = $request['label'];
            if(file_exists($path)) {
                if(is_writable($path)) {
                    // then write the file

                    if(fopen($path.$file, 'w')) {

                        $cFile = fopen($path.$file, 'w');
                        $content = 'SAMPLE TEST DATA';
                        fwrite($cFile, $content);
                        fclose($cFile);

                        if(file_exists($path.$file)) {
                            $fileContent = file_get_contents($path.$file);
                            if(!empty($fileContent)) {
                                // delete the file
                                if(unlink($path.$file)) {
                                    $success = 1;
                                    $response = array(
                                        'fileCreationTest' =>   $this->getTranslations('tr_melis_test_path_write_success', array($path.$file)),
                                        'fileDeleteTest' => $this->getTranslations('tr_melis_test_path_delete_success', array($path.$file))
                                    );
                                }
                                else {
                                    $error = $this->getTranslations('tr_melis_test_write_not_found', array($file, $path));
                                }
                            }
                        }
                        else {
                            $error = $this->getTranslations('tr_melis_test_write_not_found', array($file, $path));
                        }
                    }
                    else {
                        $error = $this->getTranslations('tr_melis_test_create_file_false', array($file, $path));
                    }
                }
                else {
                    $error = $this->getTranslations('tr_melis_test_writable_false', array($path));
                }
            }
            else {
                $error = $this->getTranslations('tr_melis_test_path_exists_false', array($path));
            }

        }

        $actionResponse = array_merge(array(
            'label' => $label,
            'success' => $success,
            'error' => $error
        ), array('result' => $response));

        return new JsonModel($actionResponse);
    }

    public function testModuleTablesAction()
    {
        $request = $this->params()->fromRoute('payload', $this->params()->fromQuery('payload', null));
        

        $label = null;
        $success = 1;
        $response = array();
        $error = null;
        
        if($request) {
            $label = $request['label'];
            $env = getenv('MELIS_PLATFORM') ? getenv('MELIS_PLATFORM') : 'development';
            $dbConfig = 'config/autoload/platforms/'.$env.'.php';
            if(file_exists($dbConfig)) {
                $dbConfig = include('config/autoload/platforms/'.$env.'.php');
                $this->setDbAdapter($dbConfig['db']);
                
                $dbResults = array();
                $sql = new Sql($this->getDbAdapter());
                $select = $sql->select();
                
                
                $tables = $request['tables'];
                foreach($tables as $table) {

                    $select->from($table);
                    $statement = $sql->prepareStatementForSqlObject($select);
                    try {
                        $result = $statement->execute();
                        $response[$table] = $this->getTranslations('tr_melis_test_db_table_test_success', $table);
                    }catch(\Exception $e) {
                        $response[$table] = $this->getTranslations('tr_melis_test_db_table_test_failed', $table);
                        $error .= $this->getTranslations('tr_melis_test_db_table_exists_false', $table).PHP_EOL;
                        $success = 0;
                    }
                }
                
            }
            else {
                $error = $this->getTranslations('tr_melis_test_db_config_exists_false', $dbConfig);
            }
        }
        
        $actionResponse = array_merge(array(
            'label' => $label,
            'success' => $success,
            'error' => $error
        ), array('result' => $response));
        
        return new JsonModel($actionResponse);
    }
    
    
    
    private function getTranslations($translationKey, $args = array())
    {
        $translator = $this->getServiceLocator()->get('translator');
        $translationText = vsprintf($translator->translate($translationKey), $args);
        
        return $translationText;
    }
    
    /**
     * Set's the DB Adapter
     * @param String $config
     */
    private function setDbAdapter($config)
    {
        if(is_array($config)) {
            $this->odbAdapter = new DbAdapter(array_merge(array('driver' => 'Pdo_Mysql'), $config));
        }
    }
    
    /**
     * Returns the set DB Adapter
     */
    private function getDbAdapter()
    {
        return $this->odbAdapter;
    }
    
}