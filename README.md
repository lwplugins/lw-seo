# Lightweight SEO

Lightweight SEO plugin for WordPress - minimal footprint, maximum impact.

[![CI](https://github.com/lwplugins/lw-seo/actions/workflows/ci.yml/badge.svg)](https://github.com/lwplugins/lw-seo/actions/workflows/ci.yml)

**Website:** [lwplugins.com](https://lwplugins.com)
**GitHub:** [github.com/lwplugins/lw-seo](https://github.com/lwplugins/lw-seo)

## Features

### Meta Tags & Titles
- **Custom Titles** - Per-post/page title override with template variables
- **Meta Descriptions** - Auto-generated from excerpt or content
- **Title Separator** - Customizable separator character
- **Canonical URLs** - Prevent duplicate content issues
- **Robots Control** - noindex/nofollow per post

### Social Media
- **Open Graph** - Facebook, LinkedIn sharing optimization
- **Twitter Cards** - Summary and large image cards
- **Default Social Image** - Fallback when post has no featured image
- **Custom OG Images** - Per-post social images

### XML Sitemap
- **Auto-generated** - Posts, pages, categories, tags
- **Search Engine Ping** - Automatic sitemap submission
- **Configurable** - Enable/disable per content type
- Available at `yoursite.com/sitemap.xml`

### Schema.org / JSON-LD
- **WebSite Schema** - Site-wide structured data
- **Article Schema** - Blog posts with author info
- **Organization/Person** - Knowledge graph support
- **Breadcrumb Schema** - Navigation markup

### Breadcrumbs
- **Shortcode** - `[lw_breadcrumbs]`
- **PHP Function** - `lw_seo_breadcrumbs()`
- **Microdata** - Built-in structured data

### AI & Crawlers
- **robots.txt** - Auto sitemap reference
- **llms.txt** - AI crawler information file ([llmstxt.org](https://llmstxt.org/))
- **AI Crawler Control** - Block/allow individual crawlers:
  - GPTBot (OpenAI)
  - ChatGPT-User
  - Claude-Web (Anthropic)
  - Google-Extended
  - Bytespider (ByteDance)
  - CCBot (Common Crawl)
  - PerplexityBot
  - Cohere-AI

### WooCommerce Integration
- **Auto-Detection** - Automatically enables when WooCommerce is active
- **Product OpenGraph** - Price, currency, availability, brand, condition
- **Product Schema** - Full Schema.org Product markup with offers
- **Reviews Schema** - Product reviews and aggregate ratings
- **Product Sitemap** - Include products and product categories
- **Custom Settings** - Dedicated WooCommerce SEO settings tab

### Local SEO
- **LocalBusiness Schema** - 100+ business types supported
- **Business Address** - Street, city, state, zip, country
- **Contact Info** - Phone, email with schema markup
- **Opening Hours** - Per-day hours with OpeningHoursSpecification
- **Geo Coordinates** - Latitude/longitude for location
- **Shortcodes** - `[lw_address]`, `[lw_phone]`, `[lw_email]`, `[lw_hours]`, `[lw_map]`

### Cleanup
- Remove shortlinks
- Remove RSD links
- Remove WLW manifest

### Admin Interface
- Unified **LW Plugins** menu
- Modern tabbed settings interface
- WordPress media library integration

## Installation

### Via Composer (recommended)

```bash
composer require lwplugins/lw-seo
```

### Manual Installation

1. Download the latest release
2. Upload to `/wp-content/plugins/lw-seo/`
3. Activate in WordPress admin
4. Go to **LW Plugins → SEO**

## Template Variables

Use these in title templates:

| Variable | Description |
|----------|-------------|
| `%%sitename%%` | Site name |
| `%%sitedesc%%` | Site tagline |
| `%%title%%` | Post/page title |
| `%%sep%%` | Separator character |
| `%%excerpt%%` | Post excerpt |
| `%%author%%` | Author name |
| `%%category%%` | Primary category |
| `%%term_title%%` | Taxonomy term name |
| `%%currentdate%%` | Current date |
| `%%searchphrase%%` | Search query |

## Requirements

- PHP 8.2+
- WordPress 6.0+

## Conflict Detection

LW SEO automatically disables its output when detecting:
- Yoast SEO
- Rank Math
- All in One SEO

## Development

```bash
# Install dependencies
composer install

# Run code sniffer
composer phpcs

# Fix coding standards
composer phpcbf

# Run tests
composer test
```

## License

GPL-2.0-or-later
