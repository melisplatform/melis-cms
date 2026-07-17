<?php

namespace MelisCms\Controller\React;

use MelisReactOverride\Controller\PluginViewController;
use MelisReactOverride\Controller\PluginViewToolPageExtensionInterface;

/**
 * Quirks du mécanisme partagé /melis/react-tool-page pour l'ÉDITEUR DE PAGE CMS (clé
 * `meliscms_page`), rendu dans l'iframe « Édition » de la coquille React.
 *
 * Problème corrigé : le HTML de l'éditeur contient des scripts INLINE (rendus par les zones)
 * qui appellent des globals de module au PARSE TIME — avant que les ressources JS de fin de
 * <body> ne soient chargées :
 *   - `melisCms.disableCmsButtons(id)`  → « melisCms is not defined »  (melisCms.js)
 *   - init de la DataTable de l'onglet Historique → « initHistoric is not defined »
 *     (melispagehistoric.js, module MelisCmsPageHistoric)
 * Dans le BO CLASSIQUE, TOUTES les ressources de module sont dans le <head> → ces globals
 * existent au parse time. On réplique ce comportement UNIQUEMENT pour l'éditeur de page :
 * on pousse le JS des roots `meliscms` et `meliscmspagehistoric` dans le bucket <head>
 * (`$assets['js']`) et on marque ces roots dans `skipJsRoots` pour que la boucle générique de
 * fin de <body> ne les ré-injecte pas (double-load → double-bind des handlers délégués).
 *
 * N'affecte QUE react-tool-page (le /melis classique ne passe pas par ce contrôleur).
 * FULLY MODULAR : getItem() renvoie null si un module est absent → rien n'est injecté.
 */
class PluginViewToolPageExtension implements PluginViewToolPageExtensionInterface
{
    /** Roots dont le JS doit vivre dans le <head> pour l'éditeur de page (globals au parse time). */
    private const HEAD_JS_ROOTS = ['meliscms', 'meliscmspagehistoric'];

    public function adjustToolHtml(string $key, string $html, array $jsCallBacks, PluginViewController $controller): string
    {
        return $html; // rien à ajuster côté HTML pour cet éditeur
    }

    public function adjustToolAssets(string $key, string $html, array $assets, PluginViewController $controller): array
    {
        $skipJsRoots = [];
        if ($key !== 'meliscms_page') {
            return ['assets' => $assets, 'skipJsRoots' => $skipJsRoots];
        }

        $melisAppConfig = $controller->getServiceManager()->get('MelisCoreConfig');
        foreach (self::HEAD_JS_ROOTS as $root) {
            $resJs = $melisAppConfig->getItem("/$root/ressources/js");
            if (is_array($resJs)) {
                // → bucket <head> (avant les scripts inline des zones)
                $assets['js'] = array_values(array_unique(array_merge($assets['js'] ?? [], array_values($resJs))));
                // → la boucle générique de fin de <body> NE doit PAS ré-injecter ce root
                $skipJsRoots[$root] = true;
            }
        }

        return ['assets' => $assets, 'skipJsRoots' => $skipJsRoots];
    }
}
