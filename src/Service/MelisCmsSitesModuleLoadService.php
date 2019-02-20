<?php

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;

class MelisCmsSitesModuleLoadService extends MelisCoreGeneralService
{

    const MODULE_LOADER_FILE = 'config/melis.module.load.php';

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


        $exclude_modules = array(
            'MelisAssetManager',
            'MelisEngine',
            'MelisFront',
            'MelisCore',
            'MelisSites',
            'MelisInstaller',
            'MelisModuleConfig',
            'MelisComposerDeploy',
            'MelisDbDeploy',
            '.', '..','.gitignore',
        );

        $modules = array();
        $modulesList = null;

        $siteData = $this->getMelisSitesTbl()->getEntryById($siteId)->current();
        $siteModuleName = $this->generateModuleNameCase($siteData->site_name);
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites/'.$siteModuleName.'/config/module.load.php';

        $moduleLoadList = file_exists($filePath) ? include($filePath) : array();
        $moduleLoadFile = $this->getModuleSvc()->getModulePlugins($exclude_modules);

        $modules = $moduleLoadList;

        foreach($modules as $index => $modValues) {

            if(!in_array($modValues, $exclude_modules)) {

                if(in_array($modValues, $moduleLoadFile)) {
                    $modulesList[$modValues] = 1;
                }
                else {
                    $modulesList[$modValues] = 0;
                }
            }
        }

        // add the inactive modules
        foreach($moduleLoadFile as $index => $module) {

            if(!isset($modulesList[$module])) {
                $modulesList[$module] = 0;
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