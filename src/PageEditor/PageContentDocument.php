<?php

namespace MelisCms\PageEditor;

/**
 * PageContentDocument — the retro-compat spine of the React page editor
 * (branch evo/page-edition-react).
 *
 * Reads and rewrites the `melis_cms_page_{saved,published}.page_content` XML as a
 * structured, editable model WITHOUT losing anything in the plugin nodes it does
 * not understand. This is the single boundary the new (stateless) PHP edit layer
 * shares with the 10-year-old legacy code: same bytes in the DB, both directions.
 *
 * The content XML is one <document> with a FLAT list of sibling plugin nodes.
 * Each node is a plugin instance identified by its element name (= the plugin's
 * `pluginXmlDbKey`, e.g. melisTag / melisDragDropZone / melisCmsSlider) and an
 * `id` attribute unique in the page. A drag-drop zone does not contain its
 * plugins' data — it lists ordered <plugin module="" name="" id=""/> references;
 * the referenced data node lives as a top-level sibling.
 *
 * V2 layout zones (drag-and-drop schemas) nest: a zone carries a `template` attr
 * (a schema from melis-front `drag-and-drop-layouts`) and, PHYSICALLY NESTED
 * inside it, child <melisDragDropZone> sub-zones with ids `<parent>_1`..`<parent>_N`.
 * The schema template renders exactly those N cells; a cell renders its <plugin>
 * refs. This class models that nesting recursively so the React editor can drive it
 * statelessly, while the bytes stay identical to what the legacy code emits
 * (verified against MelisFrontDragDropZonePlugin::buildXmlFromArray()).
 *
 * Design principle for retro-compatibility:
 *   - Every node keeps its VERBATIM original XML (`raw`).
 *   - A node that has NOT been structurally edited is re-emitted byte-for-byte
 *     from `raw` (opaque preservation — third-party/unknown plugins included).
 *   - Only a node explicitly marked dirty is rebuilt; editing a nested zone marks
 *     its whole ancestor chain dirty so the rebuild reaches it, while untouched
 *     siblings still emit from `raw`.
 *
 * No Laminas / no DB dependency: pure libxml, so it is unit-testable in CLI.
 */
final class PageContentDocument
{
    private const HEADER = '<?xml version="1.0" encoding="UTF-8"?>';
    private const DEFAULT_TPL = 'MelisFront/dnd-default-tpl';

    /** @var array<string,string> wrapper element attributes (type/author/version) */
    private array $wrapper = [
        'type'    => 'MelisCMS',
        'author'  => 'MelisTechnology',
        'version' => '2.0',
    ];

    /** @var array<int,array<string,mixed>> ordered top-level plugin nodes */
    private array $nodes = [];

    private function __construct()
    {
    }

    // ---------------------------------------------------------------- parse ---

    public static function fromXml(string $xml): self
    {
        $doc = new self();

        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        // LIBXML_NOERROR|LIBXML_NONET: tolerate the odd legacy quirk, never fetch.
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml, LIBXML_NONET);
        libxml_use_internal_errors($prev);
        if ($ok === false || $dom->documentElement === null) {
            throw new \RuntimeException('page_content is not well-formed XML');
        }

        $root = $dom->documentElement; // <document>
        $doc->wrapper = [];
        foreach ($root->attributes as $attr) {
            $doc->wrapper[$attr->nodeName] = $attr->nodeValue;
        }

        foreach ($root->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue; // insignificant whitespace between nodes
            }
            $doc->nodes[] = self::captureNode($dom, $child);
        }

        return $doc;
    }

    /**
     * Capture one plugin node into the model (verbatim + structured), recursively
     * for zones so nested sub-zones become editable `zone` items (not opaque).
     */
    private static function captureNode(\DOMDocument $dom, \DOMElement $el): array
    {
        $attrs = [];
        foreach ($el->attributes as $attr) {
            $attrs[$attr->nodeName] = $attr->nodeValue;
        }

        $innerRaw = '';
        foreach ($el->childNodes as $c) {
            $innerRaw .= $dom->saveXML($c);
        }

        $isZone = ($el->nodeName === 'melisDragDropZone');
        $node = [
            'kind'     => $isZone ? 'zone' : 'plugin',
            'tag'      => $el->nodeName,
            'id'       => $attrs['id'] ?? null,
            'attrs'    => $attrs,
            'innerRaw' => $innerRaw,
            'raw'      => $dom->saveXML($el),
            'dirty'    => false,
        ];

        if ($isZone) {
            // Split the zone body into ordered items: <plugin> references we
            // understand, nested <melisDragDropZone> sub-zones (modelled), and
            // anything else kept opaque so edits never drop unknown content.
            $items = [];
            foreach ($el->childNodes as $c) {
                if ($c->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                if ($c->nodeName === 'plugin') {
                    $ref = [];
                    foreach ($c->attributes as $a) {
                        $ref[$a->nodeName] = $a->nodeValue;
                    }
                    $items[] = ['kind' => 'ref', 'ref' => $ref];
                } elseif ($c->nodeName === 'melisDragDropZone') {
                    $items[] = self::captureNode($dom, $c); // nested zone (kind=zone)
                } else {
                    $items[] = ['kind' => 'opaque', 'raw' => $dom->saveXML($c)];
                }
            }
            $node['items'] = $items;
        }

        return $node;
    }

    // ------------------------------------------------------------ serialise ---

    public function toXml(): string
    {
        $out = self::HEADER;
        $out .= '<document' . self::renderAttrs($this->wrapper) . '>';
        foreach ($this->nodes as $node) {
            $out .= $this->renderNode($node);
        }
        $out .= '</document>';
        return $out;
    }

    /** Render a node (top-level or nested): verbatim when clean, rebuilt when dirty. */
    private function renderNode(array $node): string
    {
        if (empty($node['dirty'])) {
            return $node['raw']; // opaque preservation — byte-for-byte
        }

        if ($node['kind'] === 'zone') {
            $inner = '';
            foreach ($node['items'] ?? [] as $item) {
                $kind = $item['kind'] ?? '';
                if ($kind === 'ref') {
                    $inner .= '<plugin' . self::renderAttrs($item['ref']) . '/>';
                } elseif ($kind === 'zone') {
                    $inner .= $this->renderNode($item); // recurse (clean → its raw)
                } else {
                    $inner .= $item['raw'];
                }
            }
            return self::element($node['tag'], $node['attrs'], $inner);
        }

        // plugin data node: rebuild open tag, body preserved verbatim
        return self::element($node['tag'], $node['attrs'], $node['innerRaw']);
    }

    /** Emit an element, self-closing when empty to match libxml's canonical form. */
    private static function element(string $tag, array $attrs, string $inner): string
    {
        if ($inner === '') {
            return '<' . $tag . self::renderAttrs($attrs) . '/>';
        }
        return '<' . $tag . self::renderAttrs($attrs) . '>' . $inner . '</' . $tag . '>';
    }

    private static function renderAttrs(array $attrs): string
    {
        $s = '';
        foreach ($attrs as $name => $value) {
            $s .= ' ' . $name . '="'
                . htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8')
                . '"';
        }
        return $s;
    }

    // -------------------------------------------------- JSON model (React) ---

    /** The document as the JSON contract the React editor consumes (zones recursive). */
    public function toArray(): array
    {
        return [
            'wrapper' => $this->wrapper,
            'nodes'   => array_map([$this, 'nodeToArray'], $this->nodes),
        ];
    }

    private function nodeToArray(array $n): array
    {
        $out = [
            'kind'  => $n['kind'],
            'tag'   => $n['tag'],
            'id'    => $n['id'],
            'attrs' => $n['attrs'],
            // ?? '': a brand-new node (duplicateZone) has no verbatim XML to preserve — it's built
            // fresh from attrs/items whenever dirty, which a new node always starts as.
            'raw'   => $n['raw'] ?? '',
        ];
        if ($n['kind'] === 'zone') {
            $out['template'] = $n['attrs']['template'] ?? '';
            $out['refs']  = [];   // this cell's own ordered <plugin> refs
            $out['zones'] = [];   // nested sub-zones (columns/rows), recursive
            foreach ($n['items'] ?? [] as $it) {
                $kind = $it['kind'] ?? '';
                if ($kind === 'ref') {
                    $out['refs'][] = $it['ref'];
                } elseif ($kind === 'zone') {
                    $out['zones'][] = $this->nodeToArray($it);
                }
            }
        }
        return $out;
    }

    // ------------------------------------------------- structural editing ---
    // (the generic, per-plugin-agnostic operations the canvas needs; all
    //  depth-aware — a zone/plugin is found wherever it lives in the tree)

    /** @return array<int,array<string,mixed>> */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /** @return array<string,string> */
    public function wrapper(): array
    {
        return $this->wrapper;
    }

    /**
     * Walk the node tree (top-level nodes + nested zone items) to the node with
     * $id, run $mutate on it in place, and mark it AND every ancestor dirty so the
     * rebuild reaches it while untouched siblings still emit from raw.
     */
    private function walk(array &$list, string $id, callable $mutate): bool
    {
        foreach ($list as &$node) {
            if (!is_array($node)) {
                continue;
            }
            $kind = $node['kind'] ?? '';
            if (($kind === 'zone' || $kind === 'plugin') && (($node['id'] ?? null) === $id)) {
                $mutate($node);
                $node['dirty'] = true;
                return true;
            }
            if ($kind === 'zone' && isset($node['items']) && is_array($node['items'])) {
                if ($this->walk($node['items'], $id, $mutate)) {
                    $node['dirty'] = true; // ancestor on the path to the target
                    return true;
                }
            }
        }
        unset($node);
        return false;
    }

    /**
     * Ensure a TOP-LEVEL drag-drop zone with this id exists (empty). Seeds a fresh page's
     * template-defined zones into the model so the structure panel shows them AND the structural ops
     * (addPlugin/applyLayout/setZoneRefs) can target them BEFORE the page has any saved content — the
     * zones otherwise live only in the template render, never in an empty `<document/>`. No-op if the
     * zone is already a top-level node. Returns true when a zone was added.
     */
    public function ensureZone(string $id): bool
    {
        if ($id === '') {
            return false;
        }
        foreach ($this->nodes as $n) {
            if (($n['kind'] ?? '') === 'zone' && (string) ($n['id'] ?? '') === $id) {
                return false; // already present
            }
        }
        $this->nodes[] = [
            'kind'     => 'zone',
            'tag'      => 'melisDragDropZone',
            'id'       => $id,
            'attrs'    => ['id' => $id],
            'items'    => [],
            'innerRaw' => '',
            'raw'      => '<melisDragDropZone id="' . htmlspecialchars($id, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"/>',
            'dirty'    => true,
        ];
        return true;
    }

    /** Reorder top-level nodes by id; unknown ids ignored, missing kept in order. */
    public function reorderNodes(array $idsInOrder): void
    {
        $byId = [];
        foreach ($this->nodes as $i => $n) {
            $byId[$n['id'] ?? ('#' . $i)] = $n;
        }
        $reordered = [];
        foreach ($idsInOrder as $id) {
            if (isset($byId[$id])) {
                $reordered[] = $byId[$id];
                unset($byId[$id]);
            }
        }
        foreach ($byId as $n) {
            $reordered[] = $n; // anything not named keeps trailing
        }
        $this->nodes = $reordered;
    }

    /**
     * Reorder the TOP-LEVEL drag-drop ZONE nodes among themselves — legacy's own "reorder D&D
     * zones" (dndUpdateOrderAction, which reorders the session's flat melisDragDropZone map)
     * applied to our single flat node list. Only matters WITHIN a plugin_referer group: two zones
     * with different ids/groups each render at their own fixed template call site regardless of
     * document order, so reordering across groups is a visual no-op — the client only offers this
     * between zones that already share a group (see EditionCanvas's moveZone). Every OTHER
     * top-level node (plugin data) is left exactly where it already was — unlike reorderNodes,
     * which would shove every unlisted node to the end — so this never disturbs the document
     * beyond the zone slots actually being reordered. Unknown zone ids are ignored; zones not
     * named in $zoneIdsInOrder keep their existing relative order, trailing the named ones.
     */
    public function reorderZones(array $zoneIdsInOrder): void
    {
        $byId = [];
        foreach ($this->nodes as $n) {
            if (($n['kind'] ?? '') === 'zone' && !empty($n['id'])) {
                $byId[(string) $n['id']] = $n;
            }
        }
        $ordered = [];
        foreach ($zoneIdsInOrder as $zid) {
            if (isset($byId[$zid])) {
                $ordered[] = $byId[$zid];
                unset($byId[$zid]);
            }
        }
        foreach ($byId as $n) {
            $ordered[] = $n; // any zone not named keeps trailing order
        }

        $queue = $ordered;
        foreach ($this->nodes as &$n) {
            if (($n['kind'] ?? '') === 'zone' && !empty($n['id'])) {
                $n = array_shift($queue);
            }
        }
        unset($n);
    }

    /** Set responsive widths on any node (plugin data or sub-zone), at any depth. */
    public function setWidths(string $id, string $desktop, string $tablet, string $mobile): void
    {
        $this->walk($this->nodes, $id, static function (array &$n) use ($desktop, $tablet, $mobile): void {
            $n['attrs']['width_desktop'] = $desktop;
            $n['attrs']['width_tablet']  = $tablet;
            $n['attrs']['width_mobile']  = $mobile;
        });
    }

    /** Reorder a zone's own <plugin> references by plugin id (other items preserved). */
    public function reorderZoneRefs(string $zoneId, array $refIdsInOrder): void
    {
        $this->walk($this->nodes, $zoneId, function (array &$n) use ($refIdsInOrder): void {
            if ($n['kind'] !== 'zone') {
                return;
            }
            $n['items'] = self::orderRefs($n['items'], $refIdsInOrder, false);
        });
    }

    /**
     * Set a zone's own <plugin> references to EXACTLY the given list (in order) — refs
     * not listed are DROPPED (this is how a block is removed from a cell). Nested
     * sub-zones and unknown items are preserved. Referenced data nodes are left in the
     * document (orphan, unrendered).
     */
    public function setZoneRefs(string $zoneId, array $refIdsInOrder): void
    {
        $this->walk($this->nodes, $zoneId, function (array &$n) use ($refIdsInOrder): void {
            if ($n['kind'] !== 'zone') {
                return;
            }
            $n['items'] = self::orderRefs($n['items'], $refIdsInOrder, true);
        });
    }

    /**
     * Move a <plugin> reference from one zone/cell to another (drag-and-drop between drop
     * zones). setZoneRefs/reorderZoneRefs only ever reorder refs a zone ALREADY owns — they
     * can't relocate one, since orderRefs() matches ids against that zone's own items. Here
     * the ref item is spliced out of the source zone's items and into the target zone's, at
     * $position (clamped; end when omitted). The referenced data node (its actual plugin
     * content) lives as a top-level sibling regardless of which zone points to it, so it is
     * untouched — only the lightweight <plugin module name id/> pointer moves.
     */
    public function moveRef(string $fromZoneId, string $toZoneId, string $refId, ?int $position = null): void
    {
        if ($fromZoneId === $toZoneId || $refId === '') {
            return; // same-zone reorder goes through setZoneRefs/reorderZoneRefs instead
        }

        $moved = null;
        $this->walk($this->nodes, $fromZoneId, function (array &$n) use ($refId, &$moved): void {
            if ($n['kind'] !== 'zone') {
                return;
            }
            foreach ($n['items'] as $i => $item) {
                if (($item['kind'] ?? '') === 'ref' && (($item['ref']['id'] ?? null) === $refId)) {
                    $moved = $item;
                    array_splice($n['items'], $i, 1);
                    break;
                }
            }
        });

        if ($moved === null) {
            return; // ref wasn't in the source zone — nothing to move
        }

        $this->walk($this->nodes, $toZoneId, function (array &$n) use ($moved, $position): void {
            if ($n['kind'] !== 'zone') {
                return;
            }
            $pos = ($position === null || $position < 0 || $position > count($n['items']))
                ? count($n['items']) : $position;
            array_splice($n['items'], $pos, 0, [$moved]);
        });
    }

    /**
     * Create a NEW top-level drag-drop zone, a sibling of $zoneId, positioned immediately after it.
     * A page's template is static PHP — `$this->MelisDragDropZone($pageId, "some_id")` LOOKS fixed
     * to one id — but the actual view helper behind it (MelisDragDropZoneHelper::__invoke) does not
     * render just that one: it scans the WHOLE page XML for every <melisDragDropZone> whose own id
     * OR plugin_referer ATTRIBUTE matches the requested id, and renders all of them, in XML document
     * order. So a genuinely new zone the template never explicitly asked for is possible after all
     * — it just needs plugin_referer set to an existing, template-reachable zone's group. This
     * adopts legacy's own XML shape verbatim (plugin_container_id/plugin_referer/plugin_position —
     * see dndLayoutAction's addAction branch and MelisDragDropZoneHelper) rather than reinventing
     * it, so the two stay interoperable (both read/write the same melis_cms_page.page_content XML).
     * $withContent clones the source zone's own blocks into the new one (fresh ids — see cloneRefs);
     * false creates it empty.
     *
     * @return string the new zone's id, or '' if $zoneId itself doesn't exist
     */
    public function duplicateZone(string $zoneId, bool $withContent): string
    {
        $sourceIndex = null;
        $source = null;
        foreach ($this->nodes as $i => $n) {
            if (($n['kind'] ?? '') === 'zone' && ($n['id'] ?? null) === $zoneId) {
                $sourceIndex = $i;
                $source = $n;
                break;
            }
        }
        if ($source === null) {
            return '';
        }

        // Same grouping legacy's own JS uses (`if (pluginReferer) dndId = pluginReferer`) — a
        // duplicate of a duplicate stays in the SAME group as the original, not a chain of
        // one-off referers each rendering only at their immediate parent's call site.
        $referer = (string) ($source['attrs']['plugin_referer'] ?? '');
        $groupId = $referer !== '' ? $referer : $zoneId;

        $existingIds = [];
        foreach ($this->nodes as $n) {
            if (!empty($n['id'])) {
                $existingIds[(string) $n['id']] = true;
            }
        }
        $newZoneId = $groupId . '_' . time();
        while (isset($existingIds[$newZoneId])) {
            $newZoneId = $groupId . '_' . time() . substr(bin2hex(random_bytes(2)), 0, 3);
        }

        $items = $withContent ? $this->cloneRefs($source['items'] ?? [], $existingIds, $newZoneId) : [];

        $attrs = [
            'id'                  => $newZoneId,
            'plugin_container_id' => $newZoneId,
            'plugin_referer'      => $groupId,
            'plugin_position'     => '',
        ];
        // A real duplicate (withContent) must also carry the source's LAYOUT SCHEMA — without it
        // the front render has no idea the new zone is split into columns at all (falls back to a
        // plain single dropzone, so its cloned nested-cell content — see cloneRefs — never renders
        // even though it's genuinely in the document). "New Zone" (withContent=false) stays a plain
        // blank single-cell zone on purpose, so it does NOT inherit the source's template.
        if ($withContent) {
            $sourceTemplate = (string) ($source['attrs']['template'] ?? '');
            if ($sourceTemplate !== '') {
                $attrs['template'] = $sourceTemplate;
            }
        }

        $newZone = [
            'kind'  => 'zone',
            'tag'   => 'melisDragDropZone',
            'id'    => $newZoneId,
            'attrs' => $attrs,
            'items' => $items,
            'dirty' => true, // brand new — no verbatim raw to preserve; renderNode() builds it from attrs/items
        ];

        array_splice($this->nodes, $sourceIndex + 1, 0, [$newZone]);

        return $newZoneId;
    }

    /**
     * Remove a DYNAMICALLY CREATED top-level zone — one produced by duplicateZone (carries a
     * non-empty plugin_referer). Mirrors legacy's dndRemoveAction verbatim in spirit: a plain
     * removal of the zone's own entry, nothing else touched. The ORIGINAL template zone
     * (plugin_referer="") is refused — MelisDragDropZoneHelper's own "create initial dnd zone"
     * fallback regenerates it on the very next render regardless (the template's own
     * MelisDragDropZone() call still asks for that id), so removing it would silently do nothing
     * but orphan its content; refusing avoids that footgun. Its own referenced plugin data nodes
     * are left in the document, same as setZoneRefs (orphan, unrendered) — nothing is physically
     * deleted, so a mistaken removal loses nothing before the next Save/Publish.
     *
     * @return bool true when the zone was removed
     */
    public function removeZone(string $zoneId): bool
    {
        foreach ($this->nodes as $i => $n) {
            if (($n['kind'] ?? '') === 'zone' && ($n['id'] ?? null) === $zoneId) {
                if ((string) ($n['attrs']['plugin_referer'] ?? '') === '') {
                    return false; // original template zone — refuse
                }
                array_splice($this->nodes, $i, 1);
                return true;
            }
        }
        return false;
    }

    /**
     * Clone a zone's items under fresh ids — recurses into nested sub-zones (a split-layout
     * zone's own columns, kind='zone' items) so duplicating a zone that's had applyLayout
     * applied to it actually carries its columns' content along too, not just direct refs (a
     * zone with a layout applied has ALL its content moved into those columns — see
     * reconcileLayout — so skipping them meant "duplicate" silently produced an empty copy).
     * Each ref's referenced data node is cloned as a new top-level sibling (a ref is a
     * lightweight pointer; the actual plugin content lives as a top-level sibling, same as
     * moveRef relies on), so the copy is independent afterward, not a shared pointer.
     * $existingIds is mutated as ids are claimed, so repeat calls in the same request never
     * collide. $newParentId scopes nested cell ids to "<newParentId>_<n>" (the same convention
     * applyLayout itself uses/expects — see reconcileLayout's `$zoneId . '_' . $i` lookup), so
     * re-splitting the duplicate later still recognizes its own cells instead of orphaning them.
     *
     * @param array<int,array<string,mixed>> $items
     * @param array<string,bool> $existingIds
     * @return array<int,array<string,mixed>>
     */
    private function cloneRefs(array $items, array &$existingIds, string $newParentId = ''): array
    {
        $clones = [];
        foreach ($items as $item) {
            $kind = $item['kind'] ?? '';
            if ($kind === 'zone') {
                $clones[] = $this->cloneNestedZone($item, $existingIds, $newParentId);
                continue;
            }
            if ($kind !== 'ref') {
                continue;
            }
            $oldId = (string) ($item['ref']['id'] ?? '');
            if ($oldId === '') {
                continue;
            }
            $source = null;
            foreach ($this->nodes as $n) {
                if (($n['id'] ?? null) === $oldId) {
                    $source = $n;
                    break;
                }
            }
            if ($source === null) {
                continue; // orphan ref (its data node is missing) — nothing to clone
            }

            $newId = $oldId . '_copy_' . time();
            while (isset($existingIds[$newId])) {
                $newId = $oldId . '_copy_' . time() . substr(bin2hex(random_bytes(2)), 0, 3);
            }
            $existingIds[$newId] = true;

            $clone = $source;
            $clone['id'] = $newId;
            $clone['attrs']['id'] = $newId;
            // Re-target the verbatim XML to the new id too — it stays "clean" (re-emitted byte-for-
            // byte otherwise), so this is the only place the new id needs to actually take effect.
            $clone['raw'] = str_replace('id="' . $oldId . '"', 'id="' . $newId . '"', (string) $source['raw']);
            $clone['dirty'] = false;
            $this->nodes[] = $clone;

            $clones[] = ['kind' => 'ref', 'ref' => array_merge($item['ref'], ['id' => $newId])];
        }
        return $clones;
    }

    /**
     * Clone one nested layout cell (a split-zone's own column) — id becomes
     * "<newParentId>_<n>" (n = the cell's own position suffix, e.g. "_2") so a later
     * applyLayout on the duplicate still matches its existing cells, then recurses via
     * cloneRefs for that cell's own items (further nesting supported, though not currently
     * produced by the layout picker).
     *
     * @param array<string,mixed> $item
     * @param array<string,bool> $existingIds
     */
    private function cloneNestedZone(array $item, array &$existingIds, string $newParentId): array
    {
        $oldId = (string) ($item['id'] ?? '');
        $suffix = $oldId;
        if (($pos = strrpos($oldId, '_')) !== false) {
            $suffix = substr($oldId, $pos); // "_1", "_2"…
        }
        $newId = $newParentId !== '' ? $newParentId . $suffix : $oldId . '_copy_' . time();
        while (isset($existingIds[$newId])) {
            $newId .= '_' . substr(bin2hex(random_bytes(2)), 0, 3);
        }
        $existingIds[$newId] = true;

        $clone = $item;
        $clone['id'] = $newId;
        $clone['attrs']['id'] = $newId;
        $clone['attrs']['plugin_referer'] = $newParentId;
        $clone['items'] = $this->cloneRefs($item['items'] ?? [], $existingIds, $newId);
        $clone['dirty'] = true;

        return $clone;
    }

    /**
     * Reorder/select a zone's ref items. Refs are keyed by id and re-emitted in
     * $order; non-ref items (nested zones, opaque) keep their relative order after
     * the refs. When $drop is false, refs absent from $order are kept (appended
     * after the named ones); when true they are removed.
     *
     * @param array<int,array<string,mixed>> $items
     * @param array<int,string>              $order
     * @return array<int,array<string,mixed>>
     */
    private static function orderRefs(array $items, array $order, bool $drop): array
    {
        $refs = [];
        $others = [];
        foreach ($items as $item) {
            if (($item['kind'] ?? '') === 'ref') {
                $refs[$item['ref']['id'] ?? spl_object_id((object) $item)] = $item;
            } else {
                $others[] = $item; // nested zones / opaque kept
            }
        }
        $new = [];
        foreach ($order as $rid) {
            if (isset($refs[$rid])) {
                $new[] = $refs[$rid];
                unset($refs[$rid]);
            }
        }
        if (!$drop) {
            foreach ($refs as $item) {
                $new[] = $item; // unnamed refs kept
            }
        }
        foreach ($others as $item) {
            $new[] = $item;
        }
        return $new;
    }

    /**
     * Add a new plugin to a zone (at any depth): append its verbatim node as a
     * top-level sibling AND add a `<plugin module name id/>` reference to the zone
     * (at $position, default end). The caller builds `$rawNode` (the plugin's XML)
     * and a fresh unique `$id`. dirty=false on the data node → emitted verbatim.
     */
    public function addPlugin(string $zoneId, string $module, string $name, string $id, string $rawNode, ?int $position = null): void
    {
        $tag = preg_match('/^\s*<([A-Za-z0-9_]+)/', $rawNode, $m) ? $m[1] : 'melisTag';
        $this->nodes[] = [
            'kind'     => $tag === 'melisDragDropZone' ? 'zone' : 'plugin',
            'tag'      => $tag,
            'id'       => $id,
            'attrs'    => [],
            'innerRaw' => '',
            'raw'      => $rawNode,
            'dirty'    => false,
        ];

        $this->walk($this->nodes, $zoneId, static function (array &$n) use ($module, $name, $id, $position): void {
            if ($n['kind'] !== 'zone') {
                return;
            }
            $ref = ['kind' => 'ref', 'ref' => ['module' => $module, 'name' => $name, 'id' => $id]];
            $count = count($n['items']);
            if ($position === null || $position >= $count) {
                $n['items'][] = $ref;
            } else {
                array_splice($n['items'], max(0, $position), 0, [$ref]);
            }
        });
    }

    /**
     * Replace a text/HTML plugin node's inner content (its CDATA body) — the config for a `melisTag`
     * html/text block. The open tag (id, type, widths…) is preserved; only the body changes. The new
     * html is CDATA-wrapped and made CDATA-safe (a literal `]]>` is split).
     */
    public function setTagContent(string $id, string $html): void
    {
        $cdata = '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $html) . ']]>';
        $this->walk($this->nodes, $id, static function (array &$n) use ($cdata): void {
            $n['innerRaw'] = $cdata;
        });
    }

    /**
     * Replace a plugin data node's WHOLE fragment — the retro-compat seam for plugin
     * configuration. `$fragment` is the authoritative XML the plugin itself produces
     * (`MelisTemplatingPlugin::savePluginConfigToXml()`), e.g.
     * `<melisBreadcrumb id="x"><template_path><![CDATA[…]]></template_path></melisBreadcrumb>`.
     * The node keyed by $id gets that fragment's attrs + inner (open tag preserved via
     * the fragment's own attributes, which carry the id); a plugin never configured
     * before (a zone ref with no top-level data node yet) is appended verbatim.
     *
     * Defensive by design (the whole layer's ethos): an empty or malformed producer
     * output is a NO-OP — we never wipe or corrupt an existing plugin on bad input.
     *
     * @return bool true when the fragment was applied (node updated or appended).
     */
    public function setPluginXml(string $id, string $fragment): bool
    {
        $fragment = trim($fragment);
        if ($fragment === '') {
            return false; // never wipe a plugin because the producer returned nothing
        }

        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML(self::HEADER . $fragment, LIBXML_NONET);
        libxml_use_internal_errors($prev);
        if ($ok === false || $dom->documentElement === null) {
            return false; // malformed producer output — leave the draft untouched
        }

        $el = $dom->documentElement;
        $tag = $el->nodeName;
        $attrs = [];
        foreach ($el->attributes as $attr) {
            $attrs[$attr->nodeName] = $attr->nodeValue;
        }
        $inner = '';
        foreach ($el->childNodes as $c) {
            $inner .= $dom->saveXML($c);
        }

        $found = $this->walk($this->nodes, $id, static function (array &$n) use ($attrs, $inner, $tag): void {
            $a = $attrs;
            if (!isset($a['id']) && isset($n['attrs']['id'])) {
                $a['id'] = $n['attrs']['id']; // keep identity if the producer omitted id
            }
            // savePluginConfigToXml() emits only id + config children; carry the STRUCTURAL attrs the
            // editor manages (responsive widths, container) so configuring a block never resets its size.
            foreach (['width_desktop', 'width_tablet', 'width_mobile', 'plugin_container_id'] as $keep) {
                if (!isset($a[$keep]) && isset($n['attrs'][$keep])) {
                    $a[$keep] = $n['attrs'][$keep];
                }
            }
            $n['tag']      = $tag;
            $n['attrs']    = $a;
            $n['innerRaw'] = $inner;
        });
        if ($found) {
            return true;
        }

        // No data node yet for this ref (plugin never configured): append it verbatim.
        $this->nodes[] = [
            'kind'     => $tag === 'melisDragDropZone' ? 'zone' : 'plugin',
            'tag'      => $tag,
            'id'       => $attrs['id'] ?? $id,
            'attrs'    => $attrs,
            'innerRaw' => $inner,
            'raw'      => $fragment,
            'dirty'    => false, // emitted byte-for-byte from the producer output
        ];
        return true;
    }

    /**
     * Apply a drag-and-drop layout SCHEMA to a zone (V2). Sets the zone's `template`
     * to $template and reconciles its nested cells to exactly $cols leaf sub-zones
     * `<zoneId>_1`..`<zoneId>_N`, PHYSICALLY NESTED — the shape the schema template
     * renders. Existing cells are reused by id (their plugins kept); the zone's own
     * direct plugins are moved into cell 1; cells beyond $cols are merged into the
     * last kept cell so nothing is lost.
     *
     * $cols <= 0 (or the default template) collapses the zone back to a single leaf,
     * hoisting every nested plugin ref back up as a direct ref.
     */
    public function applyLayout(string $zoneId, string $template, int $cols): void
    {
        $self = $this;
        $this->walk($this->nodes, $zoneId, static function (array &$z) use ($self, $template, $cols): void {
            if ($z['kind'] !== 'zone') {
                return;
            }
            $self->reconcileLayout($z, $template, $cols);
        });
    }

    /** @param array<string,mixed> $z */
    private function reconcileLayout(array &$z, string $template, int $cols): void
    {
        $zoneId = (string) ($z['id'] ?? '');

        $childZones = [];
        $directRefs = [];
        $opaque = [];
        foreach ($z['items'] ?? [] as $it) {
            $kind = $it['kind'] ?? '';
            if ($kind === 'zone') {
                $childZones[(string) ($it['id'] ?? '')] = $it;
            } elseif ($kind === 'ref') {
                $directRefs[] = $it;
            } else {
                $opaque[] = $it;
            }
        }

        $z['attrs']['template'] = $template;

        if ($cols <= 0 || $template === self::DEFAULT_TPL) {
            // Collapse to a single leaf: hoist every nested ref back up as direct.
            $refs = $directRefs;
            foreach ($childZones as $cz) {
                self::collectRefs($cz, $refs);
            }
            $z['items'] = array_merge($refs, $opaque);
            return;
        }

        $newChildren = [];
        for ($i = 1; $i <= $cols; $i++) {
            $cid = $zoneId . '_' . $i;
            if (isset($childZones[$cid])) {
                $newChildren[$cid] = $childZones[$cid];
                unset($childZones[$cid]);
            } else {
                $newChildren[$cid] = self::newLeafZone($cid, $zoneId);
            }
        }

        // Shrunk layout: merge leftover cells' refs into the last kept cell.
        $lastId = $zoneId . '_' . $cols;
        foreach ($childZones as $cz) {
            $refs = [];
            self::collectRefs($cz, $refs);
            foreach ($refs as $r) {
                $newChildren[$lastId]['items'][] = $r;
            }
            $newChildren[$lastId]['dirty'] = true;
        }

        // Zone's own direct plugins move into the first cell.
        if ($directRefs) {
            $firstId = $zoneId . '_1';
            $newChildren[$firstId]['items'] = array_merge($newChildren[$firstId]['items'], $directRefs);
            $newChildren[$firstId]['dirty'] = true;
        }

        $z['items'] = array_merge(array_values($newChildren), $opaque);
    }

    /** A fresh empty leaf cell, matching legacy buildXmlFromArray() attribute set. */
    private static function newLeafZone(string $id, string $referer): array
    {
        $attrs = [
            'id'                => $id,
            'plugin_container_id' => '',
            'plugin_referer'    => $referer,
            'plugin_position'   => '1',
            'width_desktop'     => '100',
            'width_tablet'      => '100',
            'width_mobile'      => '100',
            'template'          => self::DEFAULT_TPL,
        ];
        return [
            'kind'     => 'zone',
            'tag'      => 'melisDragDropZone',
            'id'       => $id,
            'attrs'    => $attrs,
            'innerRaw' => '',
            'raw'      => '',
            'dirty'    => true,
            'items'    => [],
        ];
    }

    /**
     * Collect every <plugin> ref item reachable in a zone (this cell + nested),
     * depth-first, so collapsing/shrinking a layout preserves all blocks.
     *
     * @param array<string,mixed>            $zone
     * @param array<int,array<string,mixed>> $out
     */
    private static function collectRefs(array $zone, array &$out): void
    {
        foreach ($zone['items'] ?? [] as $it) {
            $kind = $it['kind'] ?? '';
            if ($kind === 'ref') {
                $out[] = $it;
            } elseif ($kind === 'zone') {
                self::collectRefs($it, $out);
            }
        }
    }
}
