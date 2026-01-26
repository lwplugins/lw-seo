=== LW SEO ===
Contributors: lwplugins
Tags: seo, sitemap, schema, opengraph, breadcrumbs
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.1.5
Requires PHP: 8.1
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

* XML Sitemap generation
* Schema.org / JSON-LD structured data (Organization/Person)
* robots.txt optimization
* Breadcrumbs with shortcode and PHP function
* URL Redirect Manager (301, 302, 307, 410, 451)
* Regex redirect support
* CSV import/export for redirects
* 404 to homepage redirect option

**AI & LLM**

* llms.txt generation for AI crawlers
* Block/allow individual AI crawlers (GPTBot, ChatGPT-User, Claude-Web, Google-Extended, Bytespider, CCBot, PerplexityBot, Cohere-AI)

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

= How do I flush the sitemap? =

Go to **Settings → Permalinks** and click Save. This regenerates the rewrite rules.

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
