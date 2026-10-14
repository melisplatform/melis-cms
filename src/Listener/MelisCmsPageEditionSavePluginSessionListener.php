<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Listener;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container;
use MelisCore\Listener\MelisGeneralListener;
use Laminas\Stdlib\ArrayUtils;

class MelisCmsPageEditionSavePluginSessionListener extends MelisGeneralListener implements ListenerAggregateInterface
{

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->attachEventListener(
            $events,
            'MelisCms',
            'meliscms_page_savesession_plugin_start',
            function($event){

                $sm = $event->getTarget()->getEvent()->getApplication()->getServiceManager();
                $params = $event->getParams();

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
            -1000
        );

        // Runs BEFORE the plugin rewrites its XML (MelisCmsPluginSaveEditionSessionListener, priority 80):
        // remember the widths the plugin XML already carries, so the handler above can re-apply them.
        $this->attachEventListener(
            $events,
            'MelisCms',
            'meliscms_page_savesession_plugin_start',
            function($event){
                $params   = $event->getParams();
                $pageId   = isset($params['idPage']) ? (int) $params['idPage'] : 0;
                $post     = isset($params['postValues']) ? $params['postValues'] : null;
                $plugin   = isset($post['melisPluginTag']) ? $post['melisPluginTag'] : null;
                $pluginId = isset($post['melisPluginId'])  ? $post['melisPluginId']  : null;

                if($pageId && $plugin && $pluginId) {
                    $this->seedWidthsFromSessionXml($pageId, $plugin, $pluginId);
                }
            },
            1000
        );
    }

    /**
     * Mantis #0011125: a tag plugin (mini-template text…) rebuilds its XML without any width attribute
     * (MelisFrontTagPlugin::savePluginConfigToXml), relying on the session settings to re-apply them.
     * Those settings only hold widths resized during the CURRENT edit session: after a Draft + reload the
     * session is re-seeded from the page XML without them (or with only plugin_container_id, which
     * setPluginWidthXmlAttribute then completes with 100), so editing the text reset the block to 100%.
     * When the session doesn't know the widths yet, take them from the plugin XML as it is now.
     * @param int $pageId
     * @param string $plugin
     * @param string $pluginId
     */
    private function seedWidthsFromSessionXml($pageId, $plugin, $pluginId)
    {
        $pluginXml = isset($_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId]) ?
            $_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId] : null;
        if(!is_string($pluginXml) || trim($pluginXml) === '') {
            return;
        }

        $settings = array();
        if(isset($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId])) {
            $settings = (array) json_decode($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId]);
        }
        // widths already set in this session (resize) win
        if(isset($settings['width_desktop']) || isset($settings['width_tablet']) || isset($settings['width_mobile'])) {
            return;
        }

        $useErrors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($pluginXml);
        libxml_clear_errors();
        libxml_use_internal_errors($useErrors);
        if($xml === false) {
            return;
        }

        $attributes = $xml->attributes();
        $widths = array();
        foreach(array('width_desktop', 'width_tablet', 'width_mobile') as $key) {
            if(isset($attributes->$key) && (string) $attributes->$key !== '') {
                $widths[$key] = (string) $attributes->$key;
            }
        }
        if(empty($widths)) {
            return;
        }

        if(!isset($settings['plugin_container_id']) && isset($attributes->plugin_container_id)) {
            $settings['plugin_container_id'] = (string) $attributes->plugin_container_id;
        }

        $_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId] = json_encode(array_merge($settings, $widths));
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

        $pluginContent         = $content;
        $pluginSessionSettings = isset($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId]) ?
            $_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId] : '';
        $pluginSettings        = (array) json_decode($pluginSessionSettings);
        
        if($plugin == 'melisTag') {

            $pattern = '/type\=\"([html|media|textarea]*\")/';
            if(preg_match($pattern, $pluginContent, $matches)) {
                $type = isset($matches[0]) ? $matches[0] : null;
                if($type) {
                    // apply sizes
                    if($pluginSettings) {
                        /* $replacement   = $type .' width_desktop="'.$pluginSettings['width_desktop'].
                            '" width_tablet="'.$pluginSettings['width_tablet'].
                            '" width_mobile="'.$pluginSettings['width_mobile'].'"';
                        $pluginContent = preg_replace($pattern, $replacement, $pluginContent); */
                        
                        $pluginContent = $this->setPluginWidthXmlAttribute($pluginContent, $pluginSettings);
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
                        /* $replacement   = $id .' width_desktop="'.$pluginDesktop.
                            '" width_tablet="'.$pluginTablet.
                            '" width_mobile="'.$pluginMobile.'"';
                        $pluginContent = preg_replace('/'.$pattern.'/', $replacement, $pluginContent); */
                        
                        $pluginContent = $this->setPluginWidthXmlAttribute($pluginContent, $pluginSettings);
                    }
                }
            }
        }
        

        if(isset($_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId])) {
            $_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId] =
                $this->getPluginContentWithInsertedContainerId($pageId, $plugin, $pluginId, $pluginContent);

            $pluginContent = $_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId];
            if($updateSettings) {

                $_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId] = $this->reapplySettings($pageId, $plugin, $pluginId, $pluginContent);
            }

            if($plugin == 'melisTag') {
                $content =  $this->updateContent($pageId, $plugin, $pluginId, $pluginContent);
                $_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId] = $content;
            }

        }

    }
    
    /**
     * This method will setup the width of the plugin xml config
     * 
     * @param String $pluginXml
     * @param array $pluginSettings
     * @return string
     */
    private function setPluginWidthXmlAttribute($pluginXml, $pluginSettings){
        
        try {
            
            $pluginDesktop = isset($pluginSettings['width_desktop']) ? $pluginSettings['width_desktop'] : 100;
            $pluginTablet  = isset($pluginSettings['width_tablet'])  ? $pluginSettings['width_tablet']  : 100;
            $pluginMobile  = isset($pluginSettings['width_mobile'])  ? $pluginSettings['width_mobile']  : 100;
            
            // Parsing xml string to Xml object
            $xml = simplexml_load_string($pluginXml);
            
            // Adding/updating plugin xml attributes
            if (isset($xml->attributes()->width_desktop))
                $xml->attributes()->width_desktop = $pluginDesktop;
            else
                $xml->addAttribute('width_desktop', $pluginDesktop);
                    
            if (isset($xml->attributes()->width_tablet))
                $xml->attributes()->width_tablet = $pluginTablet;
            else
                $xml->addAttribute('width_tablet', $pluginTablet);
                            
            if (isset($xml->attributes()->width_mobile))
                $xml->attributes()->width_mobile = $pluginMobile;
            else
                $xml->addAttribute('width_mobile', $pluginMobile);
                
            // Geeting the Plugin xml
            $customXML = new \SimpleXMLElement($xml->asXML());
            $dom = dom_import_simplexml($customXML);
            $pluginXml = $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
                                    
        }catch (\Exception $e){}
        
        return $pluginXml;
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

//        $pattern    = '/'.$plugin.'\sid\=\"(.*?)\"/';
//        $replace    = $plugin . ' id="'.$pluginId.'" plugin_container_id="'.$pluginContainerId.'"';
//        $newContent = $pluginContent;

        $pattern = '/(' . preg_quote($plugin, '/') . '\s+id=")([^"]*)"/';
        $replace = '${1}${2}" plugin_container_id="' . $pluginContainerId . '"';
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

            $settings = isset($settings['settings']) ? $settings['settings'] : '';

            $pluginEndTag = '</'.$plugin.'>';
            $content = str_replace($pluginEndTag, $settings.$pluginEndTag, $content);

        }

        return $content;

    }

    public function updateContent($pageId, $plugin, $pluginId, $content)
    {

        if(isset($_SESSION['meliscms']['content-pages'][$pageId]['melisTag'][$pluginId])) {
            $pattern = '\<div\sdata\-melis\-plugin\-tag\-id\=\"[a-zA-Z0-9-_]+\"\sclass\=\"[a-zA-Z0-9-_\s]*\"\>';
            if(preg_match('/'.$pattern.'/', $content)) {
                $content = preg_replace('/'.$pattern.'/', '', $content);
            }

            $pattern1 = '\<div\sclass\=\"[a-zA-Z0-9-_\s]*\"\sdata\-melis\-plugin\-tag\-id\=\"[a-zA-Z0-9-_]+\"\>';
            if(preg_match('/'.$pattern1.'/', $content)) {
                $content = preg_replace('/'.$pattern1.'/', '', $content);
            }


        }

        return $content;


    }
}