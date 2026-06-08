---
title: MelisCms module
package: melisplatform/melis-cms
doc_type: module-documentation
audience: [users, developers, ai]
language: en
module_version: unversioned   # no `version` field in composer.json; this doc tracks the current source
last_reviewed: 2026-06-08
maintainer: Melis Technology
keywords: [cms, back-office, pages, page-tree, page-editor, sites, templates, styles, languages, seo, publish, plugins, drag-drop, melis]
screenshots_dir: ./images
---

# MelisCms — Functional & Technical Documentation (for AI)

> **What this is.** MelisCms is the **back-office where you build and run your websites** on
> the Melis platform: you create sites, build their pages in a tree, fill those pages with
> content using drag-and-drop plugins on a live preview, manage templates, styles, languages
> and SEO, then publish.
>
> **How this document is organised — two clearly separated parts:**
> - **[Part A — Functional Guide](#part-a--functional-guide)** — for **everyday users** (and
>   the platform's chat assistant that helps them). Plain language: *what each tool is for,
>   where to find it, how to do things step by step, and what each option means.*
> - **[Part B — Technical Reference](#part-b--technical-reference)** — for **developers and AI
>   building inside the platform**: controllers, services, table gateways, events, cross-module
>   wiring and file paths.
>
> **Audience**: consumed by the **MelisAI** module (an MCP that answers user questions and may
> be used by an AI to build things). It fetches this `.md` and the screenshots in `./images/`
> on demand; the **[Screenshot index](#screenshot-index)** is the filename→content lookup.
>
> **Status**: reviewed 2026-06-08 against the current source (no semantic version in `composer.json`).

---
---

# PART A — Functional Guide

*For users and the chat assistant. If you want to know "where is X" or "how do I Y", read this part.*

## A1. What you can do with MelisCms

MelisCms is the **administration back-office** of your websites. With it you can:

- **Build sites** — create a website, give it a name, attach it to one or more **domains**
  (e.g. `www.example.com`), choose which **languages** it speaks, and decide which features
  (modules) are active on it.
- **Build pages** — organise pages in a **tree** (like folders and files), create new pages,
  move/duplicate/delete them, and import/export whole branches.
- **Put content on pages** — open a page and edit it **directly on a live preview**: drag
  ready-made **content blocks** (called *plugins*) onto the page — text, images, menus,
  news, sliders, forms… — then fill them in.
- **Control how pages look** — assign a **template** (the page layout) and **styles** (CSS).
- **Manage languages** — every page can have a version per language.
- **Manage SEO** — set each page's friendly **URL**, **meta title/description**, and create
  **redirects** (so old links keep working).
- **Publish** — work on a **draft** as long as you like, preview it, then **publish** to make
  it live for visitors.

Everything you create here is what visitors see on the public website (rendered by the
companion *MelisFront* system).

## A2. Finding your way around

The back-office has a **left menu**. MelisCms adds:

- a **Sites** entry that opens the **site tree** (your sites and all their pages), and
- a group of **tools** (Sites, Templates, Styles, Languages, Platform IDs, Site redirects,
  Mini Templates & Plugins).

![CMS menu entry](./images/meliscms-menu-entry.png)
*The MelisCms entry in the back-office left menu — your starting point.*

You spend most of your time in two places: the **site tree** (to find/organise pages) and the
**page editor** (to put content on a page).

## A3. Key words explained (in plain language)

- **Site** — a website. It has a name, one or more **domains**, and a set of **languages**.
- **Page** — one web page. Pages live in a **tree** under a site. A "page" can also be a
  **folder** (a container with no content of its own) or the **site root**.
- **Draft vs Published** — while you edit, you are changing the **draft** (saved) version.
  Visitors only ever see the **published** version. **Publishing** copies your draft live.
  This lets you work safely and preview before going live.
- **Template** — the **layout** of a page (header, columns, footer, where content goes). You
  pick a template when creating a page.
- **Style** — a **CSS** stylesheet you can attach to pages to change their appearance.
- **Plugin / content block** — a reusable piece of content you drop onto a page (a text zone,
  an image, a menu, a news list, a slider, a contact form…). You arrange and configure them
  visually.
- **Zone / container** — the spots on the page (defined by the template) where you can drop
  plugins.
- **Mini-template** — a pre-built, ready-to-reuse content block an editor can pick instead of
  building one from scratch.

---

## A4. The site tree — find and organise pages

**What it's for:** the site tree is the map of all your sites and every page inside them. From
here you open a page to edit it, and you create, move, duplicate, import/export or delete
pages.

**Where:** left menu → **Sites** (the tree shows in the left panel).

![Site tree view](./images/meliscms-menu-site-treeview.png)
*The site tree — each site with its page hierarchy. Click a page to open it in the editor.*

**What you can do here (right-click / the actions menu on a page):**

![Site tree — node actions](./images/meliscms-menu-site-treeview-actions.png)
*The actions available on a page in the tree.*

- **Add a page** under the selected one (creates a child).
- **Open / see the page's details.**

  ![Page — see details](./images/meliscms-page-menu-see-details.png)
  ![Page — details display](./images/meliscms-page-menu-display-details.png)
  *Opening a page and viewing its details from the tree.*

- **Duplicate** a page or a whole branch (page + its children).

  ![Site tree — duplicate page/tree](./images/meliscms-menu-site-treeview-actions-duplicate-tree.png)
  *Duplicate a page or an entire sub-tree — handy to reuse an existing structure.*

- **Export** a page (optionally with its children and resources) to a file, and **Import** it
  elsewhere — useful to move content between sites or environments.

  ![Site tree — export](./images/meliscms-menu-site-treeview-actions-export.png)
  ![Site tree — import](./images/meliscms-menu-site-treeview-actions-import.png)
  *Export a page/tree to a file, and import a page/tree from a file.*

**Tip:** moving a page is done by **drag-and-drop** in the tree; deleting a page also removes
its content and SEO. Deleting/moving cannot be undone, so duplicate first if unsure.

---

## A5. The page editor — put content on a page

**What it's for:** the page editor is where you build a page's content and settings. The page
is shown as a **live preview** with an **editing overlay**, organised into tabs.

**Where:** double-click a page in the site tree.

The main tabs are **Properties**, **Edition** (the content), **SEO** and **Languages**. (Other
installed modules can add more tabs here — e.g. a *Historic* tab that logs every change, or a
*Scripts* tab to inject custom code.)

### A5.1 Properties tab — the page's settings

**What you set here:** the page **name**, its **type** (a normal *page*, a *folder*, etc.),
the **template** (layout) it uses, its **language**, whether it appears **in menus**, and the
**style** to apply.

![Page editor — Properties tab](./images/meliscms-page-tab-properties.png)
*Properties — name, type, template, language, menu visibility and style.*

### A5.2 Edition tab — add and arrange content (drag-and-drop)

**What it's for:** this is where you actually fill the page. The page renders live, and you
**drag content blocks (plugins) into the page's zones**, then configure each one.

![Page editor — Edition tab](./images/meliscms-page-tab-edition.png)
*The Edition tab — the live page with the drag-and-drop editing overlay.*

**How to add content:**

1. Open the **templating-plugins menu** (the list of available content blocks).

   ![Edition — templating-plugins menu](./images/meliscms-page-tab-edition-menu-templating-plugins.png)
   *The plugins menu — the content blocks you can drop onto the page (text, media, menu,
   news, slider, form…). Which blocks appear depends on the modules installed on the site.*

2. **Drag** a block onto a **drop zone** on the page. Zones come from the page's template; you
   can also use layout zones to split an area into columns.

   ![Edition — drag-drop zone layouts](./images/meliscms-page-tab-edition-dragdropzone-layouts.png)
   ![Edition — more layouts](./images/meliscms-page-tab-edition-dragdropzone-layouts-2.png)
   *Drag-drop zone layouts — choose how to split a zone to place your blocks.*

3. Each placed block shows a **handle/icon** to select, move, configure or delete it.

   ![Edition — plugin handle/icon](./images/meliscms-page-tab-edition-icon-plugin.png)
   *A block's handle on the page — select it to edit or open its settings.*

4. **Edit the block's content.** A text/HTML block is edited in place with a rich-text editor;
   an image block opens the media browser; etc.

   ![Edition — HTML plugin rendering](./images/meliscms-page-tab-edition-plugin-html-rendering.png)
   *An editable HTML block rendering its content directly on the page.*

5. **Configure the block's options** in its settings modal — for example, choosing the
   **template** a block uses to render (a news list, a slider, etc.).

   ![Edition — plugin config: template selection](./images/meliscms-page-tab-edition-plugin-config-template-selection.png)
   *A block's settings — e.g. picking which template/layout the block renders with.*

6. **Reuse ready-made blocks** via the **mini-template manager**, reachable from a block, to
   insert pre-built content instead of starting from scratch.

   ![Edition — mini-template manager](./images/meliscms-page-tab-edition-minitemplatemanager-from-htmlplugin.png)
   *Opening the mini-template manager to drop in a ready-made block.*

**Common blocks you'll find** (the standard ones; more appear with extra modules):
- **Text / HTML zone** — rich-text content (titles, paragraphs…).
- **Media** — an image/file.
- **Menu** — a navigation menu built from your page tree.
- **Breadcrumb** — the "you are here" trail.
- **List from a folder** — repeat the sub-pages of a folder as a list (news teasers, etc.).
- **Block / section** — a layout container to group content.
- **GDPR banner** — the cookie/consent banner.
- *Module blocks* (when installed): **News** (latest/list/article), **Slider**, **Categories**,
  **Contact form** (Prospects), and more — documented in their own module docs.

### A5.3 SEO tab — how the page appears in search & its address

**What you set here:** the page's friendly **URL** (its web address), its **meta title** and
**meta description** (what shows in search results), and optional **redirects**.

![Page editor — SEO tab](./images/meliscms-page-tab-seo.png)
*SEO — the page's URL, meta title/description and redirects.*

### A5.4 Languages tab — the page in several languages

**What it's for:** create and manage **language versions** of the page. Each language has its
own content and SEO, but they share the same place in the tree.

![Page editor — Languages tab](./images/meliscms-page-tab-languages.png)
*Languages — manage the page's versions per language.*

### A5.5 Saving vs Publishing (important)

- **Save** stores your **draft** — nothing changes for visitors yet.
- **Preview** shows you the draft as it will look.
- **Publish** makes the current draft **live** for visitors.
- **Unpublish** takes a page offline (back to draft only).

> Rule of thumb: edit freely, **Save** as you go, **Preview** to check, then **Publish** when ready.

---

## A6. Sites tool — create and manage websites

**What it's for:** create a brand-new site and manage everything about an existing one.

**Where:** left menu → tools → **Sites**.

![Sites — list](./images/meliscms-tool-sites-list.png)
*The Sites tool — the list of your sites.*

### How do I create a new site?

Click **add** and follow the **5-step wizard** — it walks you through naming the site,
choosing its starting template/theme, setting its domain, picking its languages, and
confirming:

![New site — step 1](./images/meliscms-tool-sites-newsite-modal-step1.png)
![New site — step 2](./images/meliscms-tool-sites-newsite-modal-step2.png)
![New site — step 3](./images/meliscms-tool-sites-newsite-modal-step3.png)
![New site — step 4](./images/meliscms-tool-sites-newsite-modal-step4.png)
![New site — step 5](./images/meliscms-tool-sites-newsite-modal-step5.png)
*The new-site wizard, step by step.*

### Managing an existing site (the edit tabs)

Open a site to manage it through tabs:

- **Properties** — the site name/label, its **home page**, its **404** (page-not-found) page.

  ![Site edit — Properties](./images/meliscms-tool-sites-edit-tab-properties.png)

- **Domains** — the web addresses the site answers on (one or several, per environment).

  ![Site edit — Domains](./images/meliscms-tool-sites-edit-tab-domains.png)

- **Languages** — which languages this site offers.

  ![Site edit — Languages](./images/meliscms-tool-sites-edit-tab-languages.png)

- **Module loading** — switch features (modules) **on/off for this site** (e.g. enable News,
  the script editor, etc.). A feature only works on a site if it's loaded here.

  ![Site edit — Module loading](./images/meliscms-tool-sites-edit-tab-moduleloading.png)

- **Site config** — site-wide **settings** (key/value), e.g. analytics ids, contact emails…

  ![Site edit — Site config](./images/meliscms-tool-sites-edit-tab-siteconfig.png)

- **Translations** — site-wide **text strings** used by templates, editable per language.

  ![Site edit — Translations](./images/meliscms-tool-sites-edit-tab-translations.png)
  ![Site edit — edit a translation](./images/meliscms-tool-sites-edit-tab-translations-edit.png)
  *Manage and edit the site's translation strings.*

---

## A7. Templates tool — page layouts

**What it's for:** define the **templates** (layouts) that pages can use. A template decides
the overall structure of a page and where content zones sit.

**Where:** left menu → tools → **Templates**.

![Templates — list](./images/meliscms-tool-templatemanager-list.png)
*The list of templates.*

![Templates — edit](./images/meliscms-tool-templatemanager-edit.png)
*Editing a template — its name, type and the code (layout/controller) it maps to.*

**Tip:** you usually create templates once (often by a developer) and then editors just **pick
a template** when creating pages.

---

## A8. Styles tool — CSS for pages

**What it's for:** manage **styles** (CSS) that can be attached to pages to control appearance.

**Where:** left menu → tools → **Styles**.

![Styles — list](./images/meliscms-tool-stylemanager-list.png)
![Styles — new](./images/meliscms-tool-stylemanager-new.png)
*The styles list and the create-style screen.*

---

## A9. Languages tool — the languages of the platform

**What it's for:** manage the **languages** available to sites (e.g. English, French). You
enable a language here, then turn it on per-site (§A6) and create page versions for it (§A5.4).

**Where:** left menu → tools → **Languages**.

![Languages — list](./images/meliscms-tool-languages-frontoffice-list.png)
![Languages — edit](./images/meliscms-tool-languages-frontoffice-edit.png)
*The languages list and editing a language.*

---

## A10. Platform IDs tool — page-id ranges (advanced)

**What it's for:** an advanced setting that reserves **ranges of page IDs** per environment so
content created on different servers (dev / staging / production) never collides when you move
it around. Most editors never need this; administrators set it once.

**Where:** left menu → tools → **Platform IDs**.

![Platform IDs — list](./images/meliscms-tool-platformids-list.png)
![Platform IDs — edit](./images/meliscms-tool-platformids-edit.png)
*The platform-id ranges and editing one.*

---

## A11. Site redirects tool — keep old links working

**What it's for:** create **redirects** (301/302) so an old or changed URL automatically sends
visitors (and search engines) to the right page — essential when you rename pages.

**Where:** left menu → tools → **Site redirects**.

![Site redirects — list](./images/meliscms-tool-siteredirect-list.png)
![Site redirects — new](./images/meliscms-tool-siteredirect-new.png)
*The redirects list and creating a new redirect (old URL → new URL).*

---

## A12. Mini Templates & Plugins — reusable content

**What it's for:** build and organise **mini-templates** — ready-made content blocks editors
can drop onto pages (see §A5.2). The **menu manager** groups them into categories so they're
easy to find in the editor.

**Where:** left menu → tools → **Mini Templates & Plugins**.

![Mini-templates — list](./images/meliscms-tool-minitemplate-list.png)
![Mini-templates — edit](./images/meliscms-tool-minitemplate-edit.png)
![Mini-templates — menu manager](./images/meliscms-tool-minitemplate-menu-manager.png)
*Managing mini-templates and organising them into menu categories.*

---

## A13. Dashboard widget — Pages indicators

On the back-office **Dashboard**, MelisCms can show a **Pages Indicators** widget: how many
sites and pages exist, and how many are published vs not.

![Pages indicators widget](./images/meliscms-dashboardplugins-indicators.png)
*A quick health view of your sites and pages on the dashboard.*

---

## A14. Common tasks — "How do I…?"

- **Create a new website** → Tools → **Sites** → *add* → follow the 5-step wizard (§A6).
- **Create a page** → in the **site tree** (§A4), select the parent, choose *add page*, set its
  Properties (name, template, language), then go to the **Edition** tab to add content.
- **Add content to a page** → page editor → **Edition** tab → open the plugins menu and **drag**
  a block (text, image, menu, news…) into a zone, then click it to edit/configure (§A5.2).
- **Add a navigation menu** → drag the **Menu** block into the header zone; it builds from your
  page tree (pages flagged "show in menu" in their Properties).
- **Add an image** → drag the **Media** block, then pick the image from the media browser.
- **Change a page's web address** → page editor → **SEO** tab → edit the URL (and add a
  **redirect** in Tools → Site redirects if the old URL is already public).
- **Publish a page** → in the page editor, **Save** then **Publish** (§A5.5).
- **Make a page available in another language** → enable the language (§A9), turn it on for the
  site (§A6 → Languages), then use the page **Languages** tab (§A5.4).
- **Reuse an existing page structure** → in the tree, **Duplicate** the page/branch (§A4).
- **Move content between sites/servers** → **Export** a page/tree then **Import** it (§A4).
- **Turn a feature on for a site** (News, etc.) → Tools → Sites → open the site → **Module
  loading** (§A6).
- **Enable the cookie/GDPR banner** → drag the **GDPR banner** block onto the layout.

---
---

# PART B — Technical Reference

*For developers and AI building inside the platform. Names below are real identifiers from the
source.*

## B1. Module metadata & dependencies

| Item | Value |
|---|---|
| Package name | `melisplatform/melis-cms` |
| Type | `melisplatform-module` |
| PHP namespace | `MelisCms\` → `src/` (PSR-4) |
| Melis category | `cms` |
| License | OSL-3.0 |
| PHP required | `^8.1 | ^8.3` |
| Owns DB tables? | **No** — uses MelisEngine's |

Dependencies (`composer.json`): `melisplatform/melis-core` (auth, rights, events, config,
dispatch), `melisplatform/melis-engine` (the CMS data model — gateways/services it reads &
writes), `melisplatform/melis-front` (the render pipeline reused for live page editing/preview).

## B2. The trio architecture & load order

MelisCms is one of three core modules that must be understood together (full detail in the
[MelisEngine](../../../melis-engine/etc/MelisAI/doc/MelisEngine.md) and
[MelisFront](../../../melis-front/etc/MelisAI/doc/MelisFront.md) docs):

- **MelisEngine** — owns the CMS database model (pages, sites, templates, languages, SEO,
  styles…), exposes it via `MelisEngineTable*` gateways + `MelisEngine*` services + caching,
  and defines the `MelisTemplatingPlugin` base class.
- **MelisFront** — the front render pipeline; its **`melis` render mode** makes a live page
  editable in the BO.
- **MelisCms** — the **tableless back-office** orchestration layer over both.

**Load order:** `melis-core` → `melis-front` → `melis-engine` → **`melis-cms`**.

## B3. Data access — no own tables

MelisCms persists nothing directly; all reads/writes go through MelisEngine gateways, notably:
`MelisEngineTablePageTree`, `MelisEngineTablePageSaved`, `MelisEngineTablePagePublished`,
`MelisEngineTablePageSeo`, `MelisEngineTablePageLang`, `MelisEngineTablePageStyle`,
`MelisEngineTableTemplate`, `MelisEngineTableStyle`, `MelisEngineTableSite`,
`MelisEngineTableSiteDomain`, `MelisEngineTableCmsLang`, `MelisEngineTableCmsSiteHome`,
`MelisEngineTableSite404`, `MelisEngineTableCmsSiteLangs`, `MelisEngineTablePlatformIds`,
`MelisEngineTableFlaggedTemplate` — plus engine services (`MelisEnginePage`, `MelisEngineTree`,
`MelisEngineCacheSystem`, …). Publishing copies `melis_cms_page_saved` → `melis_cms_page_published`.

## B4. Controllers (27, by feature)

- **Page**: `PageController` (lifecycle: save/publish/unpublish/delete/move/duplicate),
  `PagePropertiesController`, `PageEditionController` (content tab, plugin drag-drop, session),
  `PageSeoController`, `PageLanguagesController`, `PageDuplicationController`,
  `PageExportController`, `PageImportController`.
- **Sites**: `SitesController` + `Sites{Properties,Domains,Languages,Translation,Config,ModuleLoader}Controller`.
- **Templates / Styles**: `ToolTemplateController`, `ToolStyleController`.
- **Other tools**: `TreeSitesController` (site tree), `LanguageController`,
  `PlatformController`, `SiteRedirectController`, `MiniTemplateManagerController`,
  `MiniTemplateMenuManagerController`, `GdprBannerController`.
- **Plugin editing**: `FrontPluginsController`, `FrontPluginsModalController` (the plugins menu
  & drag-drop modals in the page editor).
- `MelisSetupController`, `IndexController`.

## B5. Services (11)

`MelisCmsPageService` (save page tree/saved/published/SEO/style/lang), `MelisCmsSiteService`,
`MelisCmsRights` (CMS access rights), `MelisCmsSiteModuleLoadService` (per-site module
loading), `MelisCmsSitesDomainsService`, `MelisCmsSitesPropertiesService`,
`MelisCmsPageGetterService` (rendered HTML from engine cache),
`MelisCmsPageExportService` / `MelisCmsPageImportService`,
`MelisCmsMiniTemplateService` / `MelisCmsMiniTemplateGetterService`. All operate on engine
gateways/services.

## B6. Listeners (19)

Wired in `Module.php` `onBootstrap` (back-office route only), bound to the `MelisCms` event
identifier. Page lifecycle: `MelisCmsSavePageListener` (orchestrates tree+properties+edition+
SEO+style saves), `MelisCmsPublishPageListener`, `MelisCmsUnpublishPageListener`,
`MelisCmsDeletePageListener`, `MelisCmsPageGetterListener` (cache). Plus
`MelisCmsFlashMessengerListener`, `MelisCmsGetRightsTreeViewListener` (rights-filter the tree),
plugin-session listeners (`MelisCmsPageEditionSavePluginSessionListener`,
`MelisCmsPluginSaveEditionSessionListener`, `MelisCmsAddPluginContainerListener`),
domain/platform/user listeners, `MelisCmsDeletePluginMenuCachedListener`.

## B7. Page lifecycle events (the ecosystem extension point)

MelisCms fires these (others hook them — news, slider, category2, page-historic,
page-script-editor, and MelisFront's cache invalidation):

| Action | Events |
|---|---|
| Save draft | `meliscms_page_save_start` / `_end` (+ `…savetree_*`, `…saveproperties_*`, `…saveedition_*`, `…saveseo_*`) |
| Publish | `meliscms_page_publish_start` / `_end` |
| Unpublish | `meliscms_page_unpublish_start` / `_end` |
| Delete | `meliscms_page_delete_start` / `_end` (+ `…deleteseo_*`, `…delete_page_*`) |
| Move | `meliscms_page_move_start` / `_end` |
| Duplicate | `meliscms_page_duplicate_start` / `_end` |
| Plugin session | `meliscms_page_savesession_plugin_*`, `meliscms_page_removesession_plugin_*` |

Other useful hooks: `melis_cms_page_tabs_alter` (add/remove page-editor tabs),
`modify_page_properties_form_config` (alter the Properties form).

## B8. Forms, factories & dashboard plugin

Form-element factories populate the BO selects from engine data: `MelisCmsTemplateSelect`,
`MelisCmsStyleSelect`, `MelisCmsLanguageSelect`, `MelisCmsPageLanguagesSelect`,
`MelisCmsPlatformSelect` / `MelisCmsPlatformIDsSelect`, `MelisCmsPluginSiteSelect`,
`MelisCmsPluginSiteModuleSelect`. Dashboard: `MelisCmsPagesIndicatorsPlugin`
(`DashboardPlugins/`, template `melis-cms/dashboard/page-indicators`).

## B9. Cross-module wiring

- **→ MelisEngine**: all data via gateways/services (§B3); publish invalidates the engine page cache.
- **→ MelisFront**: the editor renders the page live in `melis` mode
  (`/id/:idpage/renderMode/melis`, `/preview` for the saved version); plugin drag-drop &
  re-render via `MelisPluginRendererController`; editable blocks all extend engine's
  `MelisTemplatingPlugin`. After save/publish, `MelisFrontDeletePluginCacheListener` clears
  affected plugin caches.
- **← Modules**: the `meliscms_page_*` events are consumed across the tool ecosystem.

## B10. Quick code map

```
melis-cms/
├── composer.json                 → deps (core + engine + front), category cms
├── config/
│   ├── module.config.php         → routes, services, controllers, form factories, dashboard plugin
│   ├── app.interface.php         → back-office left menu + tools tree
│   ├── app.tools.php             → DataTable/tool configs
│   └── app.forms.php             → page/site/template/style forms
├── src/
│   ├── Module.php                → bootstrap; wires the 19 listeners
│   ├── Controller/               → 27 controllers (Page*, Sites*, ToolTemplate, ToolStyle, Tree, Language, Platform, SiteRedirect, MiniTemplate*, FrontPlugins*…)
│   ├── Controller/DashboardPlugins/ → MelisCmsPagesIndicatorsPlugin
│   ├── Service/                  → 11 services
│   ├── Listener/                 → 19 listeners
│   └── Form/Factory/             → BO selects
├── view/                         → .phtml templates (tree, page editor tabs, tools, modals, dashboard)
├── public/ · language/           → BO assets · translations
└── etc/                          → MarketPlace + MelisAI/doc (this doc)
```
*(No `install/` SQL — MelisCms owns no tables; the schema is MelisEngine's.)*

---

## Screenshot index

Filename → content lookup for the MelisAI MCP. All under `./images/`.

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

*Document for AI consumption (MelisAI MCP) — `melisplatform/melis-cms`. Part A = functional
guide for users; Part B = technical reference for developers/AI. Last reviewed 2026-06-08.*
