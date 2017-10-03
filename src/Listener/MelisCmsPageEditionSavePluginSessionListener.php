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
        $priority        = -10000;
        $sharedEvents    = $events->getSharedManager();
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
                    $tag    = isset($params['melisPluginTag']) ? $params['melisPluginTag'] : null;
                    if($pageId && $post) {

                        $plugin    = isset($post['melisPluginTag']) ? $post['melisPluginTag'] : null;
                        $pluginId  = isset($post['melisPluginId'])  ? $post['melisPluginId']  : null;
                        $pluginTag = isset($post['melisPluginTag'])  ? $post['melisPluginTag']  : null;
                        $container = new Container('meliscms');

                        // add/edit plugin width depending on the width of the iframe container
                        if($plugin && $pluginId) {

                            $pageContents = $container['content-pages'];

                            if($pageContents) {

                                $pageId        = (int) $pageId;
                                $pluginContent = isset($pageContents[$pageId][$plugin][$pluginId]) ?
                                    $pageContents[$pageId][$plugin][$pluginId] : null;

                                if($pluginContent) {
                                    $desktopWidth  = isset($post['melisPluginDesktopWidth']) ? (float) $post['melisPluginDesktopWidth'] : null;
                                    $tabletWidth   = isset($post['melisPluginTabletWidth'])  ? (float) $post['melisPluginTabletWidth']  : null;
                                    $mobileWidth   = isset($post['melisPluginMobileWidth'])  ? (float) $post['melisPluginMobileWidth']  : null;

                                    if($pluginTag != 'melisTag') {
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

                                        // include the plugin container ID
                                        $pluginContainerId = null;
                                        if(isset($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId])) {
                                            $pluginContainerId = (array) json_decode($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId]);
                                            $pluginContainerId = $pluginContainerId['plugin_container_id'];
                                        }

                                        $pluginContent = $pluginContentWithoutEndTag . $endPluginTag;

                                        if(isset($_SESSION['meliscms']['content-pages'][$pageId][$plugin])) {
                                            $_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId] = $this->getPluginContentWithInsertedContainerId($pageId, $plugin, $pluginId, $pluginContent);
                                        }


                                    }
                                    else {

                                        if($desktopWidth || $tabletWidth || $mobileWidth) {

                                            $widths = array(
                                                'plugin_id'     => $pluginId,
                                                'width_desktop' => $desktopWidth,
                                                'width_tablet'  => $tabletWidth,
                                                'width_mobile'  => $mobileWidth
                                            );

                                            if(isset($_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId])) {
                                                $data = array($widths);

                                                if(isset($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId])) {
                                                    $currentData = (array) json_decode($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId]);
                                                    $data        = array_merge($currentData, $data);
                                                }
                                                $_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId] = json_encode($data);
                                                $this->updateMelisTagContent($pageId, $plugin, $pluginId, $pluginContent);
                                            }

                                        }
                                        else {
                                            // check if the size of melis tag plugin exists
                                            if(isset($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId])) {
                                                $this->updateMelisTagContent($pageId, $plugin, $pluginId, $pluginContent);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        // end checker for $plugin and $pluginIds
                    }

                }
            },
            // lowest priority so we can process this algorithm in the background
            $priority);

        $this->listeners[] = $callBackHandler;
    }

    /**
     * This method modifies the XML plugin data and looks
     * for width tags and replaces the value
     * @param $content
     * @param $search
     * @param $replace
     * @return mixed|null|string
     */
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

    /**
     * This method modifies the tag plugin xml attribute
     * applying the changes of the width
     * @param $pageId
     * @param $plugin
     * @param $pluginId
     * @param $content
     */
    private function updateMelisTagContent($pageId, $plugin, $pluginId, $content)
    {
        $pluginContent = $content;

        $pattern = '/type\=\"([html|media|textarea]*\")/';
        if(preg_match($pattern, $pluginContent, $matches)) {
            $type = isset($matches[0]) ? $matches[0] : null;
            if($type) {

                // apply sizes
                $widths        = (array) json_decode($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId]);
                $replacement   = $type .' width_desktop="'.$widths['width_desktop'].'" width_tablet="'.$widths['width_tablet'].'" width_mobile="'.$widths['width_mobile'].'"';
                $pluginContent = preg_replace($pattern, $replacement, $pluginContent);

                $_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId] = $this->getPluginContentWithInsertedContainerId($pageId, $plugin, $pluginId, $pluginContent);
            }
        }
    }

    /**
     * This method adds/replaces the plugin container ID and returning
     * the whole plugin XML content
     * @param $pageId
     * @param $plugin
     * @param $pluginId
     * @param $pluginContent
     * @return mixed
     */
    private function getPluginContentWithInsertedContainerId($pageId, $plugin, $pluginId, $pluginContent)
    {
        $pluginContainerId = null;

        if(isset($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId])) {
            $currentData       = (array) json_decode($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId]);
            $pluginContainerId = isset($currentData['plugin_container_id']) ? $currentData['plugin_container_id'] : null;
        }

        // remove first the attribute if exists
        $pluginContainerPattern = '/\splugin_container_id\=\"(.*?)\"/';
        if(preg_match($pluginContainerPattern, $pluginContent)) {
            $pluginContent = preg_replace($pluginContainerPattern, '', $pluginContent);
        }

        $pattern    = '/'.$plugin.'\sid\=\"(.*?)\"/';
        $replace    = $plugin . ' id="'.$pluginId.'" plugin_container_id="'.$pluginContainerId.'"';
        $newContent = $pluginContent;

        // add the plugin_container_id attribute
        if(preg_match($pattern, $pluginContent)) {
            $newContent = preg_replace($pattern, $replace, $pluginContent);
        }

        return $newContent;
    }
}