<?php

namespace MelisCms\PageEditor\Controller;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;
use MelisReactApi\Controller\CapabilityGuardTrait;

/**
 * EditionRenderController — first piece of the new, stateless React page-editor
 * PHP layer (branch evo/page-edition-react).
 *
 * Renders ONE plugin's HTML WITHOUT the legacy `meliscms` edit session: the page
 * content is supplied by the caller (client-owned state) and injected into the
 * plugin render through MelisEngine's `melistemplating_plugin_get_datas_db` seam.
 * When no content is supplied it falls back to the published DB (front render).
 *
 * This proves the architecture linchpin: the plugin framework (front()/back(),
 * the module contract) is reused as-is, driven by content WE own, with no session.
 *
 * Route: POST/GET /melis/react-api/cms-page/edition/render-plugin
 *   params: module, pluginName, pageId, pluginId ; optional body `contentXml`.
 *
 * Legacy is untouched: this is a new namespace, new route; the guard is the same
 * `meliscms_page` access as the rest of the page react-api.
 */
class EditionRenderController extends MelisAbstractActionController
{
    use CapabilityGuardTrait;
    use ReactApiPageGuardTrait;

    private const MELIS_KEY = 'meliscms_page';

    public function renderPluginAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) {
            return $deny;
        }

        try {
            $req        = $this->getRequest();
            $module     = (string) $req->getPost('module', $this->params()->fromQuery('module', ''));
            $pluginName = (string) $req->getPost('pluginName', $this->params()->fromQuery('pluginName', ''));
            $pageId     = (int) $req->getPost('pageId', $this->params()->fromQuery('pageId', 0));
            $pluginId   = (string) $req->getPost('pluginId', $this->params()->fromQuery('pluginId', ''));
            $contentXml = (string) $req->getPost('contentXml', '');

            if ($module === '' || $pluginName === '' || $pageId === 0 || $pluginId === '') {
                return $this->jsonResponse(['success' => false, 'error' => 'module, pluginName, pageId and pluginId are required'], 400);
            }

            $sm     = $this->getServiceManager();
            $config = $sm->get('config');
            if (empty($config['plugins'][$module]['plugins'][$pluginName])) {
                return $this->jsonResponse(['success' => false, 'error' => "plugin config not found: $module/$pluginName"], 404);
            }

            // --- inject caller-owned page content via the render data seam (no session) ---
            $injected = false;
            $shared   = $this->getEventManager()->getSharedManager();
            $listener = null;
            if ($contentXml !== '') {
                $injected = true;
                $listener = function ($e) use ($contentXml) {
                    $datas = $e->getParam('actualDatasPageTree');
                    if (!is_array($datas)) {
                        $datas = [];
                    }
                    $datas['page_content'] = $contentXml;
                    $e->setParam('actualDatasPageTree', $datas);
                };
                $shared->attach('*', 'melistemplating_plugin_get_datas_db', $listener, 100000);
            }

            try {
                // Mini-template plugin names carry a suffix; the real plugin is the prefix.
                $resolveName = $pluginName;
                $isMiniTpl   = strpos($pluginName, 'MiniTemplatePlugin') !== false;
                if ($isMiniTpl) {
                    $resolveName = explode('_', $pluginName)[0];
                }

                $plugin = $sm->get('ControllerPluginManager')->get($resolveName);
                if ($isMiniTpl && method_exists($plugin, 'setMiniTplPluginName')) {
                    $plugin->setMiniTplPluginName($pluginName);
                }
                if (method_exists($plugin, 'setPluginHardcoded')) {
                    $plugin->setPluginHardcoded(true); // an existing instance (has an id)
                }
                if (method_exists($plugin, 'setEncapsulatedPlugin')) {
                    $plugin->setEncapsulatedPlugin(true);
                }

                $view = $plugin->render(['pageId' => $pageId, 'id' => $pluginId], false);
                $html = $sm->get('ViewRenderer')->render($view);
            } finally {
                if ($listener !== null) {
                    $shared->detach($listener, '*', 'melistemplating_plugin_get_datas_db');
                }
            }

            return $this->jsonResponse([
                'success' => true,
                'data'    => [
                    'html'            => $html,
                    'stateless'       => true,
                    'sessionUsed'     => false,
                    'injectedContent' => $injected,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error'   => $e->getMessage(),
                'where'   => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }
}
