<?php

namespace MelisCms\PageEditor;

use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Loads a page's content XML from the engine tables and hands back a
 * PageContentDocument model. Draft-first, exactly like the editor: read
 * `melis_cms_page_saved`, fall back to `melis_cms_page_published`. Read-only;
 * writing (Save) will be a separate service in this layer.
 */
final class PageContentProvider
{
    public function __construct(private ServiceLocatorInterface $sm)
    {
    }

    /**
     * @return array{source: ?string, xml: ?string, document: ?PageContentDocument}
     */
    public function load(int $idPage): array
    {
        // draft first
        $row    = $this->row('MelisEngineTablePageSaved', $idPage);
        $source = 'saved';
        if ($row === null || trim((string) ($row['page_content'] ?? '')) === '') {
            $row    = $this->row('MelisEngineTablePagePublished', $idPage);
            $source = 'published';
        }

        if ($row === null) {
            return ['source' => null, 'xml' => null, 'document' => null];
        }

        $xml = (string) ($row['page_content'] ?? '');
        if (trim($xml) === '') {
            return ['source' => $source, 'xml' => '', 'document' => null];
        }

        return [
            'source'   => $source,
            'xml'      => $xml,
            'document' => PageContentDocument::fromXml($xml),
        ];
    }

    /** @return array<string,mixed>|null */
    private function row(string $tableService, int $idPage): ?array
    {
        $res = $this->sm->get($tableService)->getEntryById($idPage)->toArray();
        return $res[0] ?? null;
    }

    /**
     * The site module namespace for a page (= template.tpl_zf2_website_folder), i.e. the
     * `melisSite` value the front render needs to know which site to load. Draft-first.
     */
    public function siteNamespace(int $idPage): string
    {
        try {
            $melisPage = $this->sm->get('MelisEnginePage');
            $datasPage = $melisPage->getDatasPage($idPage, 'saved');
            $tpl = $datasPage ? $datasPage->getMelisTemplate() : null;
            if (empty($tpl) || empty($tpl->tpl_zf2_website_folder)) {
                $datasPage = $melisPage->getDatasPage($idPage, 'published');
                $tpl = $datasPage ? $datasPage->getMelisTemplate() : null;
            }
            return !empty($tpl->tpl_zf2_website_folder) ? (string) $tpl->tpl_zf2_website_folder : '';
        } catch (\Throwable) {
            return '';
        }
    }
}
