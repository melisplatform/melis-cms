<?php

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreModulesService;

/**
 * Overrides the platform's 'ModulesService' alias (normally MelisCoreModulesService, in
 * melis-core — not a live checkout here, so it can't be patched directly) to fix a real bug in
 * getActiveModules(), which activateModule()/createModuleLoader() rely on every time the
 * templating/tool-creator wizards create a new module and rewrite config/melis.module.load.php.
 *
 * getActiveModules() reads $this->getServiceManager()->get('ModuleManager')->getLoadedModules() —
 * Laminas's OWN module registry, populated only from config/application.config.php's `modules`
 * list. Site modules (module/MelisSites/*, e.g. BioCardia/Dekra/ClubThermal) are NOT in that list:
 * they're loaded through Melis's own custom bootstrap (MelisModuleManager reading
 * config/melis.module.load.php directly), a path Laminas's ModuleManager never sees. So every time
 * a new module is activated, getActiveModules() returns an incomplete list missing every site
 * module, and createModuleLoader() overwrites melis.module.load.php with that incomplete list —
 * silently dropping BioCardia/Dekra/ClubThermal and breaking their plugin resolution platform-wide
 * (confirmed live: this exact sequence happened twice in one session, each fixed by manually
 * re-adding the site modules back into melis.module.load.php).
 *
 * Fix: after the normal resolution, add back any module/MelisSites/* directory not already present
 * — generic (any current or future site), not a hardcoded list of today's three sites.
 */
class SiteAwareModulesService extends MelisCoreModulesService
{
    public function getActiveModules($exclude = [])
    {
        $modules = parent::getActiveModules($exclude);
        foreach ($this->getSitesModules() as $siteModule) {
            if (!in_array($siteModule, $exclude, true) && !in_array($siteModule, $modules, true)) {
                $modules[] = $siteModule;
            }
        }
        return $modules;
    }
}
