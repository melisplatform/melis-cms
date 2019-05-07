<?php

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;
use Zend\Config\Config;
use Zend\Config\Writer\PhpArray;

class MelisCmsSitesModuleLoadService extends MelisCoreGeneralService
{
    /**
     * Returns all the available modules (enabled/disabled modules)
     * @param $siteId param int $siteId | id of the site of which module load is configured
     * @return array
     * @internal param bool $useOnlySiteModule | used if you want to get only the Site Modules
     */
    public function getModules($siteId)
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_module_load_start', $arrayParameters);

        // Service implementation start
        $modulesList = null;
        $siteData = $this->getMelisSitesTbl()->getEntryById($siteId)->current();
        $siteModuleName = $this->generateModuleNameCase($siteData->site_name);

        $exclude_modules = array(
            'MelisAssetManager',
            'MelisCore',
            'MelisSites',
            'MelisInstaller',
            'MelisModuleConfig',
            'MelisComposerDeploy',
            'MelisDbDeploy',
            '.', '..','.gitignore',
        );

        $moduleSrv = $this->getServiceLocator()->get('ModulesService');
        /**
         * Check if module is site to exclude it
         */
        $vendordModules = $moduleSrv->getVendorModules();
        foreach ($vendordModules as $key => $module) {
            if ($moduleSrv->isSiteModule($module)) {
                $siteMod = $this->generateModuleNameCase($module);
                array_push($exclude_modules, $siteMod);
            }
        }

        /**
         * get the module path
         *
         * This will check also if the module is came
         * from the vendor or from the MelisSites
         */
        if(!empty($moduleSrv->getComposerModulePath($siteModuleName))){
            $modulePath = $moduleSrv->getComposerModulePath($siteModuleName);
        }else {
            $modulePath = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites/' . $siteModuleName;
        }

        $filePath = $modulePath.'/config/module.load.php';

        /**
         * Check if file exist
         */
        if (file_exists($filePath)) {
            chmod($filePath, 0777);
            $moduleLoadList = include $filePath;
            $moduleLoadFile = $this->getModuleSvc()->getModulePlugins($exclude_modules);

            $modules = $moduleLoadList;

            foreach ($modules as $index => $modValues) {
                if (!in_array($modValues, $exclude_modules)) {
                    $modulesList[$modValues] = 1;
                }
            }

            // add the inactive modules
            foreach ($moduleLoadFile as $index => $module) {
                if (!isset($modulesList[$module])) {
                    $modulesList[$module] = 0;
                }
            }
        }
        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $modulesList;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_module_load_end', $arrayParameters);

        return $arrayParameters['results'];
    }

    /**
     * Returns all the available modules (enabled/disabled modules)
     * @param int $siteId | id of the site of which module load is configured
     * @param array $modules| array of module names to be added to module load file
     * @return array
     */
    public function saveModuleLoad($siteId, $modules)
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_module_load_start', $arrayParameters);

        // Service implementation start

        $siteData = $this->getMelisSitesTbl()->getEntryById($siteId)->current();
        $siteModuleName = $this->generateModuleNameCase($siteData->site_name);

        $moduleList = array('MelisAssetManager');

        foreach ($modules as $module) {
            array_push($moduleList,$module);
        }

        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites/'.$siteModuleName.'/config/';

        if (!file_exists($filePath)) {
            $filePath  = $_SERVER['DOCUMENT_ROOT'] . '/../vendor/melisplatform/melis-demo-cms/config/';
        }

        $status = $this->createModuleLoader($filePath, $moduleList);

        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $status;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_module_load_end', $arrayParameters);

        return $arrayParameters['results'];
    }

	/**
	 * This will modified a string to valid zf2 module name
	 * @param string $str
	 * @return string
	 */
	private function generateModuleNameCase($str) {
	    $i = array("-","_");
	    $str = preg_replace('/([a-z])([A-Z])/', "$1 $2", $str);
	    $str = str_replace($i, ' ', $str);
	    $str = str_replace(' ', '', ucwords(strtolower($str)));
	    $str = strtolower(substr($str,0,1)).substr($str,1);
	    $str = ucfirst($str);
	    return $str;
	}

    /**
     * Creates module loader file
     *
     * @param $pathToStore
     * @param array $modules
     * @param array $topModules
     * @param array $bottomModules
     *
     * @return bool
     */
    public function createModuleLoader($pathToStore, $modules = [])
    {

        $tmpFileName = 'module.load.php.tmp';
        $fileName = 'module.load.php';

        if ($this->checkDir($pathToStore)) {

            $config = new Config($modules, true);

            $writer = new PhpArray();

            $conf = $writer->toString($config);
            $conf = preg_replace('/    \d+/u', '', $conf); // remove the number index
            $conf = str_replace('=>', '', $conf); // remove the => characters.
            file_put_contents($pathToStore . '/' . $tmpFileName, $conf);

            if (file_exists($pathToStore . '/' . $tmpFileName)) {
                // check if the array is not empty
                $checkConfig = include($pathToStore . '/' . $tmpFileName);
                if (count($checkConfig) > 1) {
                    // delete the current module loader file
                    unlink($pathToStore . '/' . $fileName);
                    // rename the module loader tmp file into module.load.php
                    rename($pathToStore . '/' . $tmpFileName, $pathToStore . '/' . $fileName);
                    // if everything went well
                    return true;
                }
            }

        }

        return false;
    }

    /**
     * This will check if directory exists and it's a valid directory
     *
     * @param $dir
     *
     * @return bool
     */
    protected function checkDir($dir)
    {
        if (file_exists($dir) && is_dir($dir)) {
            return true;
        }

        return false;
    }

    /**
     * @return \MelisCore\Service\MelisCoreModulesService
     */
    protected function getModuleSvc()
    {
        /**
         * @var \MelisCore\Service\MelisCoreModulesService $modulesSvc
         */
        $modulesSvc = $this->getServiceLocator()->get('ModulesService');
        return $modulesSvc;
    }

    /**
     * @return array|\MelisEngine\Model\Tables\MelisSiteTable|object
     */
    protected function getMelisSitesTbl()
    {
        /**
         * @var \MelisEngine\Model\Tables\MelisSiteTable
         *
         */
        $modulesSvc = $this->getServiceLocator()->get('MelisEngineTableSite');
        return $modulesSvc;
    }

}