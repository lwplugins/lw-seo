=== LW SEO ===
Contributors: lwplugins
Tags: seo, sitemap, schema, opengraph, breadcrumbs
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.7
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

* XML Sitemap generation
* Schema.org / JSON-LD structured data (Organization/Person)
* robots.txt optimization
* Breadcrumbs with shortcode and PHP function

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
