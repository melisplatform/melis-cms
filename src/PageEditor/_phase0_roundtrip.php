<?php
/**
 * Phase-0 round-trip harness (throwaway — not committed).
 * Proves PageContentDocument can read + rewrite the real page_content corpus
 * without corruption (identity) and while applying structural edits without
 * losing opaque plugin bodies (structural). Run in the php container (CLI).
 */
require __DIR__ . '/PageContentDocument.php';

use MelisCms\PageEditor\PageContentDocument;

function c14n(string $xml): ?string
{
    $d = new DOMDocument();
    $d->preserveWhiteSpace = false;
    $prev = libxml_use_internal_errors(true);
    $ok = $d->loadXML($xml, LIBXML_NONET);
    libxml_use_internal_errors($prev);
    return $ok ? $d->C14N() : null;
}

/** Canonical form of a node's INNER body only (ignores the element's own attrs). */
function innerC14n(string $nodeXml): ?string
{
    $d = new DOMDocument();
    $d->preserveWhiteSpace = false;
    $prev = libxml_use_internal_errors(true);
    $ok = $d->loadXML($nodeXml, LIBXML_NONET);
    libxml_use_internal_errors($prev);
    if (!$ok || $d->documentElement === null) {
        return null;
    }
    $s = '';
    foreach ($d->documentElement->childNodes as $c) {
        $s .= $c->C14N();
    }
    return $s;
}

mysqli_report(MYSQLI_REPORT_OFF);
$m = new mysqli(getenv('MYSQL_HOST'), getenv('MYSQL_USER'), getenv('MYSQL_PASSWORD'), getenv('MYSQL_DATABASE'));
if ($m->connect_errno) {
    fwrite(STDERR, 'DB connect fail: ' . $m->connect_error . "\n");
    exit(2);
}

$rows = [];
foreach (['melis_cms_page_published', 'melis_cms_page_saved'] as $t) {
    $res = $m->query("SELECT page_id, page_content FROM $t WHERE page_content IS NOT NULL AND TRIM(page_content) <> ''");
    while ($r = $res->fetch_assoc()) {
        $rows[] = [$t, (int) $r['page_id'], $r['page_content']];
    }
}
echo 'corpus: ' . count($rows) . " pages with content\n";

$idPass = 0; $idFail = 0; $failList = [];
$stPass = 0; $stFail = 0; $stFailList = [];

foreach ($rows as [$t, $pid, $xml]) {
    // ---- identity ----
    try {
        $doc = PageContentDocument::fromXml($xml);
        $out = $doc->toXml();
        $a = c14n($xml); $b = c14n($out);
        if ($a !== null && $a === $b) {
            $idPass++;
        } else {
            $idFail++; $failList[] = "$t#$pid (canonical mismatch)";
        }
    } catch (Throwable $e) {
        $idFail++; $failList[] = "$t#$pid EXC " . $e->getMessage();
        continue;
    }

    // ---- structural ----
    try {
        $doc = PageContentDocument::fromXml($xml);
        $nodes = $doc->toArray()['nodes'];

        $ids = array_values(array_filter(array_map(fn($n) => $n['id'], $nodes), fn($v) => $v !== null && $v !== ''));
        $origC14n = [];
        $origInner = [];
        foreach ($nodes as $n) {
            if ($n['id'] !== null && $n['id'] !== '') {
                $origC14n[$n['id']] = c14n($n['raw']);
                $origInner[$n['id']] = innerC14n($n['raw']);
            }
        }

        $rev = array_reverse($ids);
        $doc->reorderNodes($rev);

        $editWidthId = null;
        foreach ($nodes as $n) {
            if ($n['kind'] === 'plugin' && $n['id']) { $editWidthId = $n['id']; break; }
        }
        $zoneRevRefs = [];
        foreach ($nodes as $n) {
            if ($n['kind'] === 'zone' && $n['id']) {
                $refIds = array_values(array_filter(array_map(fn($r) => $r['id'] ?? null, $n['refs'])));
                $zoneRevRefs[$n['id']] = array_reverse($refIds);
                $doc->reorderZoneRefs($n['id'], $zoneRevRefs[$n['id']]);
            }
        }
        if ($editWidthId !== null) {
            $doc->setWidths($editWidthId, '42', '43', '44');
        }

        $out2 = $doc->toXml();
        $re = PageContentDocument::fromXml($out2); // must still be well-formed
        $reNodes = $re->toArray()['nodes'];

        $reIds = array_values(array_filter(array_map(fn($n) => $n['id'], $reNodes), fn($v) => $v !== null && $v !== ''));
        $orderOK = ($reIds === $rev);

        $zoneIds = array_map(fn($n) => $n['id'], array_filter($nodes, fn($n) => $n['kind'] === 'zone'));
        $preserveOK = true;
        foreach ($reNodes as $n) {
            $id = $n['id'];
            if ($id === null || $id === '' || $id === $editWidthId || in_array($id, $zoneIds, true)) {
                continue;
            }
            if (!isset($origC14n[$id]) || c14n($n['raw']) !== $origC14n[$id]) {
                $preserveOK = false; break;
            }
        }

        $widthOK = true;
        $bodyOK = true; // the resized node's CDATA/HTML body must survive the tag rebuild
        if ($editWidthId !== null) {
            foreach ($reNodes as $n) {
                if ($n['id'] === $editWidthId) {
                    $widthOK = (($n['attrs']['width_desktop'] ?? null) === '42');
                    $bodyOK = (innerC14n($n['raw']) === ($origInner[$editWidthId] ?? '__none__'));
                    break;
                }
            }
        }

        $zoneOK = true;
        foreach ($reNodes as $n) {
            if ($n['kind'] === 'zone' && isset($zoneRevRefs[$n['id']])) {
                $got = array_values(array_filter(array_map(fn($r) => $r['id'] ?? null, $n['refs'])));
                if ($got !== $zoneRevRefs[$n['id']]) { $zoneOK = false; break; }
            }
        }

        if ($orderOK && $preserveOK && $widthOK && $zoneOK && $bodyOK) {
            $stPass++;
        } else {
            $stFail++;
            $stFailList[] = "$t#$pid order=" . (int) $orderOK . " preserve=" . (int) $preserveOK . " width=" . (int) $widthOK . " zone=" . (int) $zoneOK . " body=" . (int) $bodyOK;
        }
    } catch (Throwable $e) {
        $stFail++; $stFailList[] = "$t#$pid EXC " . $e->getMessage();
    }
}

echo "\n== IDENTITY (parse->serialize == original) ==\n";
echo "  $idPass ok / $idFail fail\n";
foreach (array_slice($failList, 0, 12) as $f) { echo "  FAIL $f\n"; }

echo "\n== STRUCTURAL (reverse nodes + reverse zone refs + set widths, opaque bodies preserved) ==\n";
echo "  $stPass ok / $stFail fail\n";
foreach (array_slice($stFailList, 0, 12) as $f) { echo "  FAIL $f\n"; }
