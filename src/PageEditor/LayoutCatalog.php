<?php

namespace MelisCms\PageEditor;

use Laminas\ServiceManager\ServiceManager;

/**
 * LayoutCatalog — the drag-and-drop SCHEMA catalog for the React page editor
 * (branch evo/page-edition-react).
 *
 * The pickable layouts live in melis-front config `plugins.drag-and-drop-layouts`
 * (2/3/4/5/6-cols, ratio splits, 2/3/4-row stacks…). Each entry names a `template`
 * (a schema phtml) and an SVG-ish `html-button-icon`. This helper turns that config
 * into a flat catalog the React panel renders, and — crucially — resolves how many
 * cells a schema template lays out by reading the template itself (the number of
 * `MelisDragDropZone(` calls = the `<parent>_1`..`_N` sub-zones it renders). That
 * count is the single authority `applyLayout()` needs, and it doubles as the
 * allow-list: only a template present here (so, a real schema) can be applied —
 * `cols()` returns null for anything else, blocking arbitrary template injection.
 *
 * Pure read of config + view files; no session, no DB.
 */
final class LayoutCatalog
{
    private const DEFAULT_TPL = 'MelisFront/dnd-default-tpl';

    /**
     * The full catalog for the React picker.
     *
     * @return array<int,array{key:string,template:string,cols:int,icon:string}>
     */
    public static function all(ServiceManager $sm): array
    {
        $config = $sm->get('config');
        $defs = $config['plugins']['drag-and-drop-layouts'] ?? [];

        $out = [];
        foreach ($defs as $key => $def) {
            $tpl = (string) ($def['template'] ?? '');
            if ($tpl === '') {
                continue;
            }
            $out[] = [
                'key'      => (string) $key,
                'template' => $tpl,
                'cols'     => self::colsForTemplate($sm, $tpl),
                'icon'     => (string) ($def['html-button-icon'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * How many cells $template lays out, or null if $template is not a known schema
     * (the allow-list guard). The default (single-cell) template resolves to 0.
     */
    public static function cols(ServiceManager $sm, string $template): ?int
    {
        foreach (self::all($sm) as $l) {
            if ($l['template'] === $template) {
                return $l['cols'];
            }
        }
        return null;
    }

    /** Count the sub-zones a schema template renders (= `MelisDragDropZone(` calls). */
    private static function colsForTemplate(ServiceManager $sm, string $template): int
    {
        if ($template === self::DEFAULT_TPL) {
            return 0;
        }
        try {
            $resolver = $sm->get('ViewRenderer')->resolver();
            $path = $resolver->resolve($template);
            if (is_string($path) && is_file($path)) {
                return substr_count((string) file_get_contents($path), 'MelisDragDropZone(');
            }
        } catch (\Throwable) {
        }
        return 0;
    }
}
