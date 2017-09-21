<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Listener;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container;

use MelisCore\Listener\MelisCoreGeneralListener;

class MelisCmsPageEditionSavePluginSessionListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{

    public function attach(EventManagerInterface $events)
    {
        $sharedEvents      = $events->getSharedManager();

        $callBackHandler = $sharedEvents->attach(
            'MelisCms',
            array(
                'meliscms_page_savesession_plugin_start',
            ),
            function($e){

                $sm = $e->getTarget()->getServiceLocator();
                $params = $e->getParams();

                if($params) {
                    $pageId = isset($params['idPage'])     ? $params['idPage']     : null;
                    $post   = isset($params['postValues']) ? $params['postValues'] : null;
                    if($pageId && $post) {

                        $plugin    = isset($post['melisPluginTag']) ? $post['melisPluginTag'] : null;
                        $pluginId  = isset($post['melisPluginId'])  ? $post['melisPluginId']  : null;
                        $container = new Container('meliscms');

                        // add/edit plugin width depending on the width of the iframe container
                        if($plugin && $pluginId) {

                            $pageContents = $container['content-pages'];

                            if($pageContents) {

                                $pageId        = (int) $pageId;
                                $pluginContent = $pageContents[$pageId][$plugin][$pluginId];

                                $desktopWidth  = isset($post['melisPluginDesktopWidth']) ? (float) $post['melisPluginDesktopWidth'] : null;
                                $tabletWidth   = isset($post['melisPluginTabletWidth'])  ? (float) $post['melisPluginTabletWidth']  : null;
                                $mobileWidth   = isset($post['melisPluginMobileWidth'])  ? (float) $post['melisPluginMobileWidth'] : null;

                                $desktopXml    = '<width_desktop><![CDATA['.$desktopWidth.']]></width_desktop>';
                                $tabletXml     = '<width_tablet><![CDATA['.$tabletWidth.']]></width_tablet>';
                                $mobileXml     = '<width_mobile><![CDATA['.$mobileWidth.']]></width_mobile>';

                                $endPluginTag = "</$plugin>";


                                $pluginContentWithoutEndTag = str_replace($endPluginTag, '', $pluginContent);

                                if($desktopWidth)
                                    $pluginContentWithoutEndTag = $this->insertOrReplaceTag($pluginContentWithoutEndTag, 'width_desktop', $desktopXml);

                                if($tabletWidth)
                                    $pluginContentWithoutEndTag = $this->insertOrReplaceTag($pluginContentWithoutEndTag, 'width_tablet', $tabletXml);

                                if($mobileWidth)
                                    $pluginContentWithoutEndTag = $this->insertOrReplaceTag($pluginContentWithoutEndTag, 'width_mobile', $mobileXml);


                                $pluginContent = $pluginContentWithoutEndTag . $endPluginTag;
                                unset($_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId]);

                                if(isset($_SESSION['meliscms']['content-pages'][$pageId][$plugin]))
                                    $_SESSION['meliscms']['content-pages'][$pageId][$plugin] = array($pluginId => $pluginContent);

                            }
                        }
                    }
                }
            },
            // lowest priority so we can process this algorithm in the background
            -10000);

        $this->listeners[] = $callBackHandler;
    }

    private function insertOrReplaceTag($content, $search, $replace)
    {
        $newContent =  null;

        $regexSearch = "/(\<$search\>\<\!\[CDATA\[([0-9.])+\]\]\>\<\/$search\>)/";
        if(preg_match($regexSearch, $content)) {
            $newContent = preg_replace($regexSearch, $replace, $content);
        }
        else {
            $newContent = $content . $replace;

        }

        return $newContent;
    }
}