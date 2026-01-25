# Sitemap Settings

Navigate to **LW Plugins → SEO → Sitemap** to configure XML sitemap generation.

## Enable Sitemap

Toggle XML sitemap generation on/off.

When enabled, your sitemap is available at:
```
https://yoursite.com/sitemap.xml
```

## Sitemap URL

After enabling, flush rewrite rules to activate:

1. Go to **Settings → Permalinks**
2. Click **Save Changes** (no changes needed)

## Include Content Types

Select which content types to include in your sitemap:

### Posts
Include blog posts. Enabled by default.

### Pages
Include static pages. Enabled by default.

### Custom Post Types

If you have custom post types (e.g., products, portfolio), they appear here. Enable as needed.

## Include Taxonomies

Select which taxonomies to include:

### Categories
Include category archive pages.

### Tags
Include tag archive pages.

### Custom Taxonomies
Additional taxonomies appear here if registered.

## Exclude Specific Content

### Exclude Posts/Pages

Exclude individual items from the sitemap:

1. Edit the post/page
2. In the **LW SEO** meta box, check "Exclude from Sitemap"
3. Save

### Exclude by ID

Enter comma-separated post IDs to exclude:
```
42, 156, 789
```

## WooCommerce Integration

When WooCommerce is active, additional options appear:

| Option | Description |
|--------|-------------|
| Products | Include product pages |
| Product Categories | Include product category archives |
| Product Tags | Include product tag archives |

## Sitemap Structure

The generated sitemap follows the [sitemaps.org](https://www.sitemaps.org/) protocol:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://yoursite.com/</loc>
    <lastmod>2024-01-15T10:30:00+00:00</lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://yoursite.com/sample-post/</loc>
    <lastmod>2024-01-14T15:20:00+00:00</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
</urlset>
```

## Submitting to Search Engines

Submit your sitemap to search engines for faster indexing:

### Google Search Console
1. Go to [Search Console](https://search.google.com/search-console)
2. Select your property
3. Navigate to **Sitemaps**
4. Enter `sitemap.xml` and click Submit

### Bing Webmaster Tools
1. Go to [Bing Webmaster Tools](https://www.bing.com/webmasters)
2. Select your site
3. Navigate to **Sitemaps**
4. Submit your sitemap URL

## Tips

- Keep sitemaps under 50,000 URLs
- Exclude thin content and duplicate pages
- Update sitemap after major content changes
- Monitor indexing status in Search Console
