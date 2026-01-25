=== LW SEO ===
Contributors: lwplugins
Tags: seo, sitemap, schema, opengraph, breadcrumbs
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.5
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight SEO plugin for WordPress - minimal footprint, maximum impact.

== Description ==

LW SEO provides essential SEO features without the bloat. No upsells, no tracking, just clean and efficient SEO optimization.

**Features:**

* Custom meta titles with template variables
* Auto-generated meta descriptions
* Canonical URLs
* Open Graph tags for social sharing
* Twitter Cards support
* XML Sitemap generation
* Schema.org / JSON-LD structured data
* Breadcrumbs with shortcode and PHP function
* robots.txt optimization
* llms.txt for AI crawlers
* Cleanup unnecessary WordPress head tags

**Template Variables:**

* `%%sitename%%` - Site name
* `%%sitedesc%%` - Site tagline
* `%%title%%` - Post/page title
* `%%sep%%` - Separator character
* `%%excerpt%%` - Post excerpt
* `%%author%%` - Author name
* `%%category%%` - Primary category

**Conflict Detection:**

LW SEO automatically disables when detecting Yoast SEO, Rank Math, or All in One SEO.

== Installation ==

1. Upload the `lw-seo` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Go to Settings → LW SEO to configure

Or install via Composer:

`composer require lwplugins/lw-seo`

== Frequently Asked Questions ==

= Does this work with other SEO plugins? =

LW SEO detects Yoast SEO, Rank Math, and All in One SEO and automatically disables its output to prevent conflicts.

= How do I add breadcrumbs? =

Use the shortcode `[lw_breadcrumbs]` in your content or the PHP function `lw_seo_breadcrumbs()` in your theme.

= What is llms.txt? =

The llms.txt file provides information to AI crawlers about your website. See https://llmstxt.org/ for more details.

= How do I flush the sitemap? =

Go to Settings → Permalinks and click Save. This regenerates the rewrite rules.

== Screenshots ==

1. SEO meta box in post editor
2. Settings page - General options
3. Settings page - Social media options
4. Settings page - Sitemap options

== Changelog ==

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
