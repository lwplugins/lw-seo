# Sitemap Settings

Navigate to **LW Plugins → SEO → Sitemap** to configure XML sitemap generation.

## Enable Sitemap

Toggle XML sitemap generation on/off.

When enabled, your sitemap is available at:
```
https://yoursite.com/sitemap.xml
```

Rewrite rules are flushed automatically — on plugin activation, whenever
the Sitemap toggle changes, and on plugin update. You never need to visit
**Settings → Permalinks** for the sitemap to start working.

## Include Content Types

### Posts
Include blog posts. Enabled by default.

### Pages
Include static pages. Enabled by default.

### Custom Post Types

Every public custom post type (registered by a theme, a plugin like ACF,
or WooCommerce) is listed automatically and included by default. Untick a
type to leave it out of the sitemap. New custom post types registered
later are included automatically without any settings change.

## Include Taxonomies

### Categories
Include category archive pages.

### Tags
Include tag archive pages.

### Custom Taxonomies

Public custom taxonomies are listed here but are **opt-in**: tick a
taxonomy to add its terms to the sitemap. A taxonomy left unticked is not
included.

## What Is Always Excluded

Regardless of the toggles above, the following never appear in the
sitemap:

- Content of a post type or taxonomy marked **noindex** on the Content
  tab.
- An individual post or term marked **noindex** on its own edit screen.
- Password-protected posts.
- Non-viewable post types and non-public taxonomies.
- The WooCommerce cart, checkout and my account pages, which WooCommerce
  marks noindex itself (developers can add more IDs with the
  `lw_seo_sitemap_excluded_ids` filter).

## Excluding an Individual Post

There is no per-post "Exclude from Sitemap" checkbox. To remove one post
from the sitemap without affecting its indexability, use the
`lw_seo_sitemap_exclude_post` filter in a small must-use plugin or your
theme's `functions.php`:

```php
add_filter( 'lw_seo_sitemap_exclude_post', function ( bool $exclude, int $post_id ): bool {
	return 42 === $post_id ? true : $exclude;
}, 10, 2 );
```

See `docs/developers.md` for the full filter reference, including
`lw_seo_sitemap_post_types` and `lw_seo_sitemap_urls`.

## WooCommerce Integration

When WooCommerce is active and the WooCommerce integration is enabled,
additional toggles appear for products, product categories and product
tags, alongside the other post types and taxonomies above.

## Sitemap Structure

The plugin generates a sitemap index at `/sitemap.xml`, linking one
`/sitemap-{name}.xml` file per included post type or taxonomy (paginated
as `/sitemap-{name}-2.xml`, etc. past 1,000 items). Each file follows the
[sitemaps.org](https://www.sitemaps.org/) protocol:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://yoursite.com/sample-post/</loc>
    <lastmod>2024-01-14T15:20:00+00:00</lastmod>
    <changefreq>weekly</changefreq>
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
- Exclude thin content and duplicate pages via noindex, not by hiding
  it from the sitemap alone
- Update sitemap after major content changes
- Monitor indexing status in Search Console
