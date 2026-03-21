# Markdown Endpoint - Developer Guide

## Overview

The LW SEO Markdown Endpoint serves any WordPress content as clean markdown with YAML frontmatter. It supports posts, pages, taxonomies, and WooCommerce products out of the box, and is fully extensible via hooks.

## Hook Reference

### `lw_seo_markdown_is_supported`

Control whether a request should receive markdown output.

```php
add_filter( 'lw_seo_markdown_is_supported', function ( bool $supported, WP_Query $query ): bool {
    // Disable markdown for a specific post type.
    if ( $query->is_singular( 'secret_docs' ) ) {
        return false;
    }
    return $supported;
}, 10, 2 );
```

### `lw_seo_markdown_frontmatter`

Add or modify YAML frontmatter fields.

```php
add_filter( 'lw_seo_markdown_frontmatter', function ( array $data, WP_Post|WP_Term $object ): array {
    // Add custom field to product frontmatter.
    if ( $object instanceof WP_Post && 'product' === $object->post_type ) {
        $data['brand'] = get_post_meta( $object->ID, 'brand', true );
    }
    return $data;
}, 10, 2 );
```

### `lw_seo_markdown_body`

Modify or replace the markdown body content.

```php
add_filter( 'lw_seo_markdown_body', function ( string $body, WP_Post|WP_Term $object ): string {
    // Append related posts to the body.
    if ( $object instanceof WP_Post ) {
        $body .= "\n## Related\n\n";
        // ... add related post links ...
    }
    return $body;
}, 10, 2 );
```

### `lw_seo_content_signals`

Override Content Signals for any content type.

```php
add_filter( 'lw_seo_content_signals', function ( array $signals, WP_Post|WP_Term|null $object ): array {
    // Disable AI training for premium posts.
    if ( $object instanceof WP_Post && has_tag( 'premium', $object ) ) {
        $signals['ai-train'] = 'no';
    }
    return $signals;
}, 10, 2 );
```

### `lw_seo_markdown_output`

Full output override (escape hatch).

```php
add_filter( 'lw_seo_markdown_output', function ( string $output, WP_Post|WP_Term $object ): string {
    // Completely replace output for a custom post type.
    if ( $object instanceof WP_Post && 'event' === $object->post_type ) {
        return "---\ntitle: ...\n---\n\nCustom event markdown...";
    }
    return $output;
}, 10, 2 );
```

## Adding Support for Custom Post Types

Custom post types with `public => true` work automatically via `PostRenderer`. To customize the output:

```php
// Add custom frontmatter for 'event' post type.
add_filter( 'lw_seo_markdown_frontmatter', function ( array $data, WP_Post|WP_Term $object ): array {
    if ( ! $object instanceof WP_Post || 'event' !== $object->post_type ) {
        return $data;
    }

    $data['event_date'] = get_post_meta( $object->ID, 'event_date', true );
    $data['location']   = get_post_meta( $object->ID, 'event_location', true );
    $data['ticket_url'] = get_post_meta( $object->ID, 'ticket_url', true );

    return $data;
}, 10, 2 );
```

## Adding Support for Custom Taxonomies

Public custom taxonomies work automatically via `TaxonomyRenderer`. To customize:

```php
add_filter( 'lw_seo_markdown_frontmatter', function ( array $data, WP_Post|WP_Term $object ): array {
    if ( ! $object instanceof WP_Term || 'location' !== $object->taxonomy ) {
        return $data;
    }

    $data['map_url'] = get_term_meta( $object->term_id, 'map_url', true );
    return $data;
}, 10, 2 );
```

## Renderers

| Content Type | Renderer | Frontmatter |
|-------------|----------|-------------|
| Post/Page | `PostRenderer` | title, url, date, modified, author, language, categories, tags, featured_image, excerpt |
| WC Product | `ProductRenderer` | title, url, language, price, sku, stock_status, categories, featured_image |
| Taxonomy | `TaxonomyRenderer` | title, url, type, post_count, language, parent |

## Response Headers

| Header | Description |
|--------|-------------|
| `Content-Type: text/markdown; charset=UTF-8` | MIME type |
| `X-Content-Signals: ai-train=yes, ai-input=yes, search=yes` | Content permission signals |
| `X-Markdown-Tokens: 1250` | Approximate token count (mb_strlen / 4) |

## HTML to Markdown Conversion

The `HtmlToMarkdown` helper converts `post_content` HTML to markdown:

| HTML | Markdown |
|------|----------|
| `<h1>`-`<h6>` | `#`-`######` |
| `<p>` | Double newline |
| `<a>` | `[text](url)` |
| `<strong>`, `<b>` | `**text**` |
| `<em>`, `<i>` | `*text*` |
| `<ul><li>` | `- item` |
| `<ol><li>` | `1. item` |
| `<blockquote>` | `> text` |
| `<code>` | `` `code` `` |
| `<pre>` | Fenced code block |
| `<img>` | `![alt](src)` |
| `<table>` | Markdown table |
| Unsupported | Plain text fallback |
