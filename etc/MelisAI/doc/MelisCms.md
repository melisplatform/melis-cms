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
>   building inside the platform**: how it works, with **code examples** for the important
>   services, events and extension points.
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
**page editor** (to put content on a page). The **side tools** (§A6–A12) are used less often but
control important global settings — they're explained in detail because they're the least
obvious.

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

## The side tools (left menu) — explained

*These tools each have their own left-menu entry. They're used less often than the page editor,
so they're the most confusing to users — here's what each one really does and when you need it.*

## A6. Sites tool — create and manage websites

**What it's for.** A "site" is one whole website: its identity (name), the **web addresses**
(domains) it answers on, the **languages** it offers, which **features (modules)** are turned on
for it, and global **settings/translations**. The Sites tool is where all of that lives. You
come here to **create a new website**, or to change anything that applies to a **whole site**
(rather than to a single page).

**Where:** left menu → **Sites** tool.

![Sites — list](./images/meliscms-tool-sites-list.png)
*The Sites tool — the list of your sites. Each row is a complete website.*

### How do I create a new site?

Click **add** and follow the **5-step wizard**. It walks you, screen by screen, through the
choices needed to spin up a working website: naming it, picking the **template/theme** it starts
from, attaching its **domain(s)**, choosing its **language(s)**, and a final confirmation that
creates the site, its home page and base configuration:

![New site — step 1](./images/meliscms-tool-sites-newsite-modal-step1.png)
![New site — step 2](./images/meliscms-tool-sites-newsite-modal-step2.png)
![New site — step 3](./images/meliscms-tool-sites-newsite-modal-step3.png)
![New site — step 4](./images/meliscms-tool-sites-newsite-modal-step4.png)
![New site — step 5](./images/meliscms-tool-sites-newsite-modal-step5.png)
*The new-site wizard, step by step. After it finishes, the new site appears in the site tree
ready to receive pages.*

### Managing an existing site — the edit tabs (what each one is for)

Open a site to manage it through tabs. Each tab governs one aspect of the whole site:

- **Properties** — the site's **name/label**, its **home page** (the page shown at the root
  `/`), and its **404 page** (what visitors see when a URL doesn't exist). Set these once per
  site; the home page is what `www.example.com` resolves to.

  ![Site edit — Properties](./images/meliscms-tool-sites-edit-tab-properties.png)

- **Domains** — the **web addresses** the site answers on. A single site can have several
  domains, and **different domains per environment** (e.g. `dev.example.com` on the dev server,
  `www.example.com` in production). This is how Melis knows which site to show for an incoming
  request. If your site "isn't found" on a URL, this tab is usually why.

  ![Site edit — Domains](./images/meliscms-tool-sites-edit-tab-domains.png)

- **Languages** — which **languages this site offers**. Only languages enabled here can be used
  for the site's pages. (Languages themselves are created in the Languages tool, §A9.) Add a
  language here before you can create page versions in it.

  ![Site edit — Languages](./images/meliscms-tool-sites-edit-tab-languages.png)

- **Module loading** — the **on/off switches for features on this site**. A module (News, the
  Script Editor, a custom template module, etc.) only works on a site if it is **loaded here**.
  This is the single most common "why doesn't my feature appear?" answer: the module exists on
  the platform but hasn't been loaded onto this particular site. Toggle it on, save, and the
  feature (its plugins, its tools) becomes available for the site.

  ![Site edit — Module loading](./images/meliscms-tool-sites-edit-tab-moduleloading.png)

- **Site config** — site-wide **settings as key/value pairs**. Templates and modules read these
  to avoid hard-coding things: a Google Analytics id, a contact email, an API key, feature
  flags… Anything that should be configurable per site without touching code goes here, and a
  template reads it with the `SiteConfig` helper.

  ![Site edit — Site config](./images/meliscms-tool-sites-edit-tab-siteconfig.png)

- **Translations** — the site's **text strings**, editable per language. These are the labels
  that templates print via the `siteTranslate` helper (button captions, form labels, generic
  wording) — separate from page content, so the same template can be reused across languages.
  Add a key, give it a value in each language, and the template shows the right wording
  automatically.

  ![Site edit — Translations](./images/meliscms-tool-sites-edit-tab-translations.png)
  ![Site edit — edit a translation](./images/meliscms-tool-sites-edit-tab-translations-edit.png)
  *Manage the site's translation strings and edit a string's value per language.*

> **In short:** Properties/Domains/Languages define *what the site is and where it lives*;
> Module loading turns *features* on; Site config and Translations hold the *settings and
> wording* that templates and modules rely on.

---

## A7. Templates tool — the page layouts

**What it's for.** A **template** is the **skeleton of a page** — the header, the footer, the
columns, and the **zones** where editors are allowed to drop content. When you create a page you
**choose a template**, and that decides the page's overall structure. The Templates tool is
where those skeletons are defined and maintained.

**Why it can feel obscure:** most editors never create templates — they just pick one. Templates
are usually set up once (often by a developer or integrator) and reused across many pages. You
come here only to add a new layout or adjust an existing one.

**Where:** left menu → **Templates** tool.

![Templates — list](./images/meliscms-tool-templatemanager-list.png)
*The list of templates available to your sites.*

![Templates — edit](./images/meliscms-tool-templatemanager-edit.png)
*Editing a template — its name, its type, and the code it maps to. A template ties a friendly
name to an actual layout/controller (the `.phtml` that produces the HTML) so editors can pick it
by name when building a page.*

**Tip:** if a page "doesn't have the right zones" to drop a block into, it's the template that
defines those zones — change the template (or its zones) rather than the page.

---

## A8. Styles tool — CSS applied to pages

**What it's for.** **Styles** are **CSS stylesheets** you can attach to pages to change their
appearance (fonts, colours, spacing…). Instead of editing code, you register a style here and
then **assign it to a page** in the page's Properties. The Styles tool manages that library of
styles.

**Where:** left menu → **Styles** tool.

![Styles — list](./images/meliscms-tool-stylemanager-list.png)
![Styles — new](./images/meliscms-tool-stylemanager-new.png)
*The styles library and the create-style screen. Create a style (give it a name and its CSS
path/source), then attach it to pages from the page Properties tab (§A5.1).*

---

## A9. Languages tool — the languages the platform knows

**What it's for.** This is the **master list of languages** the platform supports (English,
French, …). It is the first step of going multilingual:

1. **Create/enable a language here** (Languages tool).
2. **Turn it on for a site** (Sites tool → Languages, §A6).
3. **Create the page version** in that language (page editor → Languages tab, §A5.4).

So if a language is missing when editing a site or page, it's because it hasn't been added here
yet.

**Where:** left menu → **Languages** tool.

![Languages — list](./images/meliscms-tool-languages-frontoffice-list.png)
![Languages — edit](./images/meliscms-tool-languages-frontoffice-edit.png)
*The languages list and editing a language (its locale, name and status).*

---

## A10. Platform IDs tool — page-id ranges across environments (advanced)

**What it's for.** This is an **administrator setting** that most users never touch. When the
same project runs on several servers — your **dev**, a **staging**, and the **production** site —
content created on each server gets database IDs. Without coordination, two servers could both
create "page 42", and then you could not safely move content between them. The Platform IDs tool
**reserves a distinct range of page IDs per environment**, so IDs never collide and you can
export/import pages between dev → staging → production reliably.

**Why it's obscure:** it's pure plumbing for multi-environment workflows; on a single-server
setup you can largely ignore it. It's set up once by an administrator.

**Where:** left menu → **Platform IDs** tool.

![Platform IDs — list](./images/meliscms-tool-platformids-list.png)
![Platform IDs — edit](./images/meliscms-tool-platformids-edit.png)
*The platform-id ranges and editing one. Each environment gets its own band of IDs.*

---

## A11. Site redirects tool — keep old links working

**What it's for.** When you **rename or remove a page**, its old web address stops working — and
any external link or bookmark to it breaks, hurting visitors and SEO. A **redirect** fixes that:
it tells the site "when someone asks for the **old URL**, send them to the **new URL**" (a 301 =
permanent, or 302 = temporary). The Site redirects tool is where you manage these rules per site.

**When you need it:** any time a public URL changes. Pair it with the page's SEO tab — change the
URL there, then add a redirect from the old one here.

**Where:** left menu → **Site redirects** tool.

![Site redirects — list](./images/meliscms-tool-siteredirect-list.png)
![Site redirects — new](./images/meliscms-tool-siteredirect-new.png)
*The redirects list and creating a new redirect (old URL → new URL, with the redirect type).*

---

## A12. Mini Templates & Plugins — reusable, ready-made content

**What it's for.** A **mini-template** is a **pre-built content block** an editor can drop onto a
page in one click — a styled call-to-action, a standard contact block, a formatted info box… The
idea is to let non-technical editors **reuse approved building blocks** instead of recreating
them every time (and keep the site consistent). This tool is where those blocks are created and
maintained, and where they're **organised into menu categories** so they're easy to find in the
page editor's mini-template manager (§A5.2, step 6).

**Where:** left menu → **Mini Templates & Plugins** tool.

![Mini-templates — list](./images/meliscms-tool-minitemplate-list.png)
*The library of mini-templates (reusable blocks).*

![Mini-templates — edit](./images/meliscms-tool-minitemplate-edit.png)
*Editing a mini-template — its content and settings.*

![Mini-templates — menu manager](./images/meliscms-tool-minitemplate-menu-manager.png)
*The menu manager — group mini-templates into categories so editors find the right block fast in
the page editor.*

---

## A13. Dashboard widget — Pages indicators

On the back-office **Dashboard**, MelisCms can show a **Pages Indicators** widget: how many
sites and pages exist, and how many are published vs not. A quick at-a-glance health check.

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
- **A feature/module isn't showing on my site** → Tools → Sites → open the site → **Module
  loading** and switch it on (§A6).
- **Change a page's web address** → page editor → **SEO** tab → edit the URL, then add a
  **redirect** in Tools → Site redirects from the old URL (§A11).
- **Publish a page** → in the page editor, **Save** then **Publish** (§A5.5).
- **Make a page available in another language** → enable the language (§A9), turn it on for the
  site (§A6 → Languages), then use the page **Languages** tab (§A5.4).
- **Store a setting for the whole site (analytics id, contact email…)** → Tools → Sites → **Site
  config** (§A6).
- **Change a label/wording used across the site** → Tools → Sites → **Translations** (§A6).
- **Reuse an existing page structure** → in the tree, **Duplicate** the page/branch (§A4).
- **Move content between sites/servers** → **Export** a page/tree then **Import** it (§A4).
- **Enable the cookie/GDPR banner** → drag the **GDPR banner** block onto the layout.

---
---

# PART B — Technical Reference

*For developers and AI building inside the platform. Below: how MelisCms actually works, the key
services to call (with examples), and the extension points (events, tabs, plugins).*

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

## B2. Architecture — where MelisCms sits

MelisCms is the **back-office orchestration layer**; it **owns no database tables**. It is one of
three core modules:

- **MelisEngine** owns the CMS data model (pages, sites, templates, languages, SEO, styles) and
  exposes it via `MelisEngineTable*` gateways + `MelisEngine*` services + the cache + the
  `MelisTemplatingPlugin` base class. → [MelisEngine doc](../../../melis-engine/etc/MelisAI/doc/MelisEngine.md)
- **MelisFront** renders pages, and its **`melis` render mode** makes a live page editable inside
  the BO. → [MelisFront doc](../../../melis-front/etc/MelisAI/doc/MelisFront.md)
- **MelisCms** drives the UI and the page lifecycle.

**Load order:** `melis-core` → `melis-front` → `melis-engine` → **`melis-cms`**.

Practical consequence for building: **never query the CMS tables directly** — go through engine
gateways/services. **Never write page-mutation logic inline** — fire/handle the `meliscms_page_*`
events so the whole ecosystem (history, caches, custom fields) stays in sync.

## B3. Reading pages & the tree (engine services, with examples)

The data lives in MelisEngine; you read it through its services. The two you'll use most:

```php
// In a controller (extends MelisCore\Controller\MelisAbstractActionController):
$sm = $this->getServiceManager();

// --- A page (full data: tree + template + SEO + lang + style) ---
/** @var \MelisEngine\Service\MelisPageService $pageSvc */
$pageSvc = $sm->get('MelisEnginePage');
$published = $pageSvc->getDatasPage($idPage);             // default: 'published' (live)
$draft     = $pageSvc->getDatasPage($idPage, 'saved');   // the working draft
// $published->getMelisPage() / ->getMelisPageTree() / ->getMelisPageSeo() … hydrated objects

// --- The page tree (navigation, links, breadcrumb) ---
/** @var \MelisEngine\Service\MelisTreeService $tree */
$tree        = $sm->get('MelisEngineTree');
$children    = $tree->getPageChildren($idPage, 1);        // 1 = published only
$father      = $tree->getPageFather($idPage, 'published');
$breadcrumb  = $tree->getPageBreadcrumb($idPage);
$url         = $tree->getPageLink($idPage, true);         // true = absolute URL
$urlInLocale = $tree->getPageLinkByLocale($idPage, 'fr_FR', true);
```

Other handy engine services: `MelisEngineTemplateService::getTemplate($tplId)`,
`MelisEngineSiteService::getSiteById($siteId)` / `getSiteDataByDomain($domain)`,
`MelisEngineLang` (languages), `MelisEngineSEOService::getSEOById($pageId)`,
`MelisEngineStyle` (page CSS). All are cached.

## B4. Saving pages (the service + why you usually use events instead)

`MelisCmsPageService` (alias `MelisCmsPageService`) writes the page model through engine
gateways. Its main methods:

```php
/** @var \MelisCms\Service\MelisCmsPageService $cmsPage */
$cmsPage = $sm->get('MelisCmsPageService');

// One call that orchestrates tree + published + saved + SEO + lang + style:
$pageId = $cmsPage->savePage($pageTree, $pagePublished, $pageSaved, $pageSeo, $pageLang, $pageStyle, $pageId);

// Or the granular pieces:
$cmsPage->savePageTree($pageId, $parentId, $data, $pageIdInitial, $pageRelation, $langInitialId);
$cmsPage->saveProperties($pageId, $data, $isNew);   // name/type/template/lang/menu/style
$cmsPage->savePageSaved($page, $pageId);            // draft content
$cmsPage->savePagePublished($page, $pageId);        // live content (publish)
$cmsPage->savePageSeo($pageSeo, $pageSeoId);
$cmsPage->savePageLang($pageLang, $pageId);
$cmsPage->savePageStyle($pageStyle, $pageId);
```

> **Important:** in normal operation the BO does **not** call these directly from your code — the
> page editor fires `meliscms_page_save_start`/`publish`/… and the module's listeners (§B6) do the
> orchestration. If you're adding behaviour to the page lifecycle, **hook the events** (§B7)
> rather than calling `savePage` yourself, so history/cache/other modules stay consistent.

## B5. Rights

`MelisCmsRights` (alias `MelisCmsRights`) checks back-office permissions against the user's XML
rights tree:

```php
/** @var \MelisCms\Service\MelisCmsRightsService $rights */
$rights = $sm->get('MelisCmsRights');
if (!$rights->isAccessible($xmlRights, $sectionId, $itemId)) {
    // user not allowed on this tool/section
}
$rights->isActionButtonActive('meliscms_page_action_publish'); // is an action available?
```

The tree view itself is rights-filtered by `MelisCmsGetRightsTreeViewListener` so users only see
the pages/sites they may access.

## B6. Listeners (19) — what actually happens on save/publish/delete

Wired in `Module.php::onBootstrap` (back-office route only), bound to the `MelisCms` event
identifier. The page lifecycle is implemented here:

- `MelisCmsSavePageListener` — on `meliscms_page_save_start`/`publish_start`, orchestrates the
  full save: page tree → properties → edition (the dragged plugins, from session) → SEO → style.
- `MelisCmsPublishPageListener` / `MelisCmsUnpublishPageListener` — rights check, then move the
  page between **saved** and **published**.
- `MelisCmsDeletePageListener` — rights check, reassign default language, delete the tree node and
  its SEO.
- `MelisCmsPageGetterListener` — flags the engine page cache for regeneration after publish.
- `MelisCmsFlashMessengerListener` — success/error toasts on each action.
- `MelisCmsGetRightsTreeViewListener` — filters the tree to the user's rights.
- Plugin-session listeners (`MelisCmsPageEditionSavePluginSessionListener`,
  `MelisCmsPluginSaveEditionSessionListener`, `MelisCmsAddPluginContainerListener`) — track the
  plugins added/removed while editing (stored in session under `content-pages[$idPage]`).
- Domain / platform-id / user listeners and `MelisCmsDeletePluginMenuCachedListener`.

## B7. The page lifecycle events (the platform's main extension point)

These events are the seam the whole ecosystem hooks (news, slider, category2, page-historic,
page-script-editor, and MelisFront's cache invalidation). Fire-and-handle pattern:

| Action | Events |
|---|---|
| Save draft | `meliscms_page_save_start` / `_end` (+ `…savetree_*`, `…saveproperties_*`, `…saveedition_*`, `…saveseo_*`) |
| Publish | `meliscms_page_publish_start` / `_end` |
| Unpublish | `meliscms_page_unpublish_start` / `_end` |
| Delete | `meliscms_page_delete_start` / `_end` (+ `…deleteseo_*`, `…delete_page_*`) |
| Move | `meliscms_page_move_start` / `_end` |
| Duplicate | `meliscms_page_duplicate_start` / `_end` |
| Plugin session | `meliscms_page_savesession_plugin_*`, `meliscms_page_removesession_plugin_*` |

**Example — react to a page being published** (this is exactly how page-historic, the script
editor, etc. plug in):

```php
// In your module's Module.php onBootstrap (or a listener's attach()):
$sharedEvents = $eventManager->getSharedManager();
$sharedEvents->attach(
    'MelisCms',                       // the identifier MelisCms fires under
    'meliscms_page_publish_end',      // the event
    function (\Laminas\EventManager\EventInterface $e) {
        $params = $e->getParams();    // contains the page id, page data…
        $idPage = $params['idPage'] ?? null;
        // your custom code: log it, sync content, clear a cache, notify…
    },
    50                                 // priority
);
```

Two more useful hooks: **`melis_cms_page_tabs_alter`** — add or remove tabs in the page editor
(this is how the *Historic* and *Scripts* tabs are injected); **`modify_page_properties_form_config`**
— alter the Properties form (add fields). Both are how sibling modules extend the editor without
modifying MelisCms.

## B8. Live page editing & preview (reusing MelisFront)

The editor doesn't re-implement rendering — it asks **MelisFront** to render the page in **`melis`
mode** and wraps it with the editing overlay:

- Edit/preview URL: `/<site>/id/<idPage>/renderMode/melis` (the saved draft, editable);
  `…/preview` renders the saved version read-only.
- `FrontPluginsController` / `FrontPluginsModalController` provide the plugins menu and the
  drag-drop modals; `MelisFront\MelisPluginRendererController` re-renders a single plugin after a
  change.
- Every editable block extends engine's `MelisTemplatingPlugin` (`front()` renders on the live
  site, `back()` renders the edit container). To **add a new content block**, create a plugin that
  extends `MelisTemplatingPlugin`, implement `front()`, and register it (this is what the News /
  Slider / Category2 modules do — see their docs for full examples).
- After save/publish, `MelisFrontDeletePluginCacheListener` clears the affected Menu/Breadcrumb
  caches so the public site reflects the change.

## B9. Cache

Pages are cached by **MelisEngine** (`MelisEngineCacheSystem`, filesystem cache namespace
`meliscms_page`). After a publish, MelisCms invalidates the relevant entries so the front
regenerates. If you mutate page data outside the normal flow, clear it yourself:

```php
$cache = $sm->get('MelisEngineCacheSystem');
$cache->deleteCacheByPrefix('page_' . $idPage, 'meliscms_page'); // prefix + cache config name
```

## B10. Controllers (27) & form factories — orientation

- **Page editing**: `PageController` (the lifecycle entry points), `PagePropertiesController`,
  `PageEditionController` (content tab + plugin session), `PageSeoController`,
  `PageLanguagesController`, `PageDuplicationController`, `PageExportController` / `PageImportController`.
- **Sites**: `SitesController` + `Sites{Properties,Domains,Languages,Translation,Config,ModuleLoader}Controller`.
- **Tools**: `TreeSitesController`, `ToolTemplateController`, `ToolStyleController`,
  `LanguageController`, `PlatformController`, `SiteRedirectController`,
  `MiniTemplateManagerController`, `MiniTemplateMenuManagerController`, `GdprBannerController`.
- **Plugin editing**: `FrontPluginsController`, `FrontPluginsModalController`.

Form-element factories populate BO selects from live engine data: `MelisCmsTemplateSelect`,
`MelisCmsStyleSelect`, `MelisCmsLanguageSelect`, `MelisCmsPageLanguagesSelect`,
`MelisCmsPlatformSelect`/`MelisCmsPlatformIDsSelect`, `MelisCmsPluginSiteSelect`,
`MelisCmsPluginSiteModuleSelect`. Dashboard: `MelisCmsPagesIndicatorsPlugin`.

## B11. Services (11) — quick reference

`MelisCmsPageService` (save page model, §B4) · `MelisCmsSiteService` (sites, pages-per-site,
404/home, domains/languages) · `MelisCmsRights` (§B5) · `MelisCmsSiteModuleLoadService` (the
Module-loading tab — load/unload modules per site) · `MelisCmsSitesDomainsService` /
`MelisCmsSitesPropertiesService` · `MelisCmsPageGetterService` (rendered HTML from engine cache) ·
`MelisCmsPageExportService` / `MelisCmsPageImportService` (page tree JSON) ·
`MelisCmsMiniTemplateService` / `MelisCmsMiniTemplateGetterService`.

## B12. Quick code map

```
melis-cms/
├── composer.json                 → deps (core + engine + front), category cms
├── config/
│   ├── module.config.php         → routes, services, controllers, form factories, dashboard plugin
│   ├── app.interface.php         → back-office left menu + tools tree
│   ├── app.tools.php             → DataTable/tool configs
│   └── app.forms.php             → page/site/template/style forms
├── src/
│   ├── Module.php                → bootstrap; wires the 19 listeners (the page lifecycle)
│   ├── Controller/               → 27 controllers (Page*, Sites*, ToolTemplate, ToolStyle, Tree…)
│   ├── Controller/DashboardPlugins/ → MelisCmsPagesIndicatorsPlugin
│   ├── Service/                  → 11 services
│   ├── Listener/                 → 19 listeners
│   └── Form/Factory/             → BO selects
├── view/ · public/ · language/   → BO templates · assets · translations
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
guide for users; Part B = technical reference with examples for developers/AI. Last reviewed 2026-06-08.*
