=== LW SEO ===
Contributors: lwplugins
Tags: seo, sitemap, schema, opengraph, breadcrumbs
Requires at least: 6.6
Tested up to: 7.1
Stable tag: 1.7.4
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
* Canonical URLs, self-referencing on paginated archives, filterable (lw_seo_canonical_url)
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
* 404 to homepage redirect option (after WordPress's own redirects for renamed slugs and guessed URLs)

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
* `%%title%%` - Post/page title (the archive title on a post type archive, e.g. the shop)
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

= 1.7.4 =
* Change: the LW Plugins overview page is now a searchable table showing each LW plugin's status and version, with one-click activation for installed plugins; it always uses the newest version shipped by any active LW plugin.
* Fix: LW Site Manager's MCP server now lists this plugin's abilities (they were only reachable through REST).

= 1.7.3 =
* Fix: Descriptions and other generated outputs no longer bypass content restriction plugins. The meta, Open Graph and Twitter descriptions (also in the REST API), the product schema description, the %%excerpt%% variable, the Markdown (/md) excerpt and product short description, and the llms.txt descriptions now read the excerpt the standard WordPress way, so a membership or paywall plugin that hides the excerpt hides it there too. Password-protected posts get no generated description.
* New: Developer filter `lw_seo_meta_description` ($description, $post, $context) to change or empty any post description before output, including ones typed in the editor. Contexts: meta, og, twitter, schema, markdown, llms.
* Change: The REST API post description is now the same as the head meta description (the excerpt, cut to 30 words when automatic) instead of the first 160 characters of the content.

= 1.7.2 =
* Fix: "Requires at least" raised to WordPress 6.6 - the React settings screen needs the react-jsx-runtime script core registers from 6.6; on older versions the page stayed blank.

= 1.7.1 =
* Fix: Notices from themes and other plugins (for example a theme's purchase-code or recommended-plugins notice) could show on the LW SEO screen. They are now kept off every LW Plugins screen, whatever their markup.
* Fix: The "settings screen files are missing" notice is no longer hidden by the notice isolation.

= 1.7.0 =
* New: settings screen built with WordPress components: side navigation, a top bar with Save/Discard and a Cmd/Ctrl+S shortcut, loading skeletons and a mobile layout; only changed settings are saved, so saving one tab never resets another.
* New: "LW SEO" panel in the block editor's document sidebar (SEO title and description with counters, noindex/nofollow, social fields, canonical URL, content signals, markdown override), saved with the post through a new lw_seo REST field; the classic meta box stays for the classic editor.
* New: redirect manager rebuilt as a filterable, searchable table with add/edit forms, CSV import/export and inline validation; every redirect gets a stable ID.
* New: admin REST API under lw-seo/v1/admin/ (settings, redirects, migration) for users with manage_options.
* New: Bricks compatibility: while LW SEO renders the head, the Bricks theme's own SEO meta tags are turned off, and its Open Graph tags too when LW SEO's Open Graph is enabled.
* New: Hungarian translation of the whole new interface, including the block editor panel, the term fields and the FAQ block.
* Change: the term edit screen's SEO fields are rendered by the new interface (social and AI sections collapsible) and still save with the core term form.
* Change: settings are no longer registered through the Settings API, so migrators and WP-CLI writing lw_seo_options no longer have unsent settings reset to off.
* Change: the classic settings page, its stylesheet and the redirects/migration AJAX handlers were removed.
* Fix: the per-day opening hours on the Local SEO tab were never saved; they are now stored and appear in the LocalBusiness schema and shortcodes.
* Fix: title templates containing %%date%%, %%category%% or other variables that start like a percent-encoded character were damaged on save.
* Fix: a regex redirect source starting with ^ got a slash in front of it and never matched; regex sources are now stored as written, and patterns saved by earlier versions are repaired when matched, including $1 in the destination.

= 1.6.2 =
* New: Shop title template (title_ptarchive_product, "Shop Title" on the WooCommerce tab); post type archives use a title_ptarchive_{post_type} template when one is set, and %%title%% there is the archive title.
* New: lw_seo_canonical_url filter for the canonical URL LW SEO prints (og:url follows it); returning an empty string prints no canonical tag.
* New: lw_seo_sitemap_excluded_ids filter for post IDs to leave out of the XML sitemap.
* Fix: paginated term and author archives, and a front page listing posts, pointed their canonical and og:url at page 1; /page/2/ and later now point at themselves, like post type archives (shop) and the posts page already did. Paginated posts (<!--nextpage-->) and comment pages take WordPress's own canonical for that page.
* Fix: singular pages carried two rel="canonical" tags, LW SEO's and WordPress core's (disagreeing when a custom canonical was set); core's is now removed whenever LW SEO prints one, and wp_get_canonical_url() returns the custom canonical.
* Fix: a custom SEO title on a post, page, product or the posts page kept WordPress's "- Site Name" suffix; it now replaces the whole title, the same as a custom term title.
* Fix: post type archives, including the WooCommerce shop, got no title template.
* Fix: the WooCommerce Product schema could list reviews without aggregateRating when WooCommerce's cached rating count was stale; the rating is now recounted from the approved rated reviews in that case.
* Fix: the XML sitemap listed the WooCommerce cart, checkout and my account pages, which WooCommerce marks noindex.
* Fix: with "Redirect 404 to homepage" on, a renamed post or product's old URL redirected to the homepage (302) instead of its new URL (301); the homepage redirect now runs after WordPress's own 404 redirects.
* Update: GitHub Actions use actions/checkout v7; PHPStan runs against the WooCommerce 11.1 stubs.

= 1.6.1 =
* Fix: llms-full.txt and the /md Markdown endpoint now include the content of pages built with Bricks; they came out as a URL and a title only, because Bricks keeps a page's content in its own data, not in post_content.
* Fix: every llms-full.txt entry now carries the post's SEO description (or excerpt), so no entry has less than its llms.txt line, also for pages whose text comes from a theme template or custom fields.
* New: robots.txt lists /llms-full.txt next to /llms.txt (as a comment) when it is enabled.

= 1.6.0 =
* New: Sitemap — every public custom post type is included automatically, with a per-type off switch on the Sitemap tab; custom taxonomies are opt-in; tags get their own toggle alongside categories.
* New: Sitemap — post/taxonomy providers are built lazily, so a type registered later on init (ACF, CPT UI, etc.) is still picked up; per-type sitemap URLs accept hyphens and digits (sitemap-case-study.xml, sitemap-top-10.xml).
* New: Sitemap — noindex post types/taxonomies and individually noindexed or password-protected posts are left out; the documented filters lw_seo_sitemap_post_types, lw_seo_sitemap_exclude_post and lw_seo_sitemap_urls are now actually implemented.
* New: llms.txt — rewritten to the llmstxt.org v2 structure: one section per post type (pages in menu order, others newest first), with the post type name appended to a heading when two types share a label or one is literally named "Optional".
* New: llms.txt — admin-configurable summary and free-text introduction, per-section item limit (default 100, max 500), descriptions from the SEO description or excerpt.
* New: llms.txt — optional "Extra links" textarea (Title | URL | optional description per line, always last under "Optional"), optional Markdown links (section links point at /md instead of the HTML URL), opt-in /llms-full.txt (1 MiB cap), and the new lw_seo_llms_txt_post_types filter.
* New: Markdown endpoint — Accept-header negotiation now honours q-values, subdirectory-aware request handling, the front page's Markdown is served at /md/.
* New: Markdown endpoint — llms.txt v2 discovery links (rel="alternate" type="text/markdown", rel="describedby") in <head> and as Link headers; the Markdown response itself sends a rel="canonical" Link header back.
* New: Markdown endpoint — HTML-to-Markdown converter rewritten: Gutenberg images/tables/captions, nested/ordered lists, blockquotes, hr/br, language-tagged and self-sizing code fences, lazy-loaded images; navigation/form noise stripped.
* New: AI crawlers — registry refreshed from vendor documentation, 22 tokens (GPTBot/OAI-SearchBot/ChatGPT-User, ClaudeBot/Claude-SearchBot/Claude-User, Google-Extended, Applebot-Extended, PerplexityBot/Perplexity-User, meta-externalagent/meta-webindexer/meta-externalfetcher, Amazonbot/Amzn-SearchBot/Amzn-User, MistralAI-Training/MistralAI-Index/MistralAI-User, CCBot, AI2Bot, Bytespider), grouped by purpose with "block all training / search / user-triggered" toggles on top of the per-crawler toggle; new lw_seo_ai_crawlers filter.
* New: Content Signals — three-state values (not specified / allow / disallow) and a standard Content-Signal HTTP header, alongside the existing ai-content-signals meta tag.
* New: robots.txt — Content-Signal line and the Content Signals Policy text inside the User-agent: * group whenever a signal is set; live preview and physical-file warning on the Advanced tab and via wp lw-seo robots preview.
* New: Admin UI — notice when another SEO plugin (Yoast SEO, Rank Math, All in One SEO) is active; Sitemap tab per-type/tags/taxonomy toggles; AI/LLM tab restructured into Content Signals, llms.txt and crawler-group blocks.
* New: WP-CLI — wp lw-seo llms preview|flush|info, wp lw-seo robots preview, wp lw-seo crawlers list, extended wp lw-seo sitemap info, and wp lw-seo option set validated the same way the settings form is (typed bool/int, JSON map values with a key.subkey dot-path, choice-list validation); new docs/cli.md.
* New: Internals — Content\PostTypes and Content\Eligibility shared gates (backing the new lw_seo_post_is_eligible restrict-only filter), Admin\SettingsSanitizer extracted with int/map/textarea/choice support, Upgrader for per-version option migrations and rewrite flushes (new lw_seo_version option).
* Fix: Blocking "Claude-Web" did not block Anthropic's crawler; the setting now migrates to ClaudeBot.
* Fix: llms.txt linked the draft Privacy Policy page, listed the static front page twice, included noindex and password-protected posts, and showed HTML entities.
* Fix: Markdown on the HTML URL (Accept negotiation) had no Vary: Accept, so page caches could serve Markdown to browsers; q-values are honoured.
* Fix: Markdown frontmatter was invalid YAML for titles with apostrophes.
* Fix: HTML to Markdown lost Gutenberg images and tables, duplicated nested lists, flattened blockquotes and dropped hr/br.
* Fix: <strong>Note: </strong>text no longer loses the space after the bold text in Markdown (CommonMark ignores emphasis markers with inner whitespace).
* Fix: robots.txt was generated outside the robots_txt filter, dropping other plugins' rules and hard-coding /wp-admin/.
* Fix: Noindex post types and taxonomies were still listed in the sitemap.
* Fix: llms-full.txt generation no longer leaves the global $post changed.
* Fix: robots.txt Content-Signal insertion normalizes CRLF line endings before matching the User-agent: * group.
* Fix: YAML frontmatter stays parseable when a value contains C1 control characters, the U+FFFE/U+FFFF noncharacters, or invalid UTF-8.
* Fix: llms.txt — two post types with the same label, or a type labelled "Optional", no longer lose a section; the post type name is appended to such headings.
* Fix: LW Site Manager set-meta collapsed the Markdown override onto one line; it now uses sanitize_textarea_field, like the meta box, and keeps its newlines.
* Fix: llms.txt cache invalidation is far more precise — rebuilt only for a published post of a listed type (including scheduled posts going live, unpublish, trash), for the plugin's own post meta keys, for a term edit, for a site address/title/permalink change, after a plugin update, and after settings are saved even while llms.txt is off; revisions, autosaves, drafts and unlisted post types no longer clear it.
* Fix: The Markdown endpoint no longer serves noindex content or content with AI Input set to "No"; private posts use the read_post capability and answer 404 instead of 403.
* Fix: Category/tag Markdown honours AI Input = No site-wide or per term; a term's post list only includes posts eligible under the same gate as the sitemap and llms.txt.
* Fix: javascript:, vbscript: and data: URLs are dropped from Markdown link/image/iframe destinations, including entity-encoded, angle-bracket-wrapped and backslash-obfuscated scheme variants.
* Fix: Markdown URLs percent-encode control characters, backtick and backslash; inline code fences are sized to their content, and every point that joins rendered Markdown pieces guards against fusing two backtick fences or leaving a trailing backslash — closing off ways decoded HTML could be read as live markup by a downstream CommonMark renderer.
* Fix: Text nodes, image alt text, and heading/list-item titles (post titles, WooCommerce product attribute labels/values, term names) are Markdown-escaped so post content can no longer inject a live link, emphasis, autolink or raw HTML tag.
* Fix: YAML frontmatter values are \u-escaped for < > [ ] and backtick, so a hostile title, author name, category/tag, excerpt or price can't be read as a heading, link or raw HTML by a CommonMark renderer without a frontmatter extension.
* Fix: llms.txt and llms-full.txt are built as a logged-out visitor (restored afterwards even if building throws), so the shared cached copy no longer contains what the_content, a shortcode or a membership/LMS plugin showed the user — often an admin — who triggered the rebuild.
* Fix: llms.txt escapes Markdown syntax in the site title, summary, section headings, link titles and descriptions, and filters and percent-encodes link URLs through the same scheme filter as the Markdown endpoint.
* Fix: Per-post and per-term content signal values are whitelisted to yes/no; any other value deletes the meta row instead of being stored.
* Fix: The custom Markdown override (post and term) can only be set by users with the unfiltered_html capability — it is served unescaped at /md. Other users see the field read-only and their existing value is preserved unchanged, in the meta box, the term screen, and the LW Site Manager set-meta ability alike. Admins should review overrides saved by other roles before relying on them.
* Fix: LW Site Manager set-meta now requires edit_post / edit_term on the specific target object (not just the generic edit_posts capability) and reports fields it left unchanged as "skipped" in the response.
* Fix: LW Site Manager get-meta, get-content-signals and get-markdown now require the target object to be publicly readable or the caller to have edit_post / edit_term on it — previously any caller with the generic permission could read another user's drafts, private posts, password-protected posts or terms of a non-public taxonomy.
* Change: Content Signals are three-state (not specified / allow / disallow); new installs default to "not specified". Existing settings keep their values (stored booleans migrate to yes/no).
* Change: Sites that never saved the Content Signals settings previously sent ai-train=yes, ai-input=yes, search=yes by default; they now send no signal until one is configured.
* Change: The lw_seo_content_signals filter now receives only the signals that are set; callbacks must read keys with isset() / ??.
* Change: The HTTP header is now Content-Signal; X-Content-Signals is still sent and will be removed in a later release.
* Change: Markdown endpoint — private posts answer 404 (not 403) to users without access; password-protected posts 403; posts whose password was entered via cookie are still never served at /md.
* Change: robots.txt is now built entirely through the core robots_txt filter instead of a separate rewrite rule that bypassed it.
* Change: Docs — docs/developers.md drops 13 hook sections for hooks nothing in includes/ ever fired; docs/settings-sitemap.md, settings-ai.md, settings-advanced.md, markdown-endpoint.md and site-manager-abilities.md rewritten for 1.6.0; new docs/cli.md.
* Change: Translations — lw-seo.pot regenerated (531 strings); Hungarian (hu_HU) updated to 100% (92 new strings).
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

= 1.7.0 =
New settings screen and a block editor "LW SEO" panel. On Bricks sites the theme's own SEO and Open Graph tags are turned off while LW SEO renders the head: move any Bricks page meta descriptions into LW SEO.

= 1.6.2 =
A custom SEO title is now the whole title (no site name appended): add your brand to custom titles where you want it. Paginated archives get a self-referencing canonical. "Redirect 404 to homepage" now lets WordPress redirect renamed slugs first.

= 1.6.0 =
Content Signals now default to "not specified"; robots.txt is filter-only (a physical robots.txt file now overrides it); "Claude-Web" blocking migrates to ClaudeBot; the Markdown override requires unfiltered_html — review overrides saved by other roles.

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
