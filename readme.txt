=== LW SEO ===
Contributors: lwplugins
Tags: seo, sitemap, schema, opengraph, breadcrumbs
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.6.0
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight SEO plugin for WordPress - minimal footprint, maximum impact.

== Description ==

LW SEO provides essential SEO features without the bloat. No upsells, no tracking, just clean and efficient SEO optimization.

= Features =

**Meta & Titles**

* Custom meta titles with template variables
* Auto-generated meta descriptions
* Customizable title separator
* Canonical URLs
* Per-post/page SEO settings via meta box

**Social Media**

* Open Graph tags for Facebook, LinkedIn, etc.
* Twitter Cards support
* Default social image for posts without featured image
* Custom OG title, description and image per post

**Technical SEO**

* XML Sitemap generation - built-in and custom post types included automatically, custom taxonomies opt-in
* Schema.org / JSON-LD structured data (Organization/Person)
* robots.txt optimization, with a live preview and physical-file conflict warning
* Breadcrumbs with shortcode and PHP function
* URL Redirect Manager (301, 302, 307, 410, 451)
* Regex redirect support
* CSV import/export for redirects
* 404 to homepage redirect option

**AI & LLM**

* llms.txt with a section per content type (pages, posts, custom post types), custom summary/intro, extra links and optional Markdown links; opt-in llms-full.txt
* Markdown endpoint (/md) for every post, page, term and product, with Accept-header negotiation and llms.txt v2 discovery links
* Content Signals (search / AI input / AI training) as a Content-Signal HTTP header, meta tag and robots.txt line
* AI crawler list grouped by purpose (training / search / user-triggered), with per-crawler and per-purpose blocking (GPTBot, ClaudeBot, Claude-SearchBot, Claude-User, OAI-SearchBot, ChatGPT-User, Google-Extended, Applebot-Extended, PerplexityBot, Perplexity-User, Meta, Amazon, Mistral AI, CCBot, AI2Bot, Bytespider)

**Cleanup**

* Remove shortlinks from head
* Remove RSD link
* Remove Windows Live Writer manifest

**Admin**

* Unified "LW Plugins" admin menu
* Modern tabbed settings interface
* WordPress media library integration

= Template Variables =

Use these in your title templates:

* `%%sitename%%` - Site name
* `%%sitedesc%%` - Site tagline
* `%%title%%` - Post/page title
* `%%sep%%` - Separator character
* `%%excerpt%%` - Post excerpt
* `%%author%%` - Author name
* `%%category%%` - Primary category
* `%%term_title%%` - Taxonomy term title
* `%%searchphrase%%` - Search query
* `%%currentdate%%` - Current date

= Conflict Detection =

LW SEO automatically disables its output when detecting Yoast SEO, Rank Math, or All in One SEO to prevent conflicts.

== Installation ==

1. Upload the `lw-seo` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Go to **LW Plugins → SEO** to configure

Or install via Composer:

`composer require lwplugins/lw-seo`

== Frequently Asked Questions ==

= Does this work with other SEO plugins? =

LW SEO detects Yoast SEO, Rank Math, and All in One SEO and automatically disables its output to prevent conflicts.

= How do I add breadcrumbs? =

Use the shortcode `[lw_breadcrumbs]` in your content or the PHP function `lw_seo_breadcrumbs()` in your theme.

= What is llms.txt? =

The llms.txt file provides information to AI crawlers about your website. See https://llmstxt.org/ for more details.

= How do I block AI crawlers like ChatGPT or Claude? =

Go to **LW Plugins → SEO → AI/LLM** tab and enable blocking for the crawlers you want to block. This adds the appropriate rules to your robots.txt.

= How do I set a default social image? =

Go to **LW Plugins → SEO → Social** tab and upload a default image. This will be used for Open Graph and Twitter Cards when a post has no featured image.

= Do I need to flush permalinks for the sitemap to work? =

No. Rewrite rules are flushed automatically on activation, whenever the sitemap is toggled, and on plugin update.

= Where can I find the sitemap? =

Your sitemap is available at `yoursite.com/sitemap.xml`

= Where can I find robots.txt and llms.txt? =

* robots.txt: `yoursite.com/robots.txt`
* llms.txt: `yoursite.com/llms.txt`

== Screenshots ==

1. SEO meta box in post editor
2. Settings page - General tab with title templates
3. Settings page - Social tab with default image
4. Settings page - AI/LLM tab with crawler control
5. Settings page - Sitemap tab
6. Settings page - Advanced tab

== Changelog ==

= 1.6.0 =
* New: Sitemap includes custom post types automatically (per-type toggles on the Sitemap tab); custom taxonomies are opt-in; tags toggle.
* New: llms.txt lists every page and custom post type in its own section, with SEO descriptions, a custom summary and intro, extra links, per-section limits and optional Markdown links.
* New: Opt-in /llms-full.txt with the full Markdown content (1 MB cap).
* New: Markdown and llms.txt discovery: rel="alternate" type="text/markdown" and rel="describedby" links and Link headers (llms.txt v2).
* New: AI crawler list refreshed from vendor documentation (ClaudeBot, Claude-SearchBot, Claude-User, OAI-SearchBot, Applebot-Extended, Perplexity-User, Meta, Amazon, Mistral, AI2) and grouped by purpose, with "block all training / search / user-triggered" toggles.
* New: Content-Signal line and the Content Signals Policy text in robots.txt.
* New: robots.txt preview and physical-file warning on the Advanced tab; notice when another SEO plugin is active.
* New: Filters lw_seo_post_is_eligible, lw_seo_llms_txt_post_types, lw_seo_ai_crawlers, and the previously documented lw_seo_sitemap_post_types, lw_seo_sitemap_exclude_post, lw_seo_sitemap_urls.
* Fix: Blocking "Claude-Web" did not block Anthropic's crawler; the setting now migrates to ClaudeBot.
* Fix: llms.txt linked the draft Privacy Policy page, listed the static front page twice, included noindex and password-protected posts, and showed HTML entities.
* Fix: Markdown on the HTML URL (Accept negotiation) had no Vary: Accept, so page caches could serve Markdown to browsers; q-values are honoured.
* Fix: Markdown frontmatter was invalid YAML for titles with apostrophes.
* Fix: HTML to Markdown lost Gutenberg images and tables, duplicated nested lists, flattened quotes and dropped rules and line breaks.
* Fix: robots.txt was generated outside the robots_txt filter, dropping other plugins' rules and hard-coding /wp-admin/.
* Fix: Noindex post types and taxonomies were still listed in the sitemap.
* Fix: llms-full.txt generation no longer leaves the global $post changed.
* Fix: robots.txt Content-Signal insertion handles CRLF line endings.
* Fix: YAML frontmatter stays parseable when a value contains C1 control characters or invalid UTF-8.
* Fix: <strong>Note: </strong>text no longer loses the space after the bold text in Markdown.
* Fix: llms.txt: two post types with the same label, or a type labelled "Optional", no longer lose a section; the post type name is appended to such headings.
* Fix: llms.txt cache: rebuilt when a listed post is published, unpublished or trashed (including scheduled posts going live), when an LW SEO post field changes, when the site address changes, after a plugin update, and after settings are saved while llms.txt is off; saving revisions, autosaves, drafts and unlisted post types no longer clears it.
* Fix: The Markdown endpoint no longer serves noindex content or content with AI Input set to "No"; private posts use the read_post capability.
* Fix: javascript:, vbscript: and data: URLs are dropped from Markdown output, including entity-encoded, angle-bracket-wrapped and backslash-escaped variants.
* Fix: Per-post and per-term content signal values are whitelisted.
* Fix: llms.txt and llms-full.txt are built as a logged-out visitor, so the shared cached copy no longer contains what the_content, shortcodes or membership plugins showed the user (often an admin) who triggered the rebuild.
* Fix: llms.txt escapes Markdown syntax in the site title, summary, section headings, link titles and descriptions (SEO description or excerpt), and filters and percent-encodes link URLs, so titles and excerpts can no longer inject links or raw HTML.
* Fix: Markdown output escapes Markdown syntax in text, headings/titles, term post lists, product attributes and YAML frontmatter values, so post content can no longer inject links, autolinks or raw HTML for downstream Markdown renderers; link/image URLs are percent-encoded (control characters, backtick, backslash, angle brackets) and inline code fences are sized to their content.
* Fix: Category/tag Markdown honours AI Input = No; a term's post list only includes eligible posts.
* Fix: The custom Markdown override (post and term) can only be set by users with the unfiltered_html capability; for other users the field is read-only and existing overrides are kept. Admins should review overrides saved by other roles before 1.6.0.
* Fix: LW Site Manager abilities: set-meta requires edit_post / edit_term on the target object and reports skipped fields; get-meta, get-content-signals and get-markdown require access to non-public objects (drafts, private, password-protected posts, private taxonomies).
* Change: Content Signals are three-state (not specified / allow / disallow); new installs default to "not specified". Existing settings keep their values.
* Change: Sites that never saved the Content Signals settings (or last saved before they existed) previously sent ai-train=yes, ai-input=yes, search=yes by default; they now send no signal until one is configured.
* Change: The lw_seo_content_signals filter now receives only the signals that are set; callbacks must read keys with isset() / ??.
* Change: The HTTP header is now Content-Signal; X-Content-Signals is still sent and will be removed in a later release.
* Change: Markdown endpoint: private posts answer 404 (not 403) to users without access; password-protected posts 403; posts whose password was entered via cookie are not served at /md either.
* Change: cohere-ai removed from the crawler list (not documented by Cohere).

= 1.5.1 =
* Fix: the release package and Composer dist no longer ship tests, docs or development configuration

= 1.5.0 =
* New: Canonical URL on post type archives (including the WooCommerce shop page) - filter and sort parameters no longer create separate indexable URLs.
* Fix: The blog posts page now gets its own canonical, og:url and title instead of the front page ones.
* New: Paged archives point their canonical at themselves instead of page one.
* Change: Head meta and title handling moved out of the main plugin class into dedicated Meta classes.

= 1.4.2 =
* Fix: Activating the plugin now registers the sitemap, robots.txt and llms.txt rewrite rules before flushing — /sitemap.xml no longer 404s until the next permalink save.
* Fix: /sitemap.xml, /sitemap-*.xml, /llms.txt and /{post}/md are served directly instead of via a 301 to the trailing-slash variant.
* New: Rewrite rules are re-flushed (on the next request) when the sitemap, robots.txt or llms.txt feature is toggled, and dropped on deactivation.
* Update: Tested up to WordPress 7.1.

= 1.4.1 =
* Dev: Added PHPStan level 5 static analysis (`composer analyse`) with WordPress/WooCommerce/WP-CLI stubs and a CI job.
* Fix: Type-safety corrections flagged by static analysis (integer types for WordPress API calls, redundant/dead code removed) — no functional changes.

= 1.4.0 =
* New: WP-CLI commands — `wp lw-seo migrate` (rankmath|yoast), `redirect` (list/add/delete/import/export), `sitemap` (info/flush), `option` (get/set/list/reset).
* New: Yoast SEO importer — options (titles/social/knowledge graph), post & term meta, primary category, and Yoast Premium redirects. Available in the Import tab alongside RankMath, and via `wp lw-seo migrate yoast`.
* New: Shared `Migration\MigratorInterface`; the Import tab and AJAX handler are now provider-aware.
* Security: Markdown endpoint (`/md`) now sends `X-Content-Type-Options: nosniff` so the plain-text Markdown response cannot be MIME-sniffed as HTML.

= 1.3.14 =
* Fix: Fatal TypeError on every frontend singular page after a 1.3.13 RankMath migration — `Plugin::get_og_image(): string` was receiving the `rank_math_og_content_image` cache array (`['check' => md5, 'images' => [...]]`) verbatim. `rank_math_og_content_image` is removed from the migration map (it was never a URL) and all OG-image read paths now coerce array values via `MetaCoerce::as_url()`.
* Fix: One-time cleanup pass scans `_lw_seo_og_image` post/term meta written by 1.3.13 and either normalizes the array to a single URL or deletes the row when no URL can be extracted (idempotent — guarded by `lw_seo_cleanup_v1314_done`).
* Change: RankMath post/term migration now skips non-scalar values for string-typed targets so future cache-shaped meta keys cannot break the frontend.

= 1.3.13 =
* New: WooCommerce slug-only permalinks (RankMath parity) — three options on the WooCommerce tab: remove `/product-category/`, remove parent category slugs, remove `/product/`
* New: Slug collision detector — categories whose root slug would shadow a page, reserved WordPress slug, taxonomy/CPT base, or WooCommerce special page are auto-skipped and reported on the WC settings tab (RankMath has no equivalent check)
* New: RankMath migrator now auto-copies `wc_remove_category_base`, `wc_remove_category_parent_slugs`, `wc_remove_product_base` from `rank-math-options-general` into the new LW SEO options
* Change: The migration "Woo permalink" warning becomes a `warning` instead of an `error` and only fires when LW SEO hasn't yet enabled the corresponding parity flag — once the migrator copies the flag, the warning clears
* Change: Rewrite rules are soft-flushed and the slug-blocker cache invalidated on permalink option change, page CRUD, and product-category CRUD

= 1.3.12 =
* New: RankMath migrator now imports primary terms (`rank_math_primary_category`, `rank_math_primary_product_cat`, `rank_math_primary_product_brand`)
* New: RankMath redirects DB table (`{prefix}rank_math_redirections`) is migrated to the LW SEO Redirects module, including exact/regex/contains/start/end comparison modes
* New: Twitter card overrides (`rank_math_twitter_*`) and `rank_math_og_content_image` are migrated as OpenGraph fallbacks
* New: Migration UI warns about active RankMath WooCommerce permalink rewrites (`wc_remove_category_base`, parent slugs, `wc_remove_product_base`) so users can prepare redirects before disabling RankMath
* New: Migration UI reports `rank_math_schema_*` and other non-migratable keys with honest counts
* Change: Migration result splits "skipped" into "already present" and "no data" so default robots arrays (`["index","follow"]`) no longer inflate the skipped count
* Change: `MetaMigrator` refactored into `PostMetaMigrator`, `TermMetaMigrator`, `UserMetaMigrator`, `PrimaryTermMigrator`, `RedirectsMigrator`, `RobotsMigrator`, `WarningCollector` (atomic classes, easier to extend)

= 1.3.11 =
* Fix: Open Graph and Twitter title on the homepage now uses the Homepage Title setting when a static page is configured as the front page (previously the page title was used)
* Fix: Default Social Image is now used as a fallback for OG/Twitter on the homepage, taxonomy archives, and author archives

= 1.3.10 =
* Fix: Homepage Title setting (`title_home`) was ignored — now applied to the document `<title>` and Open Graph / Twitter titles on the front page and blog page

= 1.3.9 =
* Change: Added missing `'default' => []` to top-level input_schemas of `lw-seo/get-meta`, `lw-seo/get-content-signals`, and `lw-seo/get-markdown` so they can be invoked without arguments

= 1.3.8 =
* New: LW Site Manager integration - SEO abilities for AI agents
* New: lw-seo/get-meta ability - get SEO meta for posts and terms
* New: lw-seo/set-meta ability - set SEO meta for posts and terms
* New: lw-seo/get-content-signals ability - get resolved AI content signals
* New: lw-seo/get-markdown ability - get markdown representation of content
* New: lw-seo/get-options ability - get global SEO settings

= 1.3.7 =
* New: Full SEO settings for taxonomy archives (title, description, noindex, social, AI signals)
* New: Custom markdown content field for posts, pages, products, and taxonomy terms
* New: Markdown endpoint support for all custom taxonomies (product_cat, product_tag, etc.)
* New: Per-term Content Signals override (ai-train, ai-input, search)
* New: Per-term social meta (OG title, OG description, OG image)
* New: Options::get_term_meta() / set_term_meta() for term meta management
* Fix: Markdown endpoint /md URL now works for WooCommerce product categories and custom taxonomies
* Fix: Product add-to-cart URL no longer contains /md/ path in markdown output

= 1.3.6 =
* Fix: Add X-Robots-Tag: noindex to markdown endpoint responses to prevent search indexing

= 1.3.5 =
* New: /markdown/ endpoint alias for /md/

= 1.3.4 =
* Fix: Smarter autoloader fallback - supports root Composer dependency installs

= 1.3.3 =
* Fix: HTML entities in product price markdown output (e.g. &amp;nbsp; &amp;#70;)

= 1.3.2 =
* New: Add to cart link in WooCommerce product markdown output
* Fix: Markdown endpoint now uses add_rewrite_endpoint for correct slug resolution

= 1.3.0 =
* New: Content Signals - AI content usage HTTP headers and meta tags
* New: Markdown endpoint (/md) for AI agent content consumption
* New: Per-post AI content signal override (ai-train, ai-input, search)
* New: Accept: text/markdown content negotiation support
* New: WooCommerce product markdown rendering
* New: Taxonomy/category markdown rendering
* New: Hook system for extending markdown output

= 1.2.6 =
* Fix: Graceful error when autoloader is missing (admin notice instead of fatal error)

= 1.2.5 =
* Fix: WooCommerce products, product categories and product tags now included in XML sitemap

= 1.2.4 =
* Minor fix

= 1.2.3 =
* Hash-based tab navigation on settings page
* Updated ParentPage with SVG icon support from registry

= 1.2.2 =
* Fix admin notice isolation for notices relocated by WordPress core JS

= 1.2.1 =
* Isolate third-party admin notices on LW plugin pages

= 1.2.0 =
* Add fresh POT file and Hungarian (hu_HU) translation

= 1.1.9 =
* New: Central plugin registry from GitHub JSON

= 1.1.8 =
* New: FAQ Gutenberg block with FAQPage schema
* New: LW Memberships and LW LMS in plugin registry
* Fix: Include missing Blocks files in release

= 1.1.7 =
* New: RankMath SEO data migrator (Import tab)
* New: Migrate global options, post meta, term meta, user meta
* New: Dry-run preview before migration
* New: Template variable conversion (%var% → %%var%%)

= 1.1.6 =
* New: REST API for headless WordPress support
* New: `/wp-json/lw-seo/v1/meta/{id}` - Get SEO meta data by post ID
* New: `/wp-json/lw-seo/v1/meta/term/{id}` - Get SEO meta data by term ID
* New: `/wp-json/lw-seo/v1/meta/author/{id}` - Get SEO meta data by author ID
* New: `/wp-json/lw-seo/v1/schema/{id}` - Get Schema.org JSON-LD by post ID
* New: `/wp-json/lw-seo/v1/breadcrumbs/{id}` - Get breadcrumbs by post ID

= 1.1.5 =
* New: Media library picker for Social Image in post meta box
* New: Image preview in Social Image field
* New: Remove button for Social Image
* Enhancement: Priority info text (Social Image → Featured Image → Default Image)

= 1.1.3 =
* Lower minimum PHP version to 8.1

= 1.1.2 =
* Refactor: Unified LW Plugins overview page with centralized plugin registry
* Refactor: Dynamic plugin cards with active/inactive status detection

= 1.1.1 =
* New: 404 settings tab with redirect to homepage option

= 1.1.0 =
* New: Redirect Manager for creating and managing URL redirects
* New: Support for 301, 302, 307, 410, and 451 redirect types
* New: Regex support for advanced redirect patterns
* New: CSV import/export for bulk redirect management
* New: Hit counter and last accessed tracking for redirects

= 1.0.12 =
* Fix: Early translation loading in Local SEO shortcodes (WordPress 6.7+)

= 1.0.11 =
* Fix: Early translation loading warning on WordPress 6.7+

= 1.0.10 =
* Fix: Remove obsolete require_once from main plugin file

= 1.0.9 =
* Refactor: PSR-4 autoloading with PascalCase file/folder names
* Refactor: Composer autoloader now handles all class loading
* Dev: Updated phpcs.xml.dist for PSR-4 compatibility

= 1.0.8 =
* New: Local SEO with LocalBusiness Schema.org markup
* New: Business type selection (100+ Schema.org types)
* New: Address, phone, email settings for structured data
* New: Opening hours with OpeningHoursSpecification schema
* New: Geo coordinates for location data
* New: Shortcodes: [lw_address], [lw_phone], [lw_email], [lw_hours], [lw_map]

= 1.0.7 =
* New: WooCommerce SEO integration (auto-detects WooCommerce)
* New: Product-specific OpenGraph tags (price, availability, brand, condition)
* New: Product Schema.org markup with reviews and offers
* New: WooCommerce settings tab for product SEO configuration
* New: Sitemap settings for products and product taxonomies

= 1.0.6 =
* Fix: Custom title separator now applies to document title

= 1.0.5 =
* New: Default social image setting for posts without featured image
* New: Image upload field with WordPress media library
* Fix: Sitemap tab icon now displays correctly

= 1.0.4 =
* Fix: PHPCS/WPCS coding standards compliance
* Dev: Move template function to separate functions.php
* Dev: Update phpcs.xml.dist configuration

= 1.0.3 =
* New: Unified "LW Plugins" admin menu for all LW plugins
* New: Plugin overview dashboard page
* New: Tabbed settings interface with vertical navigation
* New: AI/LLM section to control AI crawler access (GPTBot, Claude-Web, etc.)
* New: Block/allow individual AI crawlers via robots.txt
* Change: Settings moved from Settings → LW SEO to LW Plugins → SEO
* Dev: Refactored settings page to atomic structure for maintainability

= 1.0.2 =
* Fix: Add rewrite rules for robots.txt to work independently of server config

= 1.0.1 =
* Fix: Remove final keyword from Post_Provider to allow Page_Provider extension

= 1.0.0 =
* Initial release
* Meta titles and descriptions
* Open Graph and Twitter Cards
* XML Sitemap
* Schema.org JSON-LD
* Breadcrumbs
* robots.txt optimization
* llms.txt generation

== Upgrade Notice ==

= 1.1.8 =
FAQ Gutenberg block with FAQPage schema, plugin registry update.

= 1.1.7 =
RankMath SEO data migrator - import options, post meta, term meta with dry-run preview.

= 1.1.6 =
REST API for headless WordPress - get SEO meta, schema, and breadcrumbs via JSON.

= 1.1.3 =
Unified LW Plugins overview page with centralized plugin registry.

= 1.1.1 =
New 404 settings tab with option to redirect all 404 errors to homepage.

= 1.1.0 =
New Redirect Manager feature with support for 301, 302, 307, 410, 451 redirects.

= 1.0.12 =
Fix for early translation loading in Local SEO shortcodes.

= 1.0.11 =
Fix for WordPress 6.7+ translation warning.

= 1.0.10 =
Hotfix for PSR-4 autoloading.

= 1.0.9 =
PSR-4 autoloading refactor for better code organization.

= 1.0.8 =
Local SEO with LocalBusiness schema, opening hours, and address shortcodes.

= 1.0.7 =
WooCommerce SEO integration with product OpenGraph and Schema.org markup.

= 1.0.6 =
Title separator setting now works correctly.

= 1.0.5 =
Set a default social image for posts without featured images.

= 1.0.4 =
Code quality improvements and WPCS compliance.

= 1.0.3 =
New tabbed settings UI, AI crawler control, and unified LW Plugins menu.

= 1.0.2 =
robots.txt now works on all server configurations.

= 1.0.1 =
Bug fix for sitemap page provider.

= 1.0.0 =
Initial release.
