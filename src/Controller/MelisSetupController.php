<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Validator\File\Size;
use Zend\Validator\File\IsImage;
use Zend\Validator\File\Upload;
use Zend\File\Transfer\Adapter\Http;
use Zend\Session\Container;

class MelisSetupController extends AbstractActionController
{
    public function setupFormAction()
    {
        $coreConfig     = $this->getServiceLocator()->get('MelisCoreConfig');
        $formPlatformId = $coreConfig->getItem('melis_cms_setup/forms/melis_installer_platform_id');
        $formSite       = $coreConfig->getItem('melis_cms_setup/forms/melis_installer_site_');
        $formDomain     = $coreConfig->getItem('melis_cms_setup/forms/melis_installer_domain');

        $view = new ViewModel();
        $view->formPlatformId = $formPlatformId;
        $view->formSite       = $formSite;
        $view->formDomain     = $formDomain;

        return $view;

    }

    public function setupResultAction()
    {
        echo "Success Result";
    }
}