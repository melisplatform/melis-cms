<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Laminas\Session\Container;
use MelisCore\Controller\AbstractActionController;

class MelisSetupController extends AbstractActionController
{

    public function setupResultAction()
    {
        $success = 1;
        $message = 'tr_install_setup_message_ko';
        $title   = 'tr_install_setup_title';
        $errors  = array();

        $data = $this->getTool()->sanitizeRecursive($this->params()->fromRoute());

   //      try {
   //          $container = new Container('melis_modules_configuration_status');
           
			// $request = $this->getRequest();
			// $uri     = $request->getUri();
			// $scheme  = $uri->getScheme();
			// $siteDomain = $uri->getHost();
    
   //          $cmsSiteSrv = $this->$this->getServiceManager()->get('MelisCmsSiteService');
			// $environmentName = getenv('MELIS_PLATFORM');
   //          $container = new \Laminas\Session\Container('melisinstaller');
   //          $container = $container->getArrayCopy();

   //          $selectedSite = isset($container['site_module']['site']) ? $container['site_module']['site'] : null;

   //          $environments = isset($container['environments']['new']) ? $container['environments']['new'] : null;
   //          $siteId = 1;

   //          if ($selectedSite) {
   //              if ($selectedSite == 'NewSite') {

   //                  $dataSite = array(
   //                      'site_name' => isset($container['site_module']['website_name']) ? $container['site_module']['website_name'] : null
   //                  );

   //                  $dataDomain = array(
   //                      'sdom_env' => $environmentName,
   //                      'sdom_scheme' => $scheme,
   //                      'sdom_domain' => $siteDomain
   //                  );

   //                  $dataSiteLang = $container['site_module']['language'];

   //                  $genSiteModule = true;

   //                  $siteModule = getenv('MELIS_MODULE');

   //                  $saveSiteResult = $cmsSiteSrv->saveSite($dataSite, $dataDomain, array(), $dataSiteLang, null, $genSiteModule, $siteModule);

   //                  if ($saveSiteResult['success']) {
   //                      $siteId = $saveSiteResult['site_id'];
   //                  }
   //              }
   //          }

   //          $this->saveCmsSiteDomain($scheme, $siteDomain);

       // }
        // catch(\Exception $e) {
        //     $errors = $e->getMessage();
        // }


        $response = array(
            'success' => $success,
            'message' => $this->getTool()->getTranslation($message),
            'errors'  => $errors,
            'form'    => 'melis_installer_platform_data'
        );

        return new JsonModel($response);
    }

    /**
     * Returns the Tool Service Class
     * @return MelisCoreTool
     */
    private function getTool()
    {
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        $melisTool->setMelisToolKey('MelisCmsSlider', 'MelisCmsSlider_details');

        return $melisTool;

    }
    /**
     * Create a form from the configuration
     * @return \Laminas\Form\ElementInterface
     */
    private function getForm()
    {
        $coreConfig = $this->getServiceManager()->get('MelisCoreConfig');
        $form = $coreConfig->getItem('melis_engine_setup/forms/melis_installer_platform_data');

        $factory = new \Laminas\Form\Factory();
        $formElements = $this->getServiceManager()->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $form = $factory->createForm($form);

        return $form;

    }
    private function formatErrorMessage($errors = array())
    {
        $melisMelisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');
        $appConfigForm = $melisMelisCoreConfig->getItem('melis_engine_setup/forms/melis_installer_platform_data');
        $appConfigForm = $appConfigForm['elements'];

        foreach ($errors as $keyError => $valueError)
        {
            foreach ($appConfigForm as $keyForm => $valueForm)
            {
                if ($valueForm['spec']['name'] == $keyError &&
                    !empty($valueForm['spec']['options']['label']))
                    $errors[$keyError]['label'] = $valueForm['spec']['options']['label'];
            }
        }

        return $errors;
    }

    private function saveCmsSiteDomain($scheme, $site)
    {
        $container = new \Laminas\Session\Container('melisinstaller');

        // default platform
        $environments       = $container['environments'];
        $defaultEnvironment = $environments['default_environment'];
        $siteCtr            = 1;

        if($defaultEnvironment) {

            $defaultPlatformData[$siteCtr-1] = array(
                'sdom_site_id' => $siteCtr,
                'sdom_env'     => getenv('MELIS_PLATFORM'),
                'sdom_scheme'  => $scheme,
                'sdom_domain'  => $site
            );

            $platforms     = isset($environments['new']) ? $environments['new'] : null;
            $platformsData = array();

            if($platforms) {
                foreach($platforms as $platform) {
                    $platformsData[] = array(
                        'sdom_site_id' => $siteCtr,
                        'sdom_env'     => $platform[0]['sdom_env'],
                        'sdom_scheme'  => $platform[0]['sdom_scheme'],
                        'sdom_domain'  => $platform[0]['sdom_domain']
                    );
                }
            }

            $platformsData = array_merge($defaultPlatformData, $platformsData);

            $siteDomainTable = $this->getServiceManager()->get('MelisEngineTableSiteDomain');

            foreach($platformsData as $data) {
                $siteDomainTable->save($data);
            }

        }

    }
}
