## v6.0.6 - 2026-10-05
### Security
* **page-editor:** plugin modal shows the current template of hardcoded plugins (0011040)
* Page-editor: internationalize the React UI (was hardcoded French)
### Added
* **page-editor:** plugin picker module accordions, mobile bottom sheet for the block menu
* **page-editor:** block row "⋯" menu + "Open in Melis AI" for html-tag / mini-template blocks
* **page-editor:** touch drag-and-drop for block rows in the structure panel (mobile)
* **page-editor:** duplicate a single block / mini-template in place (Mantis #0011001)
* **page-editor:** add/remove dynamic D&D zones without a canvas reload (Mantis #0010958)
* **page-editor:** seed a fresh page's template drag-drop zones into the panel
* **page-editor:** module-contributed global config tabs + partial-caching save seam
* **page-editor:** React edition canvas + full-React plugin config kit
### Fixed
* **tree:** duplicate-tree Relationship/Root fields are Yes/No switches (0011068)
* **page-editor:** D&D zone placeholder and frame in the canvas (0011041)
* **platform-ids:** refuse ranges overlapping another platform's IDs (0011046)
* **platform-ids:** close "New range" tab, reopen created range, re-enable create after delete (0011047)
* **page-editor:** no delete button on the sub-zones of a split layout (Mantis #0011042)
* **page-editor:** collapse zone-header actions into a "⋯" menu, matching the block row
* **page-editor:** Page Analytics tab shows nothing when no valid module is assigned (Mantis #0011034 follow-up)
* **page-editor:** block row menu — visible hover, highlighted row, remove as a direct row button (Mantis #0011033)
* **page-editor:** portal the canvas overlays to document.body (Mantis #0011015)
* **page-editor:** persist block widths without relying on blur (Mantis #0010997)
* **page-editor:** mobile taps on a mini-template keep the inline editor (Mantis #0011011)
* **page-editor:** live-patch renders settle against in-flight saves (Mantis #0010987)
* **tinymce:** restore LF line endings in html.php
* **tinymce:** keep <link> tags inside html-tag block content (Mantis #0010999)
* **page-editor:** inline toolbar always wide and above the block; no scroll on canvas click
* **page-editor:** duplicated zone now keeps the source's layout template
* **page-editor:** duplicate zone now carries content from nested layout cells
* **page-editor:** match zone-head button icon/size to the toolbar
* **page-editor:** disable toolbar buttons until edition loads; gate Erase draft on hasDraft
* **page-editor:** stop reloading the page tree on every plugin move (Mantis #0010995)
* **page-editor:** return to the Edition tab after a version restore or draft clear
* **page-editor:** live-patch layout changes (Mantis #0010973) + non-destructive AI-insert refresh
* **page-editor:** reload the React canvas after a version restore/draft clear (Mantis #0010974)
* **page-editor:** scope zone reordering to same plugin_referer group (Mantis #0010958)
* **page-editor:** make inline text editing instant, fix recurring getRng crashes
* **page-editor:** stop remounting the Edition canvas on every page-tab switch
* **page-editor:** support dragging a plugin between drop zones
### Changed
* Page-editor: drop a false-negative plugin-not-found guard, fix site modules vanishing from melis.module.load.php
* Page-editor: render schema-driven switch/tinymce/date fields as their real widgets, fix legacy calendar/footer bugs
* Page-editor: drop the legacy MelisCacheInternal caching tab from the React schema
* Page-editor: fix React plugin-config modal cross-instance and stale-cache bugs
* Page-editor: front-faithful multi-column layout, Old-view refresh on save, light/dark plugin config
* Page-editor: in-canvas config modal shows the plugin TITLE, not the class name
* Page-editor: section/group custom plugins by their OWNING module, not the config key
* Page-editor: refetch the add-plugin palette when the edited page changes
* Page-editor: scope palette PER PLUGIN by owning module (fix templating plugins)
* Page-editor: scope the add-plugin palette to the page site modules
* Page-editor: runtime schema-driven React config (no-build path for live plugins)
* Page-editor: skin Bootstrap tooltips in the legacy plugin-config harness
* I18n: fix wrong-language values and mismatched keys in interface translations
* Page-editor: rebuild brick (user-account 'registration page' tab on comments)
* PluginFormKit: export usePrefill for cross-module field components
* Page-editor: register blog/user-account/comments/category2 native config forms
* Page-editor: reveal reorder arrows only for hovered/selected plugin
* Page-editor: strip leading \ from plugin names before translating
### Dependencies & build
* **page-editor:** rebuild brick.js for the fixes cherry-picked from melis-react

## v6.0.5 - 2026-09-25
### Security
* **page-editor:** load the CSRF emitter in the React edition canvas
* **security:** declare a grantable tool key on the legacy controller (audit item 7.0)
* **security:** declare the tool key on this module's legacy controllers (audit item 7.0)

## v6.0.4 - 2026-09-23
### Fixed
* **platform-ids:** refuse ranges overlapping another platform's IDs (0011046)
* **platform-ids:** close "New range" tab, reopen created range, re-enable create after delete (0011047)

## v6.0.3 - 2026-08-20
### Security
* **i18n:** give hardcoded tab/rights labels tr_ keys
* **melis-cms:** gate mutating/data actions across legacy tool controllers (CWE-862)
### Added
* **cms-react:** modular page-save-hook registry for editor tabs
* **ui-react:** use </> code icon on the New view toggle
* **page-editor:** generic per-page tab condition (react_condition)
### Fixed
* **mini-templates:** allow dashes when reading a template (empty AI preview)
* **cms-react:** show page id before the title in tab/header labels
### Docs
* **melisai:** React back-office AI documentation for MelisCms

## v6.0.1 - 2026-08-10
### Dependencies & build
* **composer:** update docs/homepage links, swap zf2 keyword for laminas, bump php constraint to ^8.3|^8.5
* **deps-dev:** bump postcss from 8.5.16 to 8.5.26 in /ui-react

## v6.0.0 - 2026-08-10
### Security
* **security:** add SECURITY.md (private vulnerability reporting policy)
* Fix audit findings
### Added
* **marketplace:** add React back-office screenshots (etc/MarketPlace/images/react)
* **cms-page-editor:** collapsible header on mobile
* **cms-react:** show per-field save errors in the site editor, + sub-tab persistence
* **webservices:** translatable service description for the token WS listing
* **cms-react:** arbre des pages (Site tree view) repliable/depliable — ticket 0010822
* **cms-react:** Mini-Template manager responsive (mobile)
* **cms-react:** listes infinies keyset + tri server-side + icones de tri unifiees
* **mini-templates:** name-only edit URL + AI generator sub-route
* **newsletter:** React "Send newsletter" modal, rendered outside the edition frame (ticket 0010743)
* **cms-react:** remonter les modules actifs en tête de l'onglet Modules
* **cms:** melisReactSidebarHostSections — MelisCms visible avec droits Pages seuls
* **cms-react:** modale React de suppression de page (treeview) + messages traduits
* **cms-react:** l'onglet « Old » de l'éditeur rouvre le dialogue mini-template legacy
* **cms-react:** recharge l'onglet Commentaires sur action workflow
* **cms-react:** onglet Languages cliquable + noms fiables + reload Historique
* **cms-react:** création de version de langue (legacy) + SEO en sections 2 colonnes
* **cms-react:** pagination serveur + dates localisées (analytics/historique/versioning) + badges & filtre historique
* **cms-react:** droits/capabilities éditeur de page CMS + polish
* **cms-react:** éditeur de page — polish onglets Versioning & Commentaires
* **cms-react:** onglets éditeur de page — Scripts éditable, Versioning (voir/restaurer/renommer), Commentaires en frise d'activité + rebuild brick
* **cms-react:** écran « Nouvelle page » full-React, modales delete/dupliquer/workflow, drapeaux images, ordre boutons
* **cms-react:** édition de page — boutons rebranchés legacy + verrou + switch publié/dépublié
* **react:** editeur de page CMS full-React (coquille + onglets natifs + boutons + publication)
* Add event listeners
* Added toolbar to site tab
* **react:** add a "Reset filters" button to the tool list page(s)
* **sites:** reflete le /:id du sous-onglet d'edition dans l'URL (cosmetique replaceState)
* **cms-sites:** colonnes Nom du site / Module + langues avec drapeaux
* **cms-react:** modale native « Dupliquer l'arborescence »
* **cms:** persistent bricks (no reload on top-tab switch) via manifest flag + active-freeze
* Added capabilities for minitemplate manager
* **cms-tools:** sub-tabs create/edit across CMS tools, faithful legacy site wizard (5 steps) + language flags, styles active toggle, platform-ids platform name/gating, templates native create
* **menu-manager:** full MenuManager (backend controller + StatusToggle) synced from main
* **react:** update brick + site/template/language/style/platform-id/redirect pages + gdpr autodelete config
* **react:** outil Sites full-React (liste + edition 6 onglets)
* **react:** declarations de capacites + gating outils CMS (briques)
* **react:** outils CMS full-React Styles/Languages/Platforms IDs (brique) + ExportModal/ViewToggle partages, export+toggle Old/New sur Redirect/Template
* **react:** pages SiteRedirect & Template + maj brick
* **react-brick:** page-lock indicator in tree + hide Export/Import context actions
* **react-brick:** backend page search + drag-and-drop reorder in the tree
* **react-brick:** tree-driven page tabs, new-page flow & tree reload
* **react-bo:** CMS page tree URL + UX (route /melis-cms/page, bigger caret, no ⋯)
* **react:** brique React melis-cms (arbre de pages + menu contextuel)
* Add MelisAI module documentation for AI consumption
* Add explicit nullable param types (PHP 8.4 deprecation)
### Fixed
* **mobile:** touch-compatible column drag-and-drop across 9 tools, KPI icons, translate New/Old toggle, move New button below toggle row
* **cms-page-editor:** reloadEdition refetches props/SEO/structure (0010873 followup)
* **cms-page-editor:** reload edition after save/publish when template changed (0010873)
* **security:** harden legacy file/dir creation & output escaping
* **cms-react/site-translations:** icon buttons + truncated key on narrow viewports
* **cms-page-editor:** hide the Display (device preview) toolbar button on mobile — pointless on a mobile viewport (ticket 0010840)
* **cms-react:** legacy page-editor tabs/tables + versioning list on narrow viewports
* **cms-react:** row action buttons no longer clipped on narrow lists
* **cms-react:** page editor action bar fits narrow viewports
* **cms-react:** mini-template edit form responsive on narrow viewports
* **cms-editor:** keep per-tab page state, no reload on tab switch (ticket 0010738)
* **rights:** tri-state dash on the Pages tree (ticket 0010741)
* **cms:** onglet domaine invisible en vue Old (tool Sites, édition)
* **cms-react:** bouton Dupliquer en vue Old rejoue le flux React (hookDuplicate)
* **cms-react:** afficher le détail des erreurs de page (URL SEO déjà utilisée)
* **cms:** barre de boutons sticky non coupée en vue old React (scroll édition)
* **cms-react:** éditeur non blanc après suppression d une page (2 onglets)
* **cms-react:** le menu de droite (drag'n'drop) suit le scroll en BO React
* **cms-react:** messages d'erreur clairs a la duplication d'arborescence
* **cms-react:** le bouton Dupliquer de l'editeur ne copie que la page courante
* **cms-react:** nettoyage console éditeur de page + modal Erase draft + divers
* **react:** make column manager panel scrollable
* **react:** keep the legacy "Old" view legacy (no more hijack to the React editor)
* Fix handling
* **cms-sites:** sous-onglet d'édition unique (React) + pas de double barre
* **sites:** onglet de langue lisible en old view + null-guard reload treeview legacy
* **react:** toggles on/off en vert (ON) / rouge (OFF) sur les outils full-React
* **melis-cms:** restore prominent §0 'the trio' section at the top
* Fixed problem on page duplicate
### Changed
* I18n(cms): traduire les textes en dur de l'éditeur de pages (FR/EN)
* Plugin menu fix
* Plugine menu fix when hovered
* Site module loading: list all dependents (active or not) and mirror legacy Yes/No deactivation popup
* Updated for page script editor
* Minitemplate and menu manager updates
* Preselect site module
* Minitemplate manager tool update
* **react-api:** outil(s) React-API du module dans leur module (modularité)
* Updated for minitemplate manager tool
* Convert minitemplate manager
### Dependencies & build
* **composer:** bump melis-core/melis-engine/melis-front constraint to ^6.0
* Local WIP snapshot before reconcile (20260806-114605)
* **cms-react:** sync teammates' unified form error handling + rebuild brick
* **sync:** aligner melis-react sur la version du dépôt parent (local6-2)
* **cms-react:** rebuild brick après merge (résolution du conflit de build)
* **react:** rebuild brick apres merge (MiniTemplate + editeur de page)
* **sync:** melis-cms content from main (merged team changes)
### Docs
* **meliscms:** richer side-tool guidance + explanatory technical reference with code examples
* **meliscms:** rewrite as two-part doc (functional guide + technical reference)

## v5.3.28 - 2026-06-03
### Fixed
* Fix minitemplate issue to show modal preview same as to the melis-cms page edition
### Changed
* Updated template name validation

## v5.3.26 - 2026-05-13
### Added
* Add validation for thumbnail file types and improve error handling
* Added css center content vertically
### Fixed
* Fix 9982
* Fix assertion failed issue
* Fix 9465
* Fix 9419
* Fix error body is null on iframeLoad
* Fix issue on re-arranging plugin on dnd
### Changed
* Update removed param in mini_template_preview_shell_url
* Update fix 10046
* Related to 10046
* Maxupload size
* Edit styles.css
* Check fancytree issue 9465
* Folder page duplicate
* Site tool translation update
* Save site with no module tool/tab
* Import/export
* Updated tinymce configs - removed listed plugins or toolbar
* Updated tinymce configs
* Updated plugins and toolbar entries
* D&d duplication on moved fix
* D&d 1:1 layout duplication
* Page cache menu key for user and sitemodule
* Updated fix
* 8923 dnd mini templates issue fix
* Skip seo canonical clean url
### Dependencies & build
* Updated bundle

## v5.3.11 - 2025-07-15
### Added
* Added icon-col-bg
* Added dynamic or standard class on dnd mode
### Fixed
* Fixed saving plugins inside plugin
### Changed
* Edit css
* Edit css conflict on layout buttons
* Check fix 8586

## v5.3.10 - 2025-07-09
### Changed
* Update on dynamic-dragndrop css on issue of transparent background

## v5.3.9 - 2025-07-09
### Added
* Added warning when changing dnd render mode
* Add warning when change dnd zone mode
* Added option to disable dynamic dnd
* Added divi style column layouts
* Added padding on dnd-layout-buttons
### Fixed
* Fix 8503
* Fix iframe heght
* Fix issue 8508
* Fix issue on .m-plugin-sub-tools
* Fixed saving dnd zone
* Fix 8502
* Fix issue 8479
* Fix arrow displays depends on current position
* Fix conflict on css
* Fixed resizable
* Fixed resizable problem
* Fix conflict
* Fixed problem removing problem
* Fixed problem removing plugins
* Fixed plugin savings
### Changed
* Check fix on 8563
* Check fix 8562
* Check fix for hover flickering
* Edit fix on drag and drop related to .melis-plugin-tools-box
* Edit melis-plugin-tools-box position
* Updated icon positioning
* Edit on fix 8509
* Check fix on 8509
* Edit fix on 8509
* Check 8523
* .m-options-handle issue on dev3
* Tinymce issue
* Update dnd zone icon
* Edit new icon
* Update css
* Update dragdropzone icon
* Update site createion with dnd render mode
* Remove logs
* Edit check on platform scheme issue
* Adjust remove button icon on firefox
* Edit on remove button icon
* Edit on disabled remove button icon
* Edit on remove button
* Edit restore default
* Update disabling dynamic dnd
* Debug cache on reset platfrom scheme
* Check fix 8467
* Check issue 8466
* Edit on js
* Edit js
* Edit css and js
* Edit css
* Edits js and css
* Updated changes on css
* Css update
* Css changes for .dnd-layout-buttons and custom column layouts
* Check parent if finish loading for resize
* Update on ui and hover effect
* Update css and js
* Updated hover effect
* Update resize init
* Updated resizable
* Update resizable
* Check hover on nested .dnd-layout-wrapper
* Update dnd duplicate
* V1 ui popover layout buttons
* Updated for html, media and textarea inits is not undefine
* Update duplicate
* Changes on dynamic-dragndrop js and css
* Popover on layout buttons
* Edit on css
* Update dnd copy
* Edit on dynamic-dragndrop.js and css
* Edit on click layout buttons
* Show both buttons
* Edit code
* Update copy dnd
* Update plugin saving
* Buttons displayed
* Displayed none on buttons
* Edit on css and js
* Update on dynamic-dragndrop css
* Update saving dnd plugins
* Hover only on specific .dnd-layout-wrapper
* Update plugins rendering
* Update on js, css and render-plugins-menu.phtml
* Plugin edition
* Update for dynamic dnd

## v5.3.6 - 2025-05-14
### Fixed
* Fix issue 8320
* Fix drag n drop, delete confirmation dialog
* Fix issue on site tree callback when zoneReload
### Changed
* Check fix assertion failed issue
* Remove fancy tree persist plugin

## v5.3.5 - 2025-03-10
### Added
* Added max-width on melis-cms-plugin-snippets image
### Fixed
* Fix site translations search for accented characters
### Changed
* Updated the 32px.png of jstree
* Delete flagged minitpl in db too
* Edit on render-pagetab.phtml
* Remove bug for single language category
* Limit key and label in translation to 64
* Edits on js

## v5.3.4 - 2025-01-16
### Security
* Fixed injection problem
### Changed
* Update needed files when bundling

## v5.3.3 - 2024-10-23
* Maintenance release.

## v5.3.2 - 2024-10-23
### Added
* Added the added option within mini template phtml file
### Fixed
* Fix issue 7283
* Fix for issue fancytree assertion failed: only init supported
* Fix on clubthermal issue on mini templates & plugins
### Changed
* Removed the added option within mini template phtml file

## v5.3.1 - 2024-09-26
### Fixed
* Fix release issue 7124 and 7113

## v5.3.0 - 2024-09-25
### Added
* Added console logs for tracing purposes
* Added bundle all needed assets in the config
### Fixed
* Fixing issue 7102 on dragndrop.js
* Fix issue 7002
* Fix issue 6979
* Fix for issue fancytree init only
* Fixed problem removing cache when updating mini template category
* Fix issue 6844
* Fix issue 6668
* Fix issue 6595
* Fix issue 6589
* Fix issue 6591
* Fix issue 6590
* Fix issue 6427
* Fix issue 6360
* Fix issue 6333
* Fix issue 6332
* Fix issue 6331
* Fix issue 6451
* Fix issue 6323
* Fix issue 6319
* Fix fancytree issue
* Fix issue 6320
* Fix issue 6356
* Fix issue 6356 on site tools then sites
* Fix 6356
* Fix issue 6317
### Changed
* Minor changes related to jquery migration
* Edits on js
* Edit on dragndrop.js
* Removed console logs
* Issue 6807 no error when adding a console.log on hidden.bs.modal
* Check issue on dragndrop.js
* Checking fix on issue 6807
* Js format
* Related jquery migration
* Edit on fancyTreeInit.js
* Edit related to private module
* Checki issue 6461
* .ready to $(function(){})
* User roles
* User role display
* Update on js and html
* Notice and fixed an issue while also fixing 6383
* Checking issue on bootstrapSwitch
* Bs5 tab
* Update jQuery 3.7.1 migration
* JQuery 3.7.1 migration
* Update on jQuery migration
### Dependencies & build
* Rebundle js remove console logs

## v5.2.2 - 2024-08-29
### Changed
* Plugin menu cached deletion
* Mini template
* Menu content render
* Mini template restore

## v5.2.1 - 2024-07-02
### Fixed
* Fixed problem redeclare function
* Fixed problem in iframe calling twice the url
* Fix for melis cms plugin menu loader
### Changed
* Cache cms menu plugins
* Change melis cms plugin menu loader color to transparent black
* Change plugin menu loading

## v5.2.0 - 2024-06-06
### Dependencies & build
* Update melis version to 5.2

## v5.1.1 - 2024-04-08
### Added
* Added toolbar mode on tinymce options
* Added promotion false on tinymce options
* Added the toolbar item name change
### Fixed
* Fix issue on site tree view modal
* Fix issues 3568 and 3682
* Fix issue 6102
### Changed
* Tinymce update issue
* Tinymce type tool full toolbar buttons
* Edit adding the toolbar item for paragraph and headings
* Checking page edition issue
* Checking issue on page edition tinymce
* Checking page edition js
* Continue page edition checking
* Checking issue on page edition tinymce mini template drag and drop
* Checking issue on page edition dev3 tinymce updates
* Update on tinymce
* Update on tinymce, minitemplate and moxiemanager
* Revert tinymce html, media and textarea.php
* Updated options and plugins on tinymce 6.7.0
* Update on minitemplates
* Update on tinymce 6.7.0
* Update on tinymce 6

## v5.1.0 - 2024-02-13
### Added
* Added label for taxonomy
### Fixed
* Fix str_replace error in plugins menu
* Fixed streplace on null
### Changed
* Update zend -> laminas
* Deprecated null on htmlspecialchars
* Update deprecated strftime
* Change version
* Change minimum stability
* Change version to dev
* Temp change version
* Change front and engine req branch
### Dependencies & build
* Update melisplatform version to 5.1
* Update php version to 8.3

## v5.0.1 - 2022-09-26
### Added
* Added allowed_classes=false param to unserialize func

## v5.0.0 - 2022-06-22
### Security
* Fixed rights issue when editing a site
### Changed
* Updated mini template image style
* Updated checked/unchecked values
* Updated affected functions caused by the upgrade
* Updated the filtered count
* Changed deprecated ArraySerializable to ArraySerializableHydrator
### Dependencies & build
* Update melis package version to 5.0
* Updated php version
* Updated functions that were affected with the lates version of laminas packages

<!-- Historical entries preserved below -->

## v3.0.7 - 2018-01-08
* Updated asset bundles
* Bugs fixes
