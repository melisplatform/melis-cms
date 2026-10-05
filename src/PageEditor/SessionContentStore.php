<?php

namespace MelisCms\PageEditor;

use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * SessionContentStore — the WORKING edit buffer of the React page editor, held in the
 * legacy `meliscms` session EXACTLY like the Old drag-drop editor:
 *
 *   $_SESSION['meliscms']['content-pages'][$idPage][<nodeName>][<id>] = <plugin XML fragment>
 *
 * This is the retro-compat contract with the legacy Save/Publish chain. Editing a page
 * only updates this session — it NEVER writes the draft. The top toolbar's Save button
 * (savePageAction → meliscms_page_save_start → MelisCmsSavePageListener →
 * PageEdition::saveEdition) is what flushes this session into `melis_cms_page_saved`, and
 * Publish copies it on to `melis_cms_page_published`. So the React editor writes here, the
 * melis render (renderMode=melis, which reads this session in priority) shows the edits
 * live, and nothing reaches the DB until the user clicks Save — same model as legacy.
 *
 * Seeded from the DB (saved → published) on first touch, mirroring the legacy
 * PageEditionController::loadPageContentPluginsInSession(). The per-plugin width buffer
 * `private:melisPluginSettings` (written by the Old editor) is preserved across writes, so
 * a page edited in both editors stays consistent. saveEdition skips that private key when
 * it rebuilds the document, so it never leaks into page_content.
 */
final class SessionContentStore
{
    private const HEADER  = '<?xml version="1.0" encoding="UTF-8"?>';
    // Same wrapper the legacy saveEditionAction() hardcodes (and PageContentDocument's default).
    private const WRAPPER = '<document type="MelisCMS" author="MelisTechnology" version="2.0">';

    public function __construct(private ServiceLocatorInterface $sm)
    {
    }

    /** Whether the working session already holds this page's content (edits or a prior seed). */
    public function has(int $idPage): bool
    {
        return !empty($_SESSION['meliscms']['content-pages'][$idPage]);
    }

    /**
     * Ensure the working session holds this page: if empty, seed it from the DB (saved →
     * published), decomposed into per-node fragments. Returns the source: 'session' when it
     * was already present, 'saved'/'published' when just seeded, or null when the page has
     * no content anywhere (nothing to seed).
     */
    public function ensureSeeded(int $idPage): ?string
    {
        $this->startSession();
        if ($this->has($idPage)) {
            return 'session';
        }
        [$xml, $source] = $this->dbContent($idPage);
        if ($xml === null) {
            return null;
        }
        $map = $this->decompose($xml);
        if ($map !== []) {
            $_SESSION['meliscms']['content-pages'][$idPage] = $map;
        }
        return $source;
    }

    /** The working page content as a composed <document> XML, or null when the page is empty. */
    public function readXml(int $idPage): ?string
    {
        $this->ensureSeeded($idPage);
        $page = $_SESSION['meliscms']['content-pages'][$idPage] ?? null;
        if (!is_array($page) || $page === []) {
            return null;
        }
        return $this->compose($page);
    }

    /** The working page content as an editable PageContentDocument, or null when empty. */
    public function readDocument(int $idPage): ?PageContentDocument
    {
        $xml = $this->readXml($idPage);
        return $xml === null ? null : PageContentDocument::fromXml($xml);
    }

    /**
     * Persist an edited document back into the working session: decompose it into per-node
     * fragments and REPLACE this page's map (so removals/reorders take effect), preserving
     * the Old editor's `private:melisPluginSettings` buffer if it was present.
     */
    public function writeDocument(int $idPage, PageContentDocument $doc): void
    {
        $this->startSession();
        $map = $this->decompose($doc->toXml());
        $settings = $_SESSION['meliscms']['content-pages'][$idPage]['private:melisPluginSettings'] ?? null;
        if ($settings !== null) {
            $map['private:melisPluginSettings'] = $settings;
        }
        $_SESSION['meliscms']['content-pages'][$idPage] = $map;
    }

    // ------------------------------------------------------------- internals ---

    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    /**
     * The current DB page content, draft-first (saved → published).
     * @return array{0: ?string, 1: ?string} [page_content xml or null, source or null]
     */
    private function dbContent(int $idPage): array
    {
        foreach (['MelisEngineTablePageSaved' => 'saved', 'MelisEngineTablePagePublished' => 'published'] as $table => $source) {
            try {
                $row = $this->sm->get($table)->getEntryById($idPage)->toArray()[0] ?? null;
            } catch (\Throwable) {
                $row = null;
            }
            $xml = (string) ($row['page_content'] ?? '');
            if (trim($xml) !== '') {
                return [$xml, $source];
            }
        }
        return [null, null];
    }

    /**
     * Decompose a <document> XML into the legacy session map [<nodeName>][<id>] = fragmentXml.
     * Mirrors PageEditionController::loadPageContentPluginsInSession() exactly (one entry per
     * top-level node, keyed by element name then id).
     *
     * @return array<string,array<string,string>>
     */
    private function decompose(string $xml): array
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml, LIBXML_NONET);
        libxml_use_internal_errors($prev);
        if ($ok === false || $dom->documentElement === null) {
            return [];
        }

        $map = [];
        foreach ($dom->documentElement->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE || !$child instanceof \DOMElement) {
                continue;
            }
            $map[$child->nodeName][(string) $child->getAttribute('id')] = $dom->saveXML($child);
        }
        return $map;
    }

    /**
     * Compose the working session map back into a <document> XML (concatenated fragments),
     * exactly like PageEditionController::saveEditionAction() — skipping the private buffer.
     *
     * @param array<string,mixed> $page
     */
    private function compose(array $page): string
    {
        $out = self::HEADER . self::WRAPPER;
        foreach ($page as $name => $entries) {
            if ($name === 'private:melisPluginSettings' || !is_array($entries)) {
                continue;
            }
            foreach ($entries as $fragment) {
                $out .= (string) $fragment;
            }
        }
        return $out . '</document>';
    }
}
