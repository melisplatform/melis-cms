---
title: MelisCms module
package: melisplatform/melis-cms
doc_type: module-documentation
audience: ai
language: en
module_version: unversioned   # no `version` field in composer.json; this doc tracks the current source
last_reviewed: 2026-06-08
maintainer: Melis Technology
keywords: [cms, back-office, pages, page-tree, page-editor, sites, templates, styles, languages, seo, publish, melis]
screenshots_dir: ./images
---

# MelisCms Module — Functional Documentation (for AI)

> **Purpose of this document**: describe, functionally and technically, the
> `melisplatform/melis-cms` module, so that an AI (or a developer) can understand
> *what the module does*, *which back-office tools it provides*, *how the page lifecycle
> works* and *where the corresponding code lives*.
>
> **Audience**: consumed by the **MelisAI** module (a MelisPlatform module that exposes an
> MCP function to answer user questions). MelisAI fetches this `.md` file and the screenshots
> in `./images/` on demand — §10 is the filename→content index.
>
> **Status**: reviewed 2026-06-08 against the current source. The module carries no
> semantic version (no `version` in `composer.json`).

---

## 0. The MelisCms / MelisFront / MelisEngine trio

These three modules are the heart of the Melis website platform and must be understood
together (full explanation in the [MelisEngine](../../../melis-engine/etc/MelisAI/doc/MelisEngine.md) doc):

- **MelisEngine** — the shared technical layer that **owns the CMS database model** (pages,
  templates, sites, languages, SEO, styles…) and exposes it via **table gateways + services +
  caching**, plus the `MelisTemplatingPlugin` base class.
- **MelisFront** — the **front-office rendering system** that turns a URL into a rendered page.
- **MelisCms** *(this module)* — the **back-office** to build & administer the websites.

**Key fact:** **MelisCms owns no database tables.** Everything it persists (pages, sites,
templates, styles, SEO, languages…) is read/written through **MelisEngine's `MelisEngineTable*`
gateways and services**, and every page it edits is **rendered live by MelisFront** in
"melis" mode. MelisCms is the UI/orchestration layer over the other two.

**Load order**: `melis-core` → `melis-front` → `melis-engine` → **`melis-cms`** (cms requires
all three).

---

## 1. Overview

`MelisCms` is the **back-office for building and administering Melis websites**. It provides
the **site tree**, the **page editor** (with live, in-place plugin editing rendered by
MelisFront), and the management tools for **sites, templates, styles, languages, SEO,
redirects and mini-templates**. It drives the page lifecycle (create → save draft → publish →
unpublish → delete) and **emits the `meliscms_page_*` events** that the whole ecosystem of
tool modules (news, slider, category2, page-historic, page-script-editor…) hooks into.

| Item | Value |
|---|---|
| Package name | `melisplatform/melis-cms` |
| Type | `melisplatform-module` |
| PHP namespace | `MelisCms\` → `src/` (PSR-4) |
| Melis category | `cms` |
| License | OSL-3.0 |
| PHP required | `^8.1 | ^8.3` |
| Owns DB tables? | **No** — uses MelisEngine's |

### Dependencies (`composer.json`)

- `melisplatform/melis-core` (`^5.2`) — auth, rights, events, config, dispatch
- `melisplatform/melis-engine` (`^5.2`) — the CMS data model (gateways/services) it reads/writes
- `melisplatform/melis-front` (`^5.2`) — the render pipeline it reuses for live page editing/preview

---

## 2. Functional concepts

- **Site** — the root of a page tree, bound to domains/languages (stored in engine's
  `melis_cms_site` + related tables).
- **Page** — a node in the site's tree. It has a **saved** (draft) version and a **published**
  (live) version (engine's `melis_cms_page_saved` / `melis_cms_page_published`). The BO edits
  the saved version; **publishing** copies it to published.
- **Template** — the layout/controller that renders a page type (engine's `melis_cms_template`).
- **Plugins/zones** — content blocks placed on a page (editable via drag-drop), all extending
  engine's `MelisTemplatingPlugin` and rendered by MelisFront.
- **Multilingual** — a page exists per language (engine's `melis_cms_page_lang`).

MelisCms has **no tables of its own** — all of the above live in **MelisEngine** (§0).

---

## 3. Back-office tools

Registered in `config/app.interface.php` (left menu) and `config/app.tools.php`. The main
tools:

| Tool | Controller(s) | Role |
|---|---|---|
| **Site tree** | `TreeSitesController` | The left-panel **tree of all sites & pages** (search, navigate, open a page) |
| **Page editor** | `PageController`, `PagePropertiesController`, `PageEditionController`, `PageSeoController`, `PageLanguagesController` | Create/edit a page: **Properties**, **content (drag-drop plugins on a live preview)**, **SEO**, **languages** tabs |
| **Sites tool** | `SitesController` (+ `Sites*Controller`: Properties, Domains, Languages, Translation, Config, ModuleLoader) | Create/manage **sites**: properties, domains, languages, translations, site config, per-site module loading |
| **Templates** | `ToolTemplateController` | Define page **templates** (layout/controller/action bindings, plugin containers) |
| **Styles** | `ToolStyleController` | Create/assign **CSS styles** to pages |
| **Languages** | `LanguageController` | Manage CMS **languages** |
| **Platform IDs** | `PlatformController` | Page-id allocation ranges per environment |
| **Site redirects** | `SiteRedirectController` | 301/302 **redirects** |
| **Mini-templates** | `MiniTemplateManagerController`, `MiniTemplateMenuManagerController` | Reusable content snippets + their menu categories |
| **Page import/export** | `PageImportController`, `PageExportController` | Move page trees in/out as files |
| **Plugin editing** | `FrontPluginsController`, `FrontPluginsModalController` | The plugin menu / drag-drop modals inside the page editor |

It also ships a **dashboard plugin** — `MelisCmsPagesIndicatorsPlugin` (counts of sites/pages,
published vs unpublished).

The tools are illustrated below, grouped by area.

#### 3.1 The CMS menu & site tree

![CMS menu entry](./images/meliscms-menu-entry.png)
*Caption: the MelisCms entry in the back-office left menu.*

![Site tree view](./images/meliscms-menu-site-treeview.png)
*Caption: the site tree — every site and its page hierarchy; selecting a page opens it in the editor.*

![Site tree — node actions](./images/meliscms-menu-site-treeview-actions.png)
*Caption: the actions available on a tree node (add/edit/delete page, duplicate, import/export…).*

![Site tree — duplicate tree action](./images/meliscms-menu-site-treeview-actions-duplicate-tree.png)
*Caption: duplicating a page/sub-tree from the tree actions.*

![Site tree — export action](./images/meliscms-menu-site-treeview-actions-export.png)
*Caption: exporting a page/tree to a file.*

![Site tree — import action](./images/meliscms-menu-site-treeview-actions-import.png)
*Caption: importing a page/tree from a file.*

#### 3.2 The page editor

The page is rendered **live by MelisFront in "melis" mode** with an editing overlay; it is
organized into tabs (Properties, Edition/content, SEO, Languages).

![Page — see details](./images/meliscms-page-menu-see-details.png)
*Caption: opening a page's details from the tree.*

![Page — display details](./images/meliscms-page-menu-display-details.png)
*Caption: the page details display.*

![Page editor — Properties tab](./images/meliscms-page-tab-properties.png)
*Caption: the Properties tab — name, type, template, language, menu visibility, style.*

![Page editor — Edition (content) tab](./images/meliscms-page-tab-edition.png)
*Caption: the Edition tab — the live page with drag-drop plugin editing.*

![Page editor — SEO tab](./images/meliscms-page-tab-seo.png)
*Caption: the SEO tab — URL, redirects, meta title/description.*

![Page editor — Languages tab](./images/meliscms-page-tab-languages.png)
*Caption: the Languages tab — the page's language versions.*

##### Edition tab — drag-drop plugins (detail)

![Edition — templating-plugins menu](./images/meliscms-page-tab-edition-menu-templating-plugins.png)
*Caption: the templating-plugins menu — the content blocks (all extend MelisEngine's
`MelisTemplatingPlugin`) draggable onto the page.*

![Edition — plugin icon](./images/meliscms-page-tab-edition-icon-plugin.png)
*Caption: a plugin's handle/icon on the page in edit mode.*

![Edition — drag-drop zone layouts](./images/meliscms-page-tab-edition-dragdropzone-layouts.png)
*Caption: the drag-drop zone layouts available to place plugins.*

![Edition — drag-drop zone layouts (2)](./images/meliscms-page-tab-edition-dragdropzone-layouts-2.png)
*Caption: more drag-drop zone layout options.*

![Edition — plugin config: template selection](./images/meliscms-page-tab-edition-plugin-config-template-selection.png)
*Caption: a plugin's configuration modal — choosing its rendering template.*

![Edition — plugin HTML rendering](./images/meliscms-page-tab-edition-plugin-html-rendering.png)
*Caption: an HTML plugin rendering its editable content.*

![Edition — mini-template manager from an HTML plugin](./images/meliscms-page-tab-edition-minitemplatemanager-from-htmlplugin.png)
*Caption: opening the mini-template manager from within an HTML plugin.*

#### 3.3 Sites tool

![Sites — list](./images/meliscms-tool-sites-list.png)
*Caption: the Sites tool — the list of sites.*

![New site — step 1](./images/meliscms-tool-sites-newsite-modal-step1.png)
![New site — step 2](./images/meliscms-tool-sites-newsite-modal-step2.png)
![New site — step 3](./images/meliscms-tool-sites-newsite-modal-step3.png)
![New site — step 4](./images/meliscms-tool-sites-newsite-modal-step4.png)
![New site — step 5](./images/meliscms-tool-sites-newsite-modal-step5.png)
*Caption: the new-site wizard (5 steps) — create a site and its initial configuration.*

![Site edit — Properties tab](./images/meliscms-tool-sites-edit-tab-properties.png)
*Caption: editing a site — Properties tab.*

![Site edit — Domains tab](./images/meliscms-tool-sites-edit-tab-domains.png)
*Caption: editing a site — Domains tab (domain bindings per environment).*

![Site edit — Languages tab](./images/meliscms-tool-sites-edit-tab-languages.png)
*Caption: editing a site — Languages tab (languages enabled for the site).*

![Site edit — Module loading tab](./images/meliscms-tool-sites-edit-tab-moduleloading.png)
*Caption: editing a site — Module Loading tab (which modules are active on the site).*

![Site edit — Site config tab](./images/meliscms-tool-sites-edit-tab-siteconfig.png)
*Caption: editing a site — Site Config tab (key/value site configuration).*

![Site edit — Translations tab](./images/meliscms-tool-sites-edit-tab-translations.png)
*Caption: editing a site — Translations tab (site-wide translation strings).*

![Site edit — Translations edit](./images/meliscms-tool-sites-edit-tab-translations-edit.png)
*Caption: editing a single site translation string.*

#### 3.4 Templates

![Templates — list](./images/meliscms-tool-templatemanager-list.png)
*Caption: the Template manager — the list of templates.*

![Templates — edit](./images/meliscms-tool-templatemanager-edit.png)
*Caption: editing a template (layout/controller/action bindings, plugin containers).*

#### 3.5 Styles

![Styles — list](./images/meliscms-tool-stylemanager-list.png)
*Caption: the Style manager — the list of CSS styles.*

![Styles — new](./images/meliscms-tool-stylemanager-new.png)
*Caption: creating a new style.*

#### 3.6 Languages

![Languages — list](./images/meliscms-tool-languages-frontoffice-list.png)
*Caption: the Languages tool — the CMS (front-office) languages.*

![Languages — edit](./images/meliscms-tool-languages-frontoffice-edit.png)
*Caption: editing a language.*

#### 3.7 Platform IDs

![Platform IDs — list](./images/meliscms-tool-platformids-list.png)
*Caption: the Platform IDs tool — page-id allocation ranges per environment.*

![Platform IDs — edit](./images/meliscms-tool-platformids-edit.png)
*Caption: editing a platform-id range.*

#### 3.8 Site redirects

![Site redirects — list](./images/meliscms-tool-siteredirect-list.png)
*Caption: the Site Redirects tool — the list of 301/302 redirects.*

![Site redirects — new](./images/meliscms-tool-siteredirect-new.png)
*Caption: creating a new redirect.*

#### 3.9 Mini-templates

![Mini-templates — list](./images/meliscms-tool-minitemplate-list.png)
*Caption: the Mini-template manager — reusable content snippets.*

![Mini-templates — edit](./images/meliscms-tool-minitemplate-edit.png)
*Caption: editing a mini-template.*

![Mini-templates — menu manager](./images/meliscms-tool-minitemplate-menu-manager.png)
*Caption: the Mini-template menu manager — organizing mini-templates into categories.*

#### 3.10 Dashboard

![Pages indicators dashboard widget](./images/meliscms-dashboardplugins-indicators.png)
*Caption: the Pages Indicators dashboard widget — counts of sites/pages, published vs unpublished.*

---

## 4. Services (`src/Service`)

| Service alias | Role |
|---|---|
| `MelisCmsPageService` | Save a page: tree, saved/published states, SEO, styles, languages — through engine gateways |
| `MelisCmsSiteService` | Save/retrieve sites; pages per site; 404/home pages; domains & languages |
| `MelisCmsRights` | Check the user's CMS access rights (per page / feature) |
| `MelisCmsSiteModuleLoadService` | Load/unload templating modules per site (coordinates with MelisFront / asset manager) |
| `MelisCmsSitesDomainsService` / `MelisCmsSitesPropertiesService` | Site domains / properties (404, home per language) |
| `MelisCmsPageGetterService` | Retrieve a page's rendered HTML from the engine cache |
| `MelisCmsPageExportService` / `MelisCmsPageImportService` | Export/import page trees as JSON |
| `MelisCmsMiniTemplateService` / `MelisCmsMiniTemplateGetterService` | Mini-template CRUD / HTML retrieval |

All of them operate on **MelisEngine** gateways (`MelisEngineTablePageTree`, `…PageSaved`,
`…PagePublished`, `…PageSeo`, `…PageLang`, `…PageStyle`, `…Template`, `…Site`, `…SiteDomain`,
`…CmsLang`, `…PlatformIds`, …) and engine services.

---

## 5. The page lifecycle & its events (the ecosystem hook)

The page lifecycle is implemented through **events** that MelisCms fires and its own listeners
(plus many other modules' listeners) handle. The real event names:

| Action | Events fired |
|---|---|
| Save draft | `meliscms_page_save_start` / `_end` (+ sub-events `…savetree_*`, `…saveproperties_*`, `…saveedition_*`, `…saveseo_*`) |
| Publish | `meliscms_page_publish_start` / `_end` |
| Unpublish | `meliscms_page_unpublish_start` / `_end` |
| Delete | `meliscms_page_delete_start` / `_end` (+ `…deleteseo_*`, `…delete_page_*`) |
| Move | `meliscms_page_move_start` / `_end` |
| Duplicate | `meliscms_page_duplicate_start` / `_end` |
| Plugin session | `meliscms_page_savesession_plugin_*`, `meliscms_page_removesession_plugin_*` |

MelisCms' own **listeners** (`src/Listener`, ~19) orchestrate these: `MelisCmsSavePageListener`
(saves tree + properties + edition + SEO + style), `MelisCmsPublishPageListener` /
`MelisCmsUnpublishPageListener` (rights check + move saved↔published),
`MelisCmsDeletePageListener` (rights + reassign lang + delete tree + SEO),
`MelisCmsFlashMessengerListener` (success/error messages),
`MelisCmsGetRightsTreeViewListener` (rights-filter the tree), plugin-session listeners, domain
& platform-id listeners, and the dashboard.

**This event surface is the platform's extension point.** Tool modules hook it, e.g.:
- **melis-cms-page-historic** logs `…publish_end` / `…save_end` / `…unpublish_end`.
- **melis-cms-page-script-editor** saves its scripts on page save/publish.
- **melis-front** invalidates Menu/Breadcrumb plugin caches on `…save_end` / `…publish_end` / `…delete_end` / `…move_end`.
- **news / slider / category2** etc. sync their own content on page events.

---

## 6. Cross-module links

### MelisCms → MelisEngine (data)
No tables of its own; every read/write goes through engine gateways & services (§4). Publishing
copies `melis_cms_page_saved` → `melis_cms_page_published`; the engine page cache is then
invalidated.

### MelisCms → MelisFront (live editing & preview)
The page editor shows the page **rendered live by MelisFront** in `melis` mode
(`/id/:idpage/renderMode/melis`) and previews the saved version via `/preview`. Plugin
drag-drop and re-rendering go through MelisFront's `MelisPluginRendererController`; the editable
zones/plugins all extend engine's `MelisTemplatingPlugin`. After save/publish, MelisFront's
cache listener clears the affected plugin caches so the public site updates.

### MelisCms ← other modules (events)
The `meliscms_page_*` events (§5) are consumed across the tool ecosystem (history, script
editor, news, slider, category2, …) — MelisCms is the conductor of the page lifecycle.

---

## 7. Quick code map

```
melis-cms/
├── composer.json                 → deps (core + engine + front), category cms
├── config/
│   ├── module.config.php         → routes, services, controllers, form factories, dashboard plugin
│   ├── app.interface.php         → the back-office left menu + tools tree
│   ├── app.tools.php             → DataTables/tool configs
│   └── app.forms.php             → page/site/template/style forms
├── src/
│   ├── Module.php                → bootstrap; wires the ~19 page-lifecycle/domain/user listeners
│   ├── Controller/               → 27 controllers: Page*, Sites*, ToolTemplate, ToolStyle, Tree, Language, Platform, SiteRedirect, MiniTemplate*, FrontPlugins*…
│   ├── Controller/DashboardPlugins/ → MelisCmsPagesIndicatorsPlugin
│   ├── Service/                  → 11 services (page, site, rights, module-load, domains, import/export, mini-template…)
│   ├── Listener/                 → 19 listeners (save/publish/unpublish/delete/move, flash, rights tree, plugin session, domains, platform ids…)
│   └── Form/Factory/             → site/template/style/language/plugin selects
├── view/                         → .phtml templates (tree, page editor tabs, tools, modals, dashboard)
├── public/                       → BO JS/CSS assets
├── language/                     → translations
└── etc/                          → MarketPlace + MelisAI/doc (this doc)
```
*(MelisCms has no `install/` SQL — it owns no tables; the schema is MelisEngine's.)*

---

## 8. Typical page lifecycle (back-office)

1. **Create** a page from the site tree (choose type, template, language).
2. **Edit** content on the live page (drag-drop plugins; fill editable zones via TinyMCE),
   set **Properties**, **SEO**, **languages** — the page renders live via MelisFront.
3. **Save draft** → `meliscms_page_save_start/_end` → writes the *saved* version (engine).
4. **Publish** → `meliscms_page_publish_start/_end` → copies saved → published; front caches cleared.
5. **Unpublish / Delete / Move / Duplicate** → respective `meliscms_page_*` events.
6. Tool modules react to those events (history, scripts, news/slider/category sync, cache).

---

## 9. Glossary

- **Saved vs published** — draft vs live version of a page (engine tables).
- **melis render mode** — MelisFront rendering a page editable-in-place for the BO.
- **Templating plugin / zone** — a content block (extends engine's `MelisTemplatingPlugin`).
- **Table gateway** — `MelisEngineTable*`; the only data-access path (MelisCms has none of its own).
- **Page lifecycle events** — `meliscms_page_{save,publish,unpublish,delete,move,duplicate}_{start,end}`.

---

## 10. Screenshot index (for on-demand retrieval)

All screenshots live in `./images/`. This table is the **filename → content** index the
MelisAI MCP uses to fetch a specific screenshot on demand.

| Image file | Content |
|---|---|
| `meliscms-menu-entry.png` | CMS entry in the back-office left menu |
| `meliscms-menu-site-treeview.png` | Site tree (all sites & pages) |
| `meliscms-menu-site-treeview-actions.png` | Site tree — node actions |
| `meliscms-menu-site-treeview-actions-duplicate-tree.png` | Site tree — duplicate page/tree |
| `meliscms-menu-site-treeview-actions-export.png` | Site tree — export page/tree |
| `meliscms-menu-site-treeview-actions-import.png` | Site tree — import page/tree |
| `meliscms-page-menu-see-details.png` | Page — see details from the tree |
| `meliscms-page-menu-display-details.png` | Page — details display |
| `meliscms-page-tab-properties.png` | Page editor — Properties tab |
| `meliscms-page-tab-edition.png` | Page editor — Edition (content) tab, live drag-drop editing |
| `meliscms-page-tab-seo.png` | Page editor — SEO tab |
| `meliscms-page-tab-languages.png` | Page editor — Languages tab |
| `meliscms-page-tab-edition-menu-templating-plugins.png` | Edition — templating-plugins menu |
| `meliscms-page-tab-edition-icon-plugin.png` | Edition — plugin handle/icon on the page |
| `meliscms-page-tab-edition-dragdropzone-layouts.png` | Edition — drag-drop zone layouts |
| `meliscms-page-tab-edition-dragdropzone-layouts-2.png` | Edition — drag-drop zone layouts (more) |
| `meliscms-page-tab-edition-plugin-config-template-selection.png` | Edition — plugin config: template selection |
| `meliscms-page-tab-edition-plugin-html-rendering.png` | Edition — HTML plugin rendering |
| `meliscms-page-tab-edition-minitemplatemanager-from-htmlplugin.png` | Edition — mini-template manager from an HTML plugin |
| `meliscms-tool-sites-list.png` | Sites tool — list of sites |
| `meliscms-tool-sites-newsite-modal-step1.png` | New-site wizard — step 1 |
| `meliscms-tool-sites-newsite-modal-step2.png` | New-site wizard — step 2 |
| `meliscms-tool-sites-newsite-modal-step3.png` | New-site wizard — step 3 |
| `meliscms-tool-sites-newsite-modal-step4.png` | New-site wizard — step 4 |
| `meliscms-tool-sites-newsite-modal-step5.png` | New-site wizard — step 5 |
| `meliscms-tool-sites-edit-tab-properties.png` | Site edit — Properties tab |
| `meliscms-tool-sites-edit-tab-domains.png` | Site edit — Domains tab |
| `meliscms-tool-sites-edit-tab-languages.png` | Site edit — Languages tab |
| `meliscms-tool-sites-edit-tab-moduleloading.png` | Site edit — Module Loading tab |
| `meliscms-tool-sites-edit-tab-siteconfig.png` | Site edit — Site Config tab |
| `meliscms-tool-sites-edit-tab-translations.png` | Site edit — Translations tab |
| `meliscms-tool-sites-edit-tab-translations-edit.png` | Site edit — edit a translation string |
| `meliscms-tool-templatemanager-list.png` | Template manager — list |
| `meliscms-tool-templatemanager-edit.png` | Template manager — edit a template |
| `meliscms-tool-stylemanager-list.png` | Style manager — list |
| `meliscms-tool-stylemanager-new.png` | Style manager — new style |
| `meliscms-tool-languages-frontoffice-list.png` | Languages tool — list |
| `meliscms-tool-languages-frontoffice-edit.png` | Languages tool — edit |
| `meliscms-tool-platformids-list.png` | Platform IDs — list |
| `meliscms-tool-platformids-edit.png` | Platform IDs — edit |
| `meliscms-tool-siteredirect-list.png` | Site redirects — list |
| `meliscms-tool-siteredirect-new.png` | Site redirects — new |
| `meliscms-tool-minitemplate-list.png` | Mini-templates — list |
| `meliscms-tool-minitemplate-edit.png` | Mini-templates — edit |
| `meliscms-tool-minitemplate-menu-manager.png` | Mini-templates — menu manager |
| `meliscms-dashboardplugins-indicators.png` | Pages Indicators dashboard widget |

---

*Document for AI consumption (MelisAI MCP) — describes the `melisplatform/melis-cms` module
and its place in the MelisCms / MelisFront / MelisEngine trio. Last reviewed 2026-06-08
against the current source.*
