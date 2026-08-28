<?php

namespace MelisCms\PageEditor\Controller;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;
use MelisCms\PageEditor\PageContentProvider;

/**
 * EditionRenderPageController — the "clean edit render" (Phase 2, path C).
 *
 * Returns the FULL page (template + plugins + site CSS/JS) rendered by the front pipeline in
 * melis mode — so it is visually faithful and its blocks carry the addressable markers
 * (`data-plugin-id`, `data-dnd-id`) — but with the LEGACY EDIT JS REMOVED (MelisCms dragndrop /
 * jquery-ui / sortable / tinymce) and the legacy edit chrome hidden. On this JS-free, marked,
 * styled canvas the React editor can own ALL interaction (drag, menus, palette) without fighting
 * the legacy editor.
 *
 * The render is produced by an internal, authenticated request to the same URL the legacy edition
 * uses (`/id/<idPage>/renderMode/melis?melisSite=<ns>`), forwarding the caller's session cookies,
 * then post-processed. No legacy file is modified; nothing runs the legacy edit JS here.
 *
 * Route: GET /melis/react-api/cms-page/edition/render?idPage=X   → text/html
 */
class EditionRenderPageController extends MelisAbstractActionController
{
    use ReactApiPageGuardTrait;

    private const MELIS_KEY = 'meliscms_page';

    public function pageAction(): HttpResponse
    {
        /** @var HttpResponse $response */
        $response = $this->getResponse();

        // Guard (returns JSON on failure — fine, it's an error path).
        if ($deny = $this->denyUnlessAccess()) {
            return $deny;
        }

        try {
            $idPage = (int) $this->params()->fromQuery('idPage', 0);
            if ($idPage <= 0) {
                return $this->html($response, 400, '<!doctype html><meta charset=utf-8><p>idPage is required');
            }

            $ns = (new PageContentProvider($this->getServiceManager()))->siteNamespace($idPage);
            if ($ns === '') {
                return $this->html($response, 404, '<!doctype html><meta charset=utf-8><p>template/site not found for this page');
            }

            // The canvas shows the WORKING edit session: renderMode=melis reads
            // $_SESSION['meliscms']['content-pages'][idPage] in priority, which is exactly where the
            // React editor writes its edits (SessionContentStore) — so reorder/resize/add/remove/tag
            // edits show live. When the session is empty (page never edited) the plugins fall back to
            // the DB draft, so a fresh open renders the current saved/published content unchanged.
            [$html, $innerCode] = $this->fetchMelisRender($idPage, $ns);
            if ($html === null) {
                return $this->html($response, 502, '<!doctype html><meta charset=utf-8><p>internal render failed (inner HTTP ' . $innerCode . ')');
            }

            // Device preview: the canvas passes vw=375 (mobile) / 768 (tablet) / 0 (desktop). We bake a
            // fixed-width viewport meta so the page lays out at THAT width regardless of how the browser
            // resolves an iframe's viewport (changing it client-side after load doesn't relayout, and a
            // meta-less iframe isn't reliably element-width across engines) → deterministic reflow.
            $vw = (int) $this->params()->fromQuery('vw', 0);
            return $this->html($response, 200, $this->stripLegacyEdit($html, $vw));
        } catch (\Throwable $e) {
            return $this->html($response, 500, '<!doctype html><meta charset=utf-8><p>render error: ' . htmlspecialchars($e->getMessage()));
        }
    }

    private function html(HttpResponse $response, int $status, string $body): HttpResponse
    {
        $response->setStatusCode($status);
        $response->getHeaders()
            ->addHeaderLine('Content-Type', 'text/html; charset=utf-8')
            ->addHeaderLine('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->setContent($body);
        return $response;
    }

    /**
     * Internal authenticated render of the melis-mode page, via the front pipeline, forwarding the
     * caller's cookies + Host so the site resolves and the BO session is honoured.
     */
    private function fetchMelisRender(int $idPage, string $ns): array
    {
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $cookie = $_SERVER['HTTP_COOKIE'] ?? '';
        $url    = 'http://127.0.0.1/id/' . $idPage . '/renderMode/melis?melisSite=' . rawurlencode($ns);

        // Release the PHP session lock BEFORE the internal sub-request: this request holds the
        // session (opened by the auth guard); the inner render needs the same session and would
        // otherwise block on the lock → deadlock/timeout. We don't touch the session afterwards.
        if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Host: ' . $host,
                'Cookie: ' . $cookie,
                'X-Requested-With: XMLHttpRequest',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $out  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $ok = ($out !== false && $code === 200 && $out !== '');
        return [$ok ? (string) $out : null, $code];
    }

    /**
     * Remove the MelisCms edit JS (all `/MelisCms/js/*` = dragndrop / jquery-ui / touch-punch /
     * sortable, plus tinymce) so nothing legacy runs on the canvas; hide the legacy edit chrome.
     * The site's own CSS/JS (owl, bootstrap, wow…) and the `data-plugin-id`/`data-dnd-id` markers
     * are kept — the page displays faithfully and React owns interaction.
     */
    private function stripLegacyEdit(string $html, int $vw = 0): string
    {
        // <script src="…/MelisCms/js/…"></script>  and any tinymce script
        $html = (string) preg_replace('#<script\b[^>]*\bsrc="[^"]*/MelisCms/js/[^"]*"[^>]*>\s*</script>#i', '', $html);
        $html = (string) preg_replace('#<script\b[^>]*\bsrc="[^"]*tinymce[^"]*"[^>]*>\s*</script>#i', '', $html);
        // Edit-mode CSS (drag-drop / jquery-ui chrome styling) — display uses the SITE css only.
        $html = (string) preg_replace('#<link\b[^>]*\bhref="[^"]*/MelisCms/css/[^"]*"[^>]*>#i', '', $html);
        // Drop the site's responsive viewport meta, then (for a device preview) bake an explicit
        // fixed-width one. In an iframe `width=device-width` = the SCREEN width, so the page never
        // reflows to the frame; a fixed `width=375`/`768` forces the layout viewport → deterministic
        // reflow at that device width. vw=0 (desktop) → no meta → the iframe follows its element width.
        $html = (string) preg_replace('#<meta\b[^>]*\bname=(["\']?)viewport\1[^>]*>#i', '', $html);
        if ($vw > 0) {
            $meta = '<meta name="viewport" content="width=' . $vw . ', initial-scale=1, shrink-to-fit=no">';
            $html = stripos($html, '</head>') !== false
                ? (string) preg_replace('#</head>#i', $meta . '</head>', $html, 1)
                : $meta . $html;
        }
        // inline scripts that init/refer to the legacy edit machinery (would throw once its JS is gone)
        $html = (string) preg_replace_callback(
            '#<script\b(?![^>]*\bsrc=)[^>]*>(.*?)</script>#is',
            static function (array $m): string {
                return preg_match('#(DragDrop|dragdrop|dragndrop|dynamicDnd|\.sortable\(|melisCms\.|tinymce|initPlugin)#i', $m[1]) ? '' : $m[0];
            },
            $html
        );

        $inject = '<style id="melis-react-clean">'
            . '.melis-plugin-tools-box,.m-plugin-sub-tools,.dnd-plugin-sub-tools,.melis-plugin-title-box,'
            . '.dnd-layout-buttons,.dnd-plugin-title-and-sub-tools,.melis-plugin-indicator,.dnd-layout-indicator,'
            // Legacy floating plugin-menu bar (`#melisPluginBtn` toggle + JS-loaded palette). Styled/
            // positioned by the stripped MelisCms edit CSS/JS, so without them it falls back to a full-width
            // block at the page bottom showing a bare blue `fa-plug` icon. Pure legacy edit chrome — the
            // React editor owns plugin-adding — so hide it. (Not in Old edition/front: front has no chrome,
            // Old keeps the CSS that hides/floats it.)
            . '.melis-cms-dnd-box'
            . '{display:none!important}'
            . '[data-plugin-id][data-dnd-id]{outline:1px dashed rgba(220,38,38,.30);outline-offset:-1px}'
            // WYSIWYG width parity with the FRONT. In melis (edit) mode each block is wrapped in an EXTRA
            // `.melis-ui-outlined` edit-chrome div the front doesn't have, and for tags/mini-templates the
            // `plugin-width-*` class sits on the INNER content wrapper — so the block laid out differently
            // than the published front (a 50% block didn't shrink, siblings didn't wrap below). Make the
            // `.melis-ui-outlined` itself the float box that CARRIES the width (its plugin-width class is
            // already present for module plugins, and copied up from the tools-box data-attrs client-side
            // for tags — see onFrameLoad), and force every inner `[data-melis-plugin-tag-id]` wrapper to
            // fill it (100%, no double float). Result: a 50% block is 50% and wide siblings wrap below,
            // exactly like the front. Scoped to blocks inside a drag-drop column so hardcoded header/footer
            // plugins are untouched. Width VALUES come from the loaded plugin-width.min.css.
            . '[class^="plugin-width"]{margin:0}'
            . '.dnd-plugins-row > div[class*="dnd-plugins-col-"] > .melis-ui-outlined{float:left;box-sizing:border-box;max-width:100%}'
            . '.dnd-plugins-row > div[class*="dnd-plugins-col-"] > .melis-ui-outlined:not([class*="plugin-width"]){width:100%}'
            . '.melis-ui-outlined [data-melis-plugin-tag-id]{width:100%!important;float:none!important;margin:0!important;max-width:100%!important}'
            . '</style>';
        if (stripos($html, '</head>') !== false) {
            return (string) preg_replace('#</head>#i', $inject . '</head>', $html, 1);
        }
        return $inject . $html;
    }
}
