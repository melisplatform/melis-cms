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
use Zend\Stdlib\ArrayUtils;
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

                        $plugin    = isset($post['melisPluginTag'])  ? $post['melisPluginTag']  : null;
                        $pluginId  = isset($post['melisPluginId'])   ? $post['melisPluginId']   : null;
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

                                    // check whether any of these 3 widths is set or changed
                                    if($desktopWidth || $tabletWidth || $mobileWidth) {

                                        $widths = array(
                                            'plugin_id'     => $pluginId,
                                            'width_desktop' => $desktopWidth,
                                            'width_tablet'  => $tabletWidth,
                                            'width_mobile'  => $mobileWidth
                                        );

                                        // check if the plugin exists in the session
                                        if(isset($_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId])) {

                                            $data = $widths;

                                            // check if the session settings of the plugin exists
                                            if(isset($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId])) {
                                                $currentData = (array) json_decode($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId]);
                                                $data        = ArrayUtils::merge($currentData, $data);
                                            }

                                            // update the session settings of the plugin
                                            $_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId] = json_encode($data);

                                            // and also the plugin itself
                                            $this->updateMelisPlugin($pageId, $plugin, $pluginId, $pluginContent, true);

                                        }

                                    }
                                    else {

                                        // preserve the settings of the plugin except melisTag
                                        if($plugin != 'melisTag') {
                                            $pluginSettings = $this->getTagElements($plugin, $pluginContent);
                                            $data = array(
                                                'settings' => $pluginSettings
                                            );

                                            // check if the size of melis tag plugin exists in session settings and just re-apply it
                                            if(isset($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId])) {

                                                $currentData = (array) json_decode($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId]);
                                                $data        = ArrayUtils::merge($currentData, $data);
                                                // update the session settings of the plugin
                                                $_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId] = json_encode($data);
                                            }
                                            else{
                                                $_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId] = json_encode($data);
                                            }
                                        }

                                        // re-apply settings
                                        $this->updateMelisPlugin($pageId, $plugin, $pluginId, $pluginContent);

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
    private function updateMelisPlugin($pageId, $plugin, $pluginId, $content, $updateSettings = false)
    {
        $pluginContent  = $content;
        $pluginSettings = (array) json_decode($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId]);

        if($plugin == 'melisTag') {
            $pattern = '/type\=\"([html|media|textarea]*\")/';
            if(preg_match($pattern, $pluginContent, $matches)) {
                $type = isset($matches[0]) ? $matches[0] : null;
                if($type) {

                    // apply sizes
                    if($pluginSettings) {
                        $replacement   = $type .' width_desktop="'.$pluginSettings['width_desktop'].
                            '" width_tablet="'.$pluginSettings['width_tablet'].
                            '" width_mobile="'.$pluginSettings['width_mobile'].'"';
                        $pluginContent = preg_replace($pattern, $replacement, $pluginContent);
                    }
                }
            }
        }
        else {
            $pattern = '\<'.$plugin.'\sid\=\"(.*?)*\"';

            if(preg_match('/'.$pattern.'/', $pluginContent, $matches)) {
                $id = isset($matches[0]) ? $matches[0] : null;

                if($id) {
                    if($pluginSettings) {
                        $replacement   = $id .' width_desktop="'.$pluginSettings['width_desktop'].
                            '" width_tablet="'.$pluginSettings['width_tablet'].
                            '" width_mobile="'.$pluginSettings['width_mobile'].'"';
                        $pluginContent = preg_replace('/'.$pattern.'/', $replacement, $pluginContent);
                    }

                }
            }

        }

        if(isset($_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId])) {
            $_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId] =
                $this->getPluginContentWithInsertedContainerId($pageId, $plugin, $pluginId, $pluginContent);

            if($updateSettings) {
                $pluginContent = $_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId];
                $_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId] = $this->reapplySettings($pageId, $plugin, $pluginId, $pluginContent);
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

    private function getTagElements($plugin, $content)
    {
        $pattern  = '\<'.$plugin.'[a-zA-Z0-9\"\_\-\=\s]+?\>([a-zA-Z0-9\"\_\-\=\s\<\>\!\/\[\]\.]+?)\<\/'.$plugin.'\>';
        $elements = '';

        if(preg_match('/'.$pattern.'/', $content, $matches)) {

            if(isset($matches[1])) {
                $elements = $matches[1];
            }
        }

        return $elements;
    }

    public function reapplySettings($pageId, $plugin, $pluginId, $content)
    {

        if(isset($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId])) {
            $settings = (array) json_decode($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId]);
            $settings = $settings['settings'];

            $pluginEndTag = '</'.$plugin.'>';
            $content = str_replace($pluginEndTag, $settings.$pluginEndTag, $content);

        }

        return $content;

    }
}