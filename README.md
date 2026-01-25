# LW SEO

Lightweight SEO plugin for WordPress - minimal footprint, maximum impact.

**Website:** [lwplugins.com](https://lwplugins.com)
**GitHub:** [github.com/lwplugins/lw-seo](https://github.com/lwplugins/lw-seo)

## Features

### Meta Tags & Titles
- **Custom Titles** - Per-post/page title override with template variables
- **Meta Descriptions** - Auto-generated from excerpt or content
- **Canonical URLs** - Prevent duplicate content issues
- **Robots Control** - noindex/nofollow per post

### Social Media
- **Open Graph** - Facebook, LinkedIn sharing optimization
- **Twitter Cards** - Summary and large image cards
- **Custom OG Images** - Per-post social images

### XML Sitemap
- **Auto-generated** - Posts, pages, categories, tags
- **Search Engine Ping** - Automatic sitemap submission
- **Configurable** - Enable/disable per content type

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
- **llms.txt** - AI crawler information file

### Cleanup
- Remove shortlinks
- Remove RSD links
- Remove WLW manifest

## Installation

### Via Composer (recommended)

```bash
composer require lwplugins/lw-seo
```

### Manual Installation

1. Download the latest release
2. Upload to `/wp-content/plugins/lw-seo/`
3. Activate in WordPress admin
4. Go to **Settings → LW SEO**

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

- PHP 8.0+
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
