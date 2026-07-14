<?php

/**
 * Capacités d'outils (droits avancés React) déclarées par MelisCms — fichier SÉPARÉ par module.
 * Mergé via MelisCms\Module::getConfig() (clé `melisReactToolCapabilities`, map melisKey => [caps]).
 * Cf. MelisReactApi\Service\Capabilities. `export` n'est déclaré QUE pour les outils avec bouton Export.
 */

return [
    'melisReactToolCapabilities' => [
        'meliscms_tool_site_301'      => ['list', 'create', 'edit', 'delete', 'export', 'test'],
        'meliscms_tool_templates'     => ['list', 'create', 'edit', 'delete', 'export'],
        'meliscms_tool_styles'        => ['list', 'create', 'edit', 'delete', 'export'],
        'meliscms_tool_language'      => ['list', 'create', 'edit', 'delete'], // pas d'export
        'meliscms_tool_platform_ids'  => ['list', 'create', 'edit', 'delete', 'export'],
        'meliscms_tool_sites'         => ['list', 'create', 'edit', 'delete', 'export'],
        'meliscms_mini_template_manager_tool'      => ['list', 'create', 'edit', 'delete', 'export'],
        'meliscms_mini_template_menu_manager_tool' => ['list', 'create', 'edit', 'delete'], // pas d'export (arbre, pas de liste tabulaire)

        // ── Éditeur de page CMS (meliscms_page) — coquille React full-React ──
        // Arbre : `actions` = boutons de la barre d'actions ; `tabs` = onglets. Le gating React
        // (CmsPage.tsx) filtre les onglets et masque les boutons refusés via ces clés à plat
        // (flatten → 'create','save',… pour les actions ; 'edition','properties',… pour les onglets).
        // MODULARITÉ : chaque module contributeur ajoute SES onglets/boutons sous CETTE MÊME clé
        // dans SON propre react.capabilities.php (merge Laminas) — small-business déclare workflow +
        // versioning + commentaires ; page-historic/analytics/script-editor peuvent déclarer les leurs.
        // Ici : seuls les onglets/boutons NATIFS de MelisCms.
        'meliscms_page' => [
            'actions' => ['create', 'save', 'publish', 'delete', 'duplicate'],
            'tabs'    => [
                ['key' => 'edition',    'label' => 'tr_meliscms_page_tab_edition_Edition'],
                ['key' => 'properties', 'label' => 'tr_meliscms_page_tab_properties_Properties'],
                ['key' => 'seo',        'label' => 'tr_meliscms_page_tab_seo_Seo'],
                ['key' => 'languages',  'label' => 'tr_meliscms_page_languages'],
            ],
        ],
    ],
];
