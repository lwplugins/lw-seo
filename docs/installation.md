# Installation

## Requirements

- WordPress 6.0 or higher
- PHP 8.2 or higher

## Installation Methods

### Via WordPress Admin

1. Go to **Plugins → Add New**
2. Search for "LW SEO"
3. Click **Install Now**
4. Click **Activate**

### Via Composer

```bash
composer require lwplugins/lw-seo
```

The plugin will be installed to `wp-content/plugins/lw-seo/`.

### Manual Installation

1. Download the plugin from [GitHub Releases](https://github.com/lwplugins/lw-seo/releases)
2. Upload the `lw-seo` folder to `/wp-content/plugins/`
3. Activate via **Plugins** menu in WordPress admin

## After Installation

1. Navigate to **LW Plugins → SEO** in the admin menu
2. Configure your settings across the available tabs
3. If using permalinks, visit **Settings → Permalinks** and click Save to flush rewrite rules

## Conflict Detection

LW SEO automatically detects and disables its output when the following SEO plugins are active:

- Yoast SEO
- Rank Math
- All in One SEO

This prevents duplicate meta tags and conflicts.

## Uninstallation

1. Deactivate the plugin via **Plugins** menu
2. Delete the plugin
3. Plugin settings are stored in `wp_options` table under `lw_seo_options` and will remain unless manually removed
