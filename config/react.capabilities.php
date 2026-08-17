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
            // Actions labellisées {key,label} : le `label` (clé tr_ des VRAIS boutons de la barre
            // d'actions) est traduit côté serveur → mêmes libellés que les boutons de l'éditeur.
            'actions' => [
                ['key' => 'create',    'label' => 'tr_meliscms_page_actions_New'],
                ['key' => 'save',      'label' => 'tr_meliscms_page_actions_Save'],
                ['key' => 'clear',     'label' => 'tr_meliscms_page_action_clear'],
                ['key' => 'publish',   'label' => 'tr_meliscms_page_actions_Publish'],
                ['key' => 'status',    'label' => 'tr_meliscms_page_actions_status'], // switch En ligne / Hors ligne
                ['key' => 'delete',    'label' => 'tr_meliscms_page_actions_Delete Page'],
                ['key' => 'duplicate', 'label' => 'tr_meliscms_page_action_duplicate'],
                ['key' => 'view',      'label' => 'tr_meliscms_page_actions_See'],
                ['key' => 'display',   'label' => 'tr_meliscms_page_actions_display_Display'],
            ],
            'tabs'    => [
                ['key' => 'edition',    'label' => 'tr_meliscms_page_tab_edition_Edition'],
                ['key' => 'properties', 'label' => 'tr_meliscms_page_tab_properties_Properties'],
                ['key' => 'seo',        'label' => 'tr_meliscms_page_tab_seo_Seo'],
                ['key' => 'languages',  'label' => 'tr_meliscms_page_languages'],
            ],
        ],
    ],

    // Nœud « rights-only » de l'éditeur de page : exposé UNIQUEMENT dans Users→Droits (pas dans le
    // menu de gauche) sous la section MelisCms. L'éditeur s'ouvre via l'arbre du site (droits par-page),
    // donc il n'a pas de nœud de menu propre — celui-ci porte ses capacités (onglets + boutons), que
    // les modules complètent sous la même clé `meliscms_page` (cf. melisReactToolCapabilities). Injecté
    // par MelisReactApi\...\buildMenuResponse quand ?full=1. Cf. react.capabilities.php des autres modules.
    'melisReactRightsTools' => [
        'meliscms_toolstree_section' => [
            ['melisKey' => 'meliscms_page', 'name' => 'tr_meliscms_page_edition_rights_label', 'icon' => 'fa-file-text-o'],
        ],
    ],

    // Section « hôte de sidebar » : garde la section MelisCms VISIBLE dans le menu de gauche — pour y
    // accrocher l'arbre des pages React (CmsSidebar) — dès que l'utilisateur a accès à l'éditeur de
    // page (canAccess('meliscms_page')), MÊME s'il n'a AUCUN outil de site (Sites/Templates/Langues…).
    // Sans ça, un user à qui on n'accorde QUE des pages voit toute la section élaguée (aucun site-tool
    // accessible → buildLevel2 vide → section prunée) → arbre invisible → il ne peut PAS atteindre ses
    // pages (ticket 0010724). Le page tree filtre déjà par droits-page côté backend. Lu par
    // MelisReactApi\...\buildMenuResponse : si les catégories sont vides mais `requires` est accessible,
    // la section est émise comme conteneur nu portant `sidebarModule` → l'hôte y attache le panneau.
    'melisReactSidebarHostSections' => [
        'meliscms_toolstree_section' => [
            'requires' => 'meliscms_page',  // gate canAccess (outil « Edition de page »)
            'module'   => 'MelisCms',       // module propriétaire → l'hôte attache son panneau (page tree)
        ],
    ],
];
