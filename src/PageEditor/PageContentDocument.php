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
            'raw'   => $n['raw'],
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
