<?php

/**
 * Capacités d'outils (droits avancés React) déclarées par MelisCms — fichier SÉPARÉ par module.
 * Mergé via MelisCms\Module::getConfig() (clé `melisReactToolCapabilities`, map melisKey => [caps]).
 * Cf. MelisReactApi\Service\Capabilities. `export` n'est déclaré QUE pour les outils avec bouton Export.
 */

return [
    'melisReactToolCapabilities' => [
        'meliscms_tool_site_301'      => ['list', 'create', 'edit', 'delete', 'export'],
        'meliscms_tool_templates'     => ['list', 'create', 'edit', 'delete', 'export'],
        'meliscms_tool_styles'        => ['list', 'create', 'edit', 'delete', 'export'],
        'meliscms_tool_language'      => ['list', 'create', 'edit', 'delete'], // pas d'export
        'meliscms_tool_platform_ids'  => ['list', 'create', 'edit', 'delete', 'export'],
        'meliscms_tool_sites'         => ['list', 'create', 'edit', 'delete'], // pas d'export
        'meliscms_mini_template_manager_tool'      => ['list', 'create', 'edit', 'delete', 'export'],
        'meliscms_mini_template_menu_manager_tool' => ['list', 'create', 'edit', 'delete'], // pas d'export (arbre, pas de liste tabulaire)
    ],
];
