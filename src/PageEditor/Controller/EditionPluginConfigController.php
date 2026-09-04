<?php

namespace MelisCms\PageEditor\Controller;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use Laminas\Stdlib\Parameters;
use MelisCore\Controller\MelisAbstractActionController;
use MelisCms\PageEditor\PageContentProvider;
use MelisCms\PageEditor\SessionContentStore;

/**
 * EditionPluginConfigController — stateless plugin CONFIGURATION for the React page
 * editor (branch evo/page-edition-react, Phase 3 "config plugin — Part 2").
 *
 * This is the GENERIC, per-plugin-agnostic config path. It reuses the plugin's OWN
 * config machinery (`createOptionsForms()` for the tabbed form + validation,
 * `savePluginConfigToXml()` as the authoritative XML producer) but WITHOUT the legacy
 * `meliscms` edit session and WITHOUT the legacy `plugins.edition.js` orchestration:
 *
 *   - formAction  (GET  → text/html): a self-contained HTML page = the iframe body.
 *     Renders every tab of the plugin's `createOptionsForms()`, PREFILLED from the
 *     WORKING edit session (seeded from saved→published on first touch) injected through
 *     MelisEngine's `melistemplating_plugin_get_datas_db` seam. A tiny vanilla-JS harness
 *     collects the fields on Apply, POSTs them to saveAction and postMessages the result up.
 *
 *   - saveAction  (POST → JSON): validates via `createOptionsForms()` in validate mode
 *     (the plugin's own input filters), then calls the plugin's `savePluginConfigToXml()`
 *     to get the authoritative `<pluginXmlDbKey id=…>…</pluginXmlDbKey>` fragment and
 *     writes it into the WORKING edit session via `PageContentDocument::setPluginXml()` —
 *     identical persistence contract to EditionSaveController (session only, no DB, no
 *     save/publish event; the top toolbar's Save flushes the session to the draft).
 *
 * Both the generic iframe path AND the full-React form path (a native React component
 * per ported plugin) POST field name/value pairs to the SAME saveAction, so the XML is
 * always produced by the plugin itself — byte-compatible with the legacy reader.
 *
 * Defensive throughout: unknown plugin → 404; validation failure → errors, draft
 * untouched; empty producer output → no-op (setPluginXml refuses it).
 *
 * Routes:
 *   GET  /melis/react-api/cms-page/edition/plugin-config       (formAction)
 *   POST /melis/react-api/cms-page/edition/plugin-config/save  (saveAction)
 */
class EditionPluginConfigController extends MelisAbstractActionController
{
    use ReactApiPageGuardTrait;

    private const MELIS_KEY = 'meliscms_page';

    // ------------------------------------------------------------ form (iframe) ---

    public function formAction(): HttpResponse
    {
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        if ($deny = $this->denyUnlessAccess()) {
            return $deny; // JSON error is fine on this path
        }

        try {
            $idPage     = (int) $this->params()->fromQuery('idPage', 0);
            $module     = (string) $this->params()->fromQuery('module', '');
            $pluginName = (string) $this->params()->fromQuery('pluginName', '');
            $pluginId   = (string) $this->params()->fromQuery('pluginId', '');
            $theme      = $this->params()->fromQuery('theme', 'light') === 'dark' ? 'dark' : 'light';

            if ($idPage <= 0 || $module === '' || $pluginName === '' || $pluginId === '') {
                return $this->html($response, 400, $this->errorPage('Paramètres manquants (idPage, module, pluginName, pluginId).', $theme));
            }

            $sm = $this->getServiceManager();
            $config = $sm->get('config');
            if (empty($config['plugins'][$module]['plugins'][$pluginName])) {
                return $this->html($response, 404, $this->errorPage("Configuration du plugin introuvable : $module / $pluginName", $theme));
            }

            // Prefill source = our stateless draft (saved-first, published fallback).
            $draftXml = $this->draftContentXml($idPage);

            [$tabs, $tag, $err] = $this->buildTabs($idPage, $module, $pluginName, $pluginId, $draftXml);
            if ($err !== '') {
                return $this->html($response, 200, $this->errorPage($err, $theme));
            }

            return $this->html($response, 200, $this->formPage($idPage, $module, $pluginName, $pluginId, $tag, $tabs, $theme));
        } catch (\Throwable $e) {
            return $this->html($response, 500, $this->errorPage('Erreur de rendu : ' . htmlspecialchars($e->getMessage()), 'light'));
        }
    }

    // ------------------------------------------------------------------- save ---

    public function saveAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) {
            return $deny;
        }

        try {
            $payload    = json_decode((string) $this->getRequest()->getContent(), true);
            $idPage     = (int) ($payload['idPage'] ?? 0);
            $module     = (string) ($payload['module'] ?? '');
            $pluginName = (string) ($payload['pluginName'] ?? '');
            $pluginId   = (string) ($payload['pluginId'] ?? '');
            $values     = is_array($payload['values'] ?? null) ? $payload['values'] : [];

            if ($idPage <= 0 || $module === '' || $pluginName === '' || $pluginId === '') {
                return $this->jsonResponse(['success' => false, 'error' => 'idPage, module, pluginName and pluginId are required'], 400);
            }

            $sm = $this->getServiceManager();
            $config = $sm->get('config');
            if (empty($config['plugins'][$module]['plugins'][$pluginName])) {
                return $this->jsonResponse(['success' => false, 'error' => "plugin config not found: $module/$pluginName"], 404);
            }

            // The plugin reads its values from the request POST + a `validate` query flag.
            // Feed our submitted values through the shared request singleton (mirrors the
            // legacy hidden fields), then let the plugin's own input filters run.
            $meta = [
                'melisModule'     => $module,
                'melisPluginName' => $pluginName,
                'melisPluginId'   => $pluginId,
                'melisIdPage'     => (string) $idPage,
            ];
            $post = array_merge($values, $meta);

            $draftXml = $this->draftContentXml($idPage);

            $errorsTabs = [];
            $this->withRequest($post, true, function () use ($sm, $module, $pluginName, $pluginId, $idPage, $draftXml, &$errorsTabs) {
                $errorsTabs = $this->loadPlugin($sm, $module, $pluginName, $pluginId, $idPage, $draftXml)->createOptionsForms();
            });

            $ok = true;
            foreach ((array) $errorsTabs as $resp) {
                if (isset($resp['success']) && !$resp['success']) {
                    $ok = false;
                }
            }
            if (!$ok) {
                return $this->jsonResponse(['success' => false, 'errors' => $errorsTabs], 200);
            }

            // Authoritative XML fragment, produced by the plugin itself. Also grab its xmlDbKey (tag).
            $fragment = '';
            $tag = '';
            $this->withRequest($post, false, function () use ($sm, $module, $pluginName, $pluginId, $idPage, $draftXml, $post, &$fragment, &$tag) {
                $plugin = $this->loadPlugin($sm, $module, $pluginName, $pluginId, $idPage, $draftXml);
                $fragment = (string) $plugin->savePluginConfigToXml($post);
                if (method_exists($plugin, 'getPluginXmlDbKey')) {
                    $tag = (string) $plugin->getPluginXmlDbKey();
                }
            });

            $fragment = trim($fragment);
            $store = new SessionContentStore($sm);
            $changed = false;
            if ($fragment !== '') {
                // Persist into the working edit session (NOT the DB), exactly like EditionSaveController.
                $doc = $store->readDocument($idPage);
                if ($doc === null) {
                    return $this->jsonResponse(['success' => false, 'error' => 'page has no content'], 422);
                }
                if (!$doc->setPluginXml($pluginId, $fragment)) {
                    return $this->jsonResponse(['success' => false, 'error' => 'plugin fragment rejected (empty/malformed)'], 422);
                }
                $store->writeDocument($idPage, $doc);
                $changed = true;
            } else {
                // Plugin decided no change to its own fields — still seed the session so a contributed
                // tab (below) has the plugin node to enrich.
                $store->readDocument($idPage);
            }

            // GENERIC post-save seam: let OTHER modules react to a React plugin-config save. Mirrors the
            // legacy `meliscms_page_savesession_plugin_start` chain, but WITHOUT re-writing the plugin
            // fragment (so the width_* attrs setPluginXml preserved are kept). melis-cache-internal listens
            // here to persist its "Cache partiel" tab (partial_caching_code) into the session plugin XML.
            try {
                $em = $this->getEventManager();
                $em->addIdentifiers(['MelisCms']);
                $em->trigger('meliscms_react_plugin_config_saved', $this, [
                    'idPage'     => $idPage,
                    'module'     => $module,
                    'pluginName' => $pluginName,
                    'pluginId'   => $pluginId,
                    'tag'        => $tag,
                    'postValues' => array_merge($values, [
                        'melisPluginName' => $pluginName,
                        'melisPluginTag'  => $tag,
                        'melisPluginId'   => $pluginId,
                        'melisIdPage'     => (string) $idPage,
                        'idPage'          => $idPage,
                    ]),
                ]);
            } catch (\Throwable) {
                // Post-processing is best-effort; the plugin's own save already succeeded.
            }

            return $this->jsonResponse([
                'success' => true,
                'data'    => ['idPage' => $idPage, 'changed' => $changed, 'pluginId' => $pluginId],
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error'   => $e->getMessage(),
                'where'   => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }

    /**
     * Option lists a plugin's config SELECT fields need, for the NATIVE React forms (PluginFormKit
     * fetchFieldOptions). Runs the plugin's OWN form (createOptionsForms → HTML tabs, prefilled from the
     * draft, exactly like the iframe path) and parses every `<select>` → `fieldOptions[name] = [{value,
     * label}]`. This reuses the plugin's real Laminas form factories (template select, site select, GDPR
     * module select…) so options are correct for ANY select field WITHOUT re-implementing each factory.
     *
     * Route: GET /melis/react-api/cms-page/edition/plugin-config/options?module&pluginName&idPage[&pluginId]
     */
    public function optionsAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) {
            return $deny;
        }
        try {
            $module     = (string) $this->params()->fromQuery('module', '');
            $pluginName = (string) $this->params()->fromQuery('pluginName', '');
            $pluginId   = (string) $this->params()->fromQuery('pluginId', '');
            $idPage     = (int) $this->params()->fromQuery('idPage', 0);

            $fieldOptions = [];
            $fieldValues  = [];
            $fieldList    = [];
            try {
                $draftXml = $this->draftContentXml($idPage);
                [$tabs] = $this->buildTabs($idPage, $module, $pluginName, $pluginId, $draftXml);
                foreach ((array) $tabs as $t) {
                    $html = (string) ($t['html'] ?? '');
                    foreach ($this->parseSelectOptions($html) as $name => $opts) {
                        $fieldOptions[$name] = $opts;
                    }
                    // Current RESOLVED value of each field (input value / selected option / textarea) — this
                    // is how HARDCODED plugins (menu…) prefill: their value comes from the template params /
                    // front-config defaults, NOT the page XML, so reading the page node alone gives nothing.
                    foreach ($this->parseFieldValues($html) as $name => $val) {
                        $fieldValues[$name] = $val;
                    }
                    // A `fields[]` / `required_fields[]` grid (e.g. prospects "Field list") → structured rows.
                    if ($fieldList === []) {
                        $fieldList = $this->parseFieldList($html);
                    }
                }
            } catch (\Throwable) {
            }

            // template_path fallback (config) if the form didn't render one (base plugin default).
            if (empty($fieldOptions['template_path'])) {
                $tpls = (array) ($this->getServiceManager()->get('config')['plugins'][$module]['plugins'][$pluginName]['front']['template_path'] ?? []);
                foreach ($tpls as $t) {
                    $fieldOptions['template_path'][] = ['value' => (string) $t, 'label' => (string) $t];
                }
            }

            return $this->jsonResponse(['success' => true, 'data' => [
                'fieldOptions'    => $fieldOptions,
                'fieldValues'     => $fieldValues,
                'fieldList'       => $fieldList,
                'templateOptions' => $fieldOptions['template_path'] ?? [],
            ]]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * The full declarative FORM SCHEMA of a plugin's config, derived from its OWN createOptionsForms()
     * HTML — the SAME source the legacy iframe renders, so this is the legacy declaration verbatim, just
     * expressed as JSON. The React SchemaForm renders it natively at RUNTIME → a plugin created live (no
     * build) gets a React config for free, while the legacy editor keeps rendering the identical form and
     * both read/write the same byte-compatible XML (golden rule: legacy never breaks).
     * GET …/edition/plugin-config/schema?idPage&module&pluginName&pluginId
     *   → { tabs:[{id,title,fields:[{name,type,label,hint,required,value,options?,rows?}]}], tag }
     */
    public function schemaAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) {
            return $deny;
        }
        try {
            $module     = (string) $this->params()->fromQuery('module', '');
            $pluginName = (string) $this->params()->fromQuery('pluginName', '');
            $pluginId   = (string) $this->params()->fromQuery('pluginId', '');
            $idPage     = (int) $this->params()->fromQuery('idPage', 0);

            $draftXml = $this->draftContentXml($idPage);
            [$tabs, $tag] = $this->buildTabs($idPage, $module, $pluginName, $pluginId, $draftXml);

            $out = [];
            foreach ((array) $tabs as $i => $t) {
                if (!empty($t['empty'])) {
                    continue;
                }
                $fields = $this->parseSchemaFields((string) ($t['html'] ?? ''));
                if ($fields === []) {
                    continue;
                }
                // MelisCacheInternal's legacy "Partial Caching" tab (injected into every plugin's
                // modal_form by MelisCacheInternalPartialCachingFormConfigListener) is intentionally
                // dropped from the SCHEMA specifically — the React side already contributes an
                // equivalent native "Partial cache" tab globally (PluginFormKit's GLOBAL_PLUGIN_TABS,
                // registered from melis-cache-internal/ui-react/plugin-config/MelisCacheInternalPartialCaching).
                // Without this, a plugin whose schema is used natively in React shows both. The legacy
                // iframe path (formAction/optionsAction) is untouched — it still renders this tab itself.
                // Matched by the `partial_caching_code` field name (stable, unlike the tab TITLE which is
                // already translated to a locale string by the time it reaches here).
                if (in_array('partial_caching_code', array_column($fields, 'name'), true)) {
                    continue;
                }
                $out[] = [
                    'id'     => 'tab' . $i,
                    'title'  => (string) ($t['name'] ?? ('Tab ' . ($i + 1))),
                    'fields' => $fields,
                ];
            }

            return $this->jsonResponse(['success' => true, 'data' => ['tabs' => $out, 'tag' => $tag]]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * A page's display name by id — so the React page-picker fields (PagePicker) can show the page NAME
     * instead of just its id. Draft-first (saved → published). Route:
     * GET /melis/react-api/cms-page/edition/page-title?id=X → { id, title }.
     */
    public function pageTitleAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) {
            return $deny;
        }
        $id = (int) $this->params()->fromQuery('id', 0);
        $title = '';
        if ($id > 0) {
            try {
                $melisPage = $this->getServiceManager()->get('MelisEnginePage');
                foreach (['saved', 'published'] as $src) {
                    $dp = $melisPage->getDatasPage($id, $src);
                    $tree = $dp ? $dp->getMelisPageTree() : null;
                    if (!empty($tree->page_name)) {
                        $title = (string) $tree->page_name;
                        break;
                    }
                }
            } catch (\Throwable) {
            }
        }
        return $this->jsonResponse(['success' => true, 'data' => ['id' => $id, 'title' => $title]]);
    }

    /**
     * Parse the `<select name=…>` fields out of a rendered form-tab's HTML → name ⇒ [{value,label}].
     * The empty placeholder option (value="") is dropped — the React select supplies its own.
     *
     * @return array<string,array<int,array{value:string,label:string}>>
     */
    private function parseSelectOptions(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }
        $dom  = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors($prev);

        $out = [];
        foreach ($dom->getElementsByTagName('select') as $sel) {
            /** @var \DOMElement $sel */
            $name = $sel->getAttribute('name');
            if ($name === '') {
                continue;
            }
            $opts = [];
            foreach ($sel->getElementsByTagName('option') as $o) {
                /** @var \DOMElement $o */
                $value = $o->getAttribute('value');
                if ($value === '') {
                    continue;
                }
                $opts[] = ['value' => $value, 'label' => trim($o->textContent)];
            }
            $out[$name] = $opts;
        }
        return $out;
    }

    /**
     * Parse the CURRENT value of each field out of a rendered form-tab's HTML: an input's `value`, a
     * select's `selected` option, a textarea's text. This carries the RESOLVED value (template params /
     * front-config defaults + draft overrides), so hardcoded plugins prefill correctly in the React form.
     *
     * @return array<string,string>
     */
    private function parseFieldValues(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }
        $dom  = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors($prev);

        $out = [];
        foreach ($dom->getElementsByTagName('input') as $el) {
            /** @var \DOMElement $el */
            $name = $el->getAttribute('name');
            $type = strtolower($el->getAttribute('type'));
            if ($name === '' || in_array($type, ['submit', 'button', 'file'], true)) {
                continue;
            }
            if (($type === 'checkbox' || $type === 'radio') && !$el->hasAttribute('checked')) {
                continue;
            }
            $out[$name] = $el->getAttribute('value');
        }
        foreach ($dom->getElementsByTagName('textarea') as $el) {
            /** @var \DOMElement $el */
            $name = $el->getAttribute('name');
            if ($name !== '') {
                $out[$name] = $el->textContent;
            }
        }
        foreach ($dom->getElementsByTagName('select') as $sel) {
            /** @var \DOMElement $sel */
            $name = $sel->getAttribute('name');
            if ($name === '') {
                continue;
            }
            $val = '';
            foreach ($sel->getElementsByTagName('option') as $o) {
                /** @var \DOMElement $o */
                if ($o->hasAttribute('selected')) {
                    $val = $o->getAttribute('value');
                    break;
                }
            }
            $out[$name] = $val;
        }
        return $out;
    }

    /**
     * Parse a `fields[]` / `required_fields[]` GRID (e.g. prospects' "Field list" tab) into ordered rows
     * {name,label,shown,required} — so the React form can render a native show/mandatory grid. Each field
     * is a `.module-cont` row with a label, a `fields[]` checkbox (shown) and a `required_fields[]` one.
     *
     * @return array<int,array{name:string,label:string,shown:bool,required:bool}>
     */
    private function parseFieldList(string $html): array
    {
        if (stripos($html, 'fields[]') === false) {
            return [];
        }
        $dom  = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors($prev);
        $xp = new \DOMXPath($dom);

        $out = [];
        foreach ($xp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' module-cont ')]") as $row) {
            $fieldsInput = null;
            $reqInput = null;
            $label = '';
            $h4 = $xp->query('.//h4', $row);
            if ($h4->length) {
                $label = trim($h4->item(0)->textContent);
            }
            foreach ($xp->query('.//input', $row) as $inp) {
                /** @var \DOMElement $inp */
                $n = $inp->getAttribute('name');
                if ($n === 'fields[]') {
                    $fieldsInput = $inp;
                } elseif ($n === 'required_fields[]') {
                    $reqInput = $inp;
                }
            }
            if ($fieldsInput === null) {
                continue;
            }
            $name = $fieldsInput->getAttribute('value');
            if ($name === '') {
                continue;
            }
            $out[] = [
                'name'     => $name,
                'label'    => $label !== '' ? $label : $name,
                'shown'    => $fieldsInput->hasAttribute('checked'),
                'required' => $reqInput ? $reqInput->hasAttribute('checked') : false,
            ];
        }
        return $out;
    }

    /**
     * Turn ONE rendered config-tab's HTML into an ordered list of field descriptors for the React
     * SchemaForm: {name,type,label,hint,required,value,options?,rows?}. Types map to the shared field kit
     * (text/number/date/select/page/textarea/checkbox/fieldlist). Everything is READ from the plugin's own
     * rendered form (labels already translated, selects already resolved, values prefilled) — no config
     * guessing, so it stays identical to what the legacy iframe shows.
     *
     * @return array<int,array<string,mixed>>
     */
    private function parseSchemaFields(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }
        $list = $this->parseFieldList($html);         // fields[]/required_fields[] grid → ONE composite field
        $options = $this->parseSelectOptions($html);
        $values  = $this->parseFieldValues($html);

        $dom  = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors($prev);
        $xp = new \DOMXPath($dom);

        $labels = [];
        foreach ($xp->query('//label[@for]') as $lab) {
            /** @var \DOMElement $lab */
            $labels[$lab->getAttribute('for')] = trim($lab->textContent);
        }

        $fields = [];
        $seen = [];
        $listDone = false;
        foreach ($xp->query('//input | //select | //textarea') as $el) {
            /** @var \DOMElement $el */
            $tag  = strtolower($el->nodeName);
            $name = $el->getAttribute('name');
            $type = strtolower($el->getAttribute('type'));

            if ($name === '' || in_array($type, ['submit', 'button', 'hidden'], true)) {
                continue;
            }
            if ($name === 'fields[]' || $name === 'required_fields[]') {
                if ($list && !$listDone) {
                    $fields[] = ['name' => 'fields', 'type' => 'fieldlist', 'label' => '', 'hint' => '', 'required' => false, 'rows' => $list];
                    $listDone = true;
                }
                continue;
            }
            if (isset($seen[$name])) {
                continue;   // radio groups / duplicates: first wins
            }

            $id    = $el->getAttribute('id');
            $label = trim(rtrim($labels[$id] ?? '', " *"));
            $hint  = $el->getAttribute('data-bs-title') ?: $el->getAttribute('title');

            if ($tag === 'select') {
                $kind = 'select';
            } elseif ($tag === 'textarea') {
                $kind = 'textarea';
            } elseif ($type === 'checkbox') {
                $kind = 'checkbox';
            } elseif ($type === 'number') {
                $kind = 'number';
            } elseif ($type === 'date') {
                $kind = 'date';
            } else {
                $class = $el->getAttribute('class');
                if ($el->getAttribute('data-button-id') === 'meliscms-site-selector') {
                    $kind = 'page';
                } elseif (stripos($class, 'datepicker') !== false || $el->hasAttribute('data-date-format')) {
                    $kind = 'date';
                } else {
                    $kind = 'text';
                }
            }

            $f = [
                'name'     => $name,
                'type'     => $kind,
                'label'    => $label !== '' ? $label : $name,
                'hint'     => (string) $hint,
                'required' => $el->hasAttribute('required'),
                'value'    => (string) ($values[$name] ?? ''),
            ];
            if ($kind === 'select') {
                $f['options'] = $options[$name] ?? [];
            }
            $fields[] = $f;
            $seen[$name] = true;
        }
        return $fields;
    }

    // ------------------------------------------------------------- internals ---

    /** The current working page_content (edit session, seeded from saved→published) as XML, or ''. */
    private function draftContentXml(int $idPage): string
    {
        try {
            return (string) ((new SessionContentStore($this->getServiceManager()))->readXml($idPage) ?? '');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Load a plugin instance ready for config work: fresh from the ControllerPluginManager,
     * its config resolved against OUR draft content (injected through the render-data seam,
     * no session). Returns the plugin; call createOptionsForms()/savePluginConfigToXml() on it.
     */
    private function loadPlugin($sm, string $module, string $pluginName, string $pluginId, int $idPage, string $draftXml)
    {
        // $draftXml is the WORKING edit session composed to a full document, so it already carries
        // every in-progress edit. We inject it through the render-data seam AND leave the session in
        // place: in melis mode the plugin reads that same session, so whichever wins gives the same
        // prefill. (We must NOT clear the session here — that would wipe the user's unsaved edits.)
        $shared   = $this->getEventManager()->getSharedManager();
        $listener = null;
        if ($draftXml !== '') {
            $listener = function ($e) use ($draftXml) {
                $datas = $e->getParam('actualDatasPageTree');
                if (!is_array($datas)) { $datas = []; }
                $datas['page_content'] = $draftXml;
                $e->setParam('actualDatasPageTree', $datas);
            };
            $shared->attach('*', 'melistemplating_plugin_get_datas_db', $listener, 100000);
        }

        try {
            // Site context so site-scoped form elements (template selects, etc.) can populate.
            $ns = '';
            try { $ns = (new PageContentProvider($sm))->siteNamespace($idPage); } catch (\Throwable) {}

            // Site-scoped form elements read their options from the REQUEST query `parameters` (e.g.
            // PluginTemplateSelectFactory → config[plugins][module][plugins][name]['front']['template_path']
            // + the site's own config). The legacy modal URL carries parameters[module|pluginName|siteModule];
            // our render request doesn't, so the Template list came up EMPTY. Inject it here.
            $this->getRequest()->getQuery()->set('parameters', [
                'module'     => $module,
                'pluginName' => $pluginName,
                'siteModule' => $ns,
                'pluginId'   => $pluginId,
                'melisActivePageId' => $idPage,
            ]);

            $plugin = $sm->get('ControllerPluginManager')->get($pluginName);
            $updates = ['id' => $pluginId, 'pageId' => $idPage];
            if ($ns !== '') {
                $updates['melisSite'] = $ns;
                $updates['siteModule'] = $ns;
            }
            $plugin->setUpdatesPluginConfig($updates);
            $plugin->getPluginConfigs();
            return $plugin;
        } finally {
            if ($listener !== null) {
                $shared->detach($listener, '*', 'melistemplating_plugin_get_datas_db');
            }
        }
    }

    /**
     * Build the render-mode tabs for a plugin (prefilled from the draft).
     * @return array{0:array<int,array<string,mixed>>,1:string,2:string} [tabs, xmlDbKey, error]
     */
    private function buildTabs(int $idPage, string $module, string $pluginName, string $pluginId, string $draftXml): array
    {
        $sm = $this->getServiceManager();
        $tabs = [];
        $tag = '';
        $error = '';
        try {
            // Render mode = NO `validate` in the query (createOptionsForms returns HTML tabs).
            $this->withRequest([], false, function () use ($sm, $module, $pluginName, $pluginId, $idPage, $draftXml, &$tabs, &$tag) {
                $plugin = $this->loadPlugin($sm, $module, $pluginName, $pluginId, $idPage, $draftXml);
                $tabs = (array) $plugin->createOptionsForms();
                if (method_exists($plugin, 'getPluginXmlDbKey')) {
                    $tag = (string) $plugin->getPluginXmlDbKey();
                }
            });
        } catch (\Throwable $e) {
            $error = 'Le formulaire du plugin n’a pas pu être généré : ' . htmlspecialchars($e->getMessage());
        }
        return [$tabs, $tag, $error];
    }

    /**
     * Run $fn with the shared request temporarily carrying $post as POST and, when
     * $validate, a `validate` GET flag — restoring both afterwards. This is how the
     * plugin's own createOptionsForms()/savePluginConfigToXml() see our submitted data
     * without any session. The 'request' service is the singleton the plugin reads.
     */
    private function withRequest(array $post, bool $validate, callable $fn): void
    {
        $req   = $this->getRequest();
        $query = $req->getQuery();

        $prevPost     = $req->getPost()->toArray();
        $prevValidate = $query->get('validate', null);

        $req->setPost(new Parameters($post));
        if ($validate) {
            $query->set('validate', '1');
        } else {
            // ensure render mode (no validate)
            if ($query->offsetExists('validate')) {
                $query->offsetUnset('validate');
            }
        }

        try {
            $fn();
        } finally {
            $req->setPost(new Parameters($prevPost));
            if ($prevValidate === null) {
                if ($query->offsetExists('validate')) {
                    $query->offsetUnset('validate');
                }
            } else {
                $query->set('validate', $prevValidate);
            }
        }
    }

    // --------------------------------------------------------------- HTML out ---

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
     * The back-office WIDGET libraries (jQuery + jQuery UI + bootstrap-datepicker + select2), loaded
     * synchronously in the iframe head. This is how "special" fields work WITHOUT touching any plugin
     * code: a plugin's tab template renders its OWN inline init (`$("#date_min").datepicker(…)`,
     * select2, etc.) — we render that verbatim, and providing the same runtime the BO has makes those
     * inits run. bootstrap-datepicker is loaded AFTER jQuery UI so its `.datepicker()` API (the one the
     * plugins use) wins. Add a lib here if a plugin needs a widget not yet covered.
     */
    private function widgetAssets(): string
    {
        // The COMMON BO form-widget runtime — a deliberately MINIMAL, standalone set (jQuery + jQuery UI +
        // bootstrap-datepicker + select2 + font-awesome). Each config field self-initialises via its own
        // inline script; we just provide the same libs the BO has. Covers the widespread field types
        // (date fields, searchable selects, jQuery-UI widgets) across ALL plugins, including custom ones.
        //
        // Deliberately NOT loaded: libs that drag heavy BO dependencies. Verified failures when added
        // naively — datetimepicker needs moment.js; `melisHelper.js` throws `initCategorySelectField is
        // not defined` (needs melisCore + more) and so the functional clear/eraser button and the global
        // `melisHelper` some plugin JS calls are NOT wired here. To support such a widget for a project
        // plugin: add its lib AND its real dependencies below (or do a proper BO-form-runtime pass).
        $css = [
            '/MelisCore/assets/components/library/jquery-ui/css/jquery-ui.min.css',
            '/MelisCore/assets/components/modules/admin/forms/elements/bootstrap-datepicker/assets/lib/css/bootstrap-datepicker.css',
            '/MelisCore/assets/components/plugins/select2/css/select2.min.css',
            '/MelisCore/assets/components/library/icons/fontawesome/assets/css/font-awesome.min.css', // form icons (eraser…)
        ];
        $js = [
            '/MelisCore/assets/components/library/jquery/jquery.min.js',
            '/MelisCore/assets/components/library/jquery-ui/js/jquery-ui.min.js',
            '/MelisCore/assets/components/library/bootstrap/js/bootstrap.bundle.min.js', // Popper incl. → tooltips
            '/MelisCore/assets/components/modules/admin/forms/elements/bootstrap-datepicker/assets/lib/js/bootstrap-datepicker.js',
            '/MelisCore/assets/components/plugins/select2/js/select2.full.min.js',
        ];
        $out = '';
        foreach ($css as $u) {
            $out .= '<link rel="stylesheet" href="' . $u . '">';
        }
        foreach ($js as $u) {
            $out .= '<script src="' . $u . '"></script>';
        }
        // Anchor any datepicker popover directly UNDER its field and scroll it into view. In this short
        // iframe bootstrap-datepicker's `auto` orientation flips the calendar UPWARD and overlaps the
        // header; this re-anchors it downward generically (jQuery is loaded above). No plugin code touched.
        $out .= '<script>(function(){function b(){if(!window.jQuery){return setTimeout(b,30);}'
            // datepicker popover: anchor under its field + scroll into view (the short iframe flips its auto orientation up)
            . 'jQuery(document).on("show",function(e){var t=e.target;if(!t||!t.getBoundingClientRect)return;'
            . 'var dp=document.querySelector(".datepicker-dropdown");if(!dp)return;var r=t.getBoundingClientRect();'
            . 'dp.style.position="absolute";dp.style.top=(window.scrollY+r.bottom+2)+"px";dp.style.left=(window.scrollX+r.left)+"px";'
            . 'dp.classList.remove("datepicker-orient-bottom");dp.classList.add("datepicker-orient-top");'
            . 'setTimeout(function(){try{t.scrollIntoView({block:"center"});}catch(x){}},0);});'
            // Bootstrap-5 tooltips: the fields use data-bs-toggle="tooltip" with the text in data-bs-title (title
            // is empty) — they need an explicit init (the BO does it globally; we do it once the form DOM is ready).
            . 'function tips(){if(!(window.bootstrap&&bootstrap.Tooltip))return;document.querySelectorAll(\'[data-bs-toggle="tooltip"]\').forEach(function(el){try{bootstrap.Tooltip.getOrCreateInstance(el);}catch(x){}});}'
            . 'if(document.readyState!=="loading"){tips();}else{document.addEventListener("DOMContentLoaded",tips);}'
            // Page-selector field: the legacy button opens a BO fancytree modal (melisHelper) we don't have.
            // Bridge it to the React parent PagePicker via postMessage — generic for ANY plugin field using
            // the melis "page tree id selector" widget (input[data-button-id="meliscms-site-selector"]).
            . 'var __pf=null;'
            . 'document.addEventListener("click",function(e){var g=e.target.closest&&e.target.closest(".input-group");if(!g)return;'
            . 'var inp=g.querySelector(\'input[data-button-id="meliscms-site-selector"],input[data-button-id*="site-selector"]\');'
            . 'if(!inp||e.target===inp)return;e.preventDefault();e.stopPropagation();__pf=inp;'
            . 'parent.postMessage({type:"melis-open-page-picker",value:inp.value||""},"*");},true);'
            . 'window.addEventListener("message",function(e){var d=e.data;if(!d||d.type!=="melis-page-picked"||!__pf)return;'
            . '__pf.value=d.pageId;__pf.dispatchEvent(new Event("change",{bubbles:true}));});'
            . '}b();})();</script>';
        return $out;
    }

    /** Shared minimal styling (theme-aware) for the iframe body. */
    private function pageStyle(string $theme): string
    {
        $accent = $theme === 'dark' ? '#3b82f6' : '#dc2626';
        $bg     = $theme === 'dark' ? '#0f172a' : '#ffffff';
        $fg     = $theme === 'dark' ? '#e2e8f0' : '#1f2937';
        $muted  = $theme === 'dark' ? '#94a3b8' : '#6b7280';
        $border = $theme === 'dark' ? '#334155' : '#e5e7eb';
        $field  = $theme === 'dark' ? '#1e293b' : '#ffffff';
        return "<style>
:root{--accent:$accent}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{font:13px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:$fg;background:$bg;padding:14px}
.pcf-tabs{display:flex;gap:4px;border-bottom:1px solid $border;margin-bottom:14px;flex-wrap:wrap}
.pcf-tab{padding:7px 12px;border:0;background:transparent;color:$muted;cursor:pointer;border-bottom:2px solid transparent;font:inherit}
.pcf-tab.active{color:var(--accent);border-bottom-color:var(--accent);font-weight:600}
.pcf-pane{display:none}
.pcf-pane.active{display:block}
.pcf-pane form{margin:0}
label{display:block;margin:10px 0 4px;font-weight:600;color:$fg}
input[type=text],input[type=number],input[type=url],input[type=email],select,textarea{
  width:100%;padding:7px 9px;border:1px solid $border;border-radius:6px;background:$field;color:$fg;font:inherit}
textarea{min-height:90px}
.form-group,.form-control-wrapper{margin-bottom:6px}
.pcf-actions{position:sticky;bottom:0;background:$bg;display:flex;gap:8px;justify-content:flex-end;padding-top:14px;margin-top:14px;border-top:1px solid $border}
.pcf-btn{padding:8px 16px;border-radius:6px;border:1px solid $border;background:transparent;color:$fg;cursor:pointer;font:inherit}
.pcf-btn.primary{background:var(--accent);border-color:var(--accent);color:#fff;font-weight:600}
.pcf-btn[disabled]{opacity:.5;cursor:default}
.pcf-msg{padding:9px 11px;border-radius:6px;margin-bottom:12px;display:none}
.pcf-msg.err{display:block;background:rgba(220,38,38,.12);color:#dc2626;border:1px solid rgba(220,38,38,.3)}
.pcf-empty{color:$muted;padding:20px 0}
.pcf-fielderr{color:#dc2626;font-size:12px;margin-top:3px}
/* Minimal Bootstrap-5 grid + a few utilities (we don't load Bootstrap CSS — it would fight the harness
   theme). Plugin forms laid out with .row/.col-* (e.g. prospects 'Field list': Fields|Status|Mandatory
   columns) were stacking vertically without it. */
.row{display:flex;flex-wrap:wrap;align-items:center;margin:0 -8px}
.row>[class*=\"col-\"]{padding:3px 8px;box-sizing:border-box}
.col-1{width:8.333%}.col-2{width:16.666%}.col-3{width:25%}.col-4{width:33.333%}.col-5{width:41.666%}.col-6{width:50%}
.col-7{width:58.333%}.col-8{width:66.666%}.col-9{width:75%}.col-10{width:83.333%}.col-11{width:91.666%}.col-12{width:100%}
.text-center{text-align:center}.text-right,.text-end{text-align:right}
.d-flex{display:flex}.flex-row{flex-direction:row}.flex-column{flex-direction:column}
.justify-content-between{justify-content:space-between}.justify-content-center{justify-content:center}.align-items-center{align-items:center}
input[type=checkbox],input[type=radio]{width:auto !important;height:auto;margin:0;vertical-align:middle}
/* Bootstrap-5 tooltips: the JS (Popper) is loaded but NOT Bootstrap's CSS, so the info bubbles had no
   background and no positioning — the text left the flow and sprawled across the form. Provide the
   minimal tooltip skin (position + background box + wrapping) here. */
.tooltip{position:absolute;z-index:100001;display:block;margin:0;font-family:inherit;font-size:12px;line-height:1.45;text-align:left;white-space:normal;word-wrap:break-word;opacity:1}
.tooltip.fade:not(.show){opacity:0}
.tooltip .tooltip-inner{max-width:280px;padding:7px 10px;color:#f9fafb;text-align:left;background:#1f2937;border:1px solid rgba(255,255,255,.14);border-radius:6px;box-shadow:0 8px 24px rgba(0,0,0,.45)}
.tooltip .tooltip-arrow{display:none}
/* Legacy widget popovers must FLOAT (the `dropdown-menu` class dropped their position to static, with
   no Bootstrap loaded here) AND be fully themed (their panel background came from Bootstrap too). */
.datepicker.datepicker-dropdown{position:absolute !important;z-index:100000;background:$field;border:1px solid $border;border-radius:8px;padding:8px;box-shadow:0 12px 34px rgba(0,0,0,.35);color:$fg;width:auto;min-width:0}
.datepicker table{margin:0;width:auto;background:transparent}
.datepicker table tr td,.datepicker table tr th{color:$fg;width:34px;height:30px;text-align:center;border-radius:6px;border:0;background:transparent;font-weight:500;padding:0}
.datepicker table tr th.dow{color:$muted;font-weight:700;font-size:11px}
.datepicker table tr th.datepicker-switch{font-weight:700}
.datepicker table tr th.prev,.datepicker table tr th.next{font-size:16px;color:$fg}
.datepicker table tr td.day:hover,.datepicker table tr td.focused,.datepicker table tr th.datepicker-switch:hover,.datepicker table tr th.prev:hover,.datepicker table tr th.next:hover{background:color-mix(in srgb,var(--accent) 18%,transparent);cursor:pointer}
.datepicker table tr td.old,.datepicker table tr td.new{color:$muted}
.datepicker table tr td.today{background:color-mix(in srgb,var(--accent) 22%,transparent);color:$fg}
.datepicker table tr td.active,.datepicker table tr td.active:hover,.datepicker table tr td.active.active{background:var(--accent) !important;color:#fff !important}
.select2-container{z-index:100001;width:100% !important}
.select2-dropdown{background:$field;color:$fg;border-color:$border;z-index:100002}
.select2-container--default .select2-selection--single{background:$field;border-color:$border;height:36px}
.select2-container--default .select2-selection--single .select2-selection__rendered{color:$fg;line-height:34px}
.select2-container--default .select2-results__option--highlighted[aria-selected]{background:var(--accent)}
/* Force fields to the harness theme even when a plugin template hardcodes light colours inline
   (e.g. news hardcodes input#date_min background white) — id specificity beats ours, so !important.
   NOTE: never write a literal style close-tag inside this CSS — it terminates the <style> block early. */
.pcf-pane input,.pcf-pane select,.pcf-pane textarea{background:$field !important;color:$fg !important;border-color:$border !important}
.pcf-pane input::placeholder{color:$muted}
/* Rebuild Bootstrap's input-group inline layout (no Bootstrap CSS here) so addon buttons (the clear
   'eraser') sit BESIDE the field instead of wrapping under it as a stray bar. */
.input-group{display:flex;align-items:stretch;width:100%}
.input-group>.form-control{flex:1 1 auto;width:1%;border-radius:6px 0 0 6px}
.input-group-btn{display:flex}
.input-group-btn>.btn{display:inline-flex;align-items:center;border:1px solid $border;background:$field !important;color:$fg !important;padding:0 11px;cursor:pointer;border-radius:0 6px 6px 0;margin-left:-1px}
.input-group-btn>.btn:hover{background:color-mix(in srgb,var(--accent) 16%,transparent) !important}
</style>";
    }

    private function errorPage(string $message, string $theme): string
    {
        return '<!doctype html><html><head><meta charset="utf-8">' . $this->pageStyle($theme) . '</head><body>'
            . '<div class="pcf-msg err" style="display:block">' . $message . '</div>'
            . '</body></html>';
    }

    /**
     * The self-contained iframe page: tabs + forms (verbatim plugin HTML) + a vanilla-JS
     * harness that collects fields across all tab forms and POSTs them to saveAction.
     *
     * @param array<int,array<string,mixed>> $tabs
     */
    private function formPage(int $idPage, string $module, string $pluginName, string $pluginId, string $tag, array $tabs, string $theme): string
    {
        // Filter out placeholder "empty" tabs with no real form (base plugin default).
        $real = [];
        foreach ($tabs as $t) {
            if (!empty($t['empty'])) {
                continue;
            }
            $real[] = $t;
        }

        $saveUrl = '/melis/react-api/cms-page/edition/plugin-config/save';

        $head = '<!doctype html><html data-theme="' . $theme . '"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . $this->widgetAssets()   // BO widget libs so the plugin's own inline inits (datepicker…) run
            . $this->pageStyle($theme) . '</head><body>';

        if (count($real) === 0) {
            return $head
                . '<div class="pcf-empty">Ce plugin n’expose pas de configuration éditable.</div>'
                . '</body></html>';
        }

        $tabsNav = '<div class="pcf-tabs" role="tablist">';
        $panes   = '';
        foreach ($real as $i => $t) {
            $name = htmlspecialchars((string) ($t['name'] ?? ('Onglet ' . ($i + 1))), ENT_QUOTES);
            $active = $i === 0 ? ' active' : '';
            $tabsNav .= '<button type="button" class="pcf-tab' . $active . '" data-i="' . $i . '">' . $name . '</button>';
            // The plugin HTML is trusted BO output (Laminas form render); embed verbatim.
            $panes .= '<div class="pcf-pane' . $active . '" data-i="' . $i . '">' . ((string) ($t['html'] ?? '')) . '</div>';
        }
        $tabsNav .= '</div>';

        $ctx = json_encode([
            'idPage'     => $idPage,
            'module'     => $module,
            'pluginName' => $pluginName,
            'pluginId'   => $pluginId,
            'tag'        => $tag,
            'saveUrl'    => $saveUrl,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $script = <<<'JS'
<script>
(function(){
  var CTX = __CTX__;
  var tabs = Array.prototype.slice.call(document.querySelectorAll('.pcf-tab'));
  var panes = Array.prototype.slice.call(document.querySelectorAll('.pcf-pane'));
  tabs.forEach(function(btn){
    btn.addEventListener('click', function(){
      var i = btn.getAttribute('data-i');
      tabs.forEach(function(b){ b.classList.toggle('active', b.getAttribute('data-i')===i); });
      panes.forEach(function(p){ p.classList.toggle('active', p.getAttribute('data-i')===i); });
    });
  });

  function collect(){
    // Gather every field across all tab forms (the legacy layout uses one <form> per tab).
    var values = {};
    var els = document.querySelectorAll('.pcf-pane input, .pcf-pane select, .pcf-pane textarea');
    Array.prototype.forEach.call(els, function(el){
      var name = el.getAttribute('name');
      if (!name) return;
      if (el.type === 'checkbox') { values[name] = el.checked ? (el.value || '1') : '0'; return; }
      if (el.type === 'radio') { if (el.checked) values[name] = el.value; return; }
      values[name] = el.value;
    });
    return values;
  }

  function clearErrors(){
    var m = document.getElementById('pcf-msg'); m.className = 'pcf-msg'; m.textContent = '';
    Array.prototype.forEach.call(document.querySelectorAll('.pcf-fielderr'), function(n){ n.remove(); });
  }

  function showErrors(errorsTabs){
    var m = document.getElementById('pcf-msg');
    m.className = 'pcf-msg err';
    var msgs = [];
    (errorsTabs||[]).forEach(function(tab){
      if (tab && tab.success === false) {
        msgs.push(tab.name || 'Onglet');
        var errs = tab.errors || {};
        Object.keys(errs).forEach(function(field){
          var e = errs[field]; var label = (e && e.label) ? e.label : field;
          var txt = [];
          if (e && typeof e === 'object') { Object.keys(e).forEach(function(k){ if (k!=='label' && typeof e[k]==='string') txt.push(e[k]); }); }
          var input = document.querySelector('.pcf-pane [name="'+field+'"]');
          if (input) { var d = document.createElement('div'); d.className='pcf-fielderr'; d.textContent = txt.join(' '); input.parentNode.appendChild(d); }
        });
      }
    });
    m.textContent = 'Veuillez corriger les champs' + (msgs.length ? ' ('+msgs.join(', ')+').' : '.');
  }

  var applyBtn = document.getElementById('pcf-apply');
  applyBtn.addEventListener('click', function(){
    clearErrors();
    applyBtn.disabled = true; applyBtn.textContent = 'Enregistrement…';
    fetch(CTX.saveUrl, {
      method:'POST', credentials:'same-origin',
      headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
      body: JSON.stringify({ idPage:CTX.idPage, module:CTX.module, pluginName:CTX.pluginName, pluginId:CTX.pluginId, values: collect() })
    }).then(function(r){ return r.json(); }).then(function(data){
      applyBtn.disabled = false; applyBtn.textContent = 'Enregistrer';
      if (data && data.success) {
        parent.postMessage({ type:'melis-plugin-config-saved', pluginId:CTX.pluginId, changed: !!(data.data && data.data.changed) }, '*');
      } else if (data && data.errors) {
        showErrors(data.errors);
      } else {
        var m = document.getElementById('pcf-msg'); m.className='pcf-msg err';
        m.textContent = 'Échec de l’enregistrement' + (data && data.error ? ' : '+data.error : '.') ;
      }
    }).catch(function(e){
      applyBtn.disabled = false; applyBtn.textContent = 'Enregistrer';
      var m = document.getElementById('pcf-msg'); m.className='pcf-msg err'; m.textContent = 'Erreur réseau : '+e;
    });
  });

  document.getElementById('pcf-cancel').addEventListener('click', function(){
    parent.postMessage({ type:'melis-plugin-config-cancel', pluginId:CTX.pluginId }, '*');
  });
})();
</script>
JS;
        $script = str_replace('__CTX__', $ctx, $script);

        return $head
            . '<div id="pcf-msg" class="pcf-msg"></div>'
            . $tabsNav
            // NOT a <form>: the plugin tab layouts emit their own <form> per tab; nesting forms is
            // invalid HTML (the browser closes the outer one early). Fields are collected by .pcf-pane.
            . '<div id="pcf-form">' . $panes . '</div>'
            . '<div class="pcf-actions">'
            . '<button type="button" id="pcf-cancel" class="pcf-btn">Annuler</button>'
            . '<button type="button" id="pcf-apply" class="pcf-btn primary">Enregistrer</button>'
            . '</div>'
            . $script
            . '</body></html>';
    }
}
