# Markdown Endpoint - Developer Guide

## Overview

The LW SEO Markdown Endpoint serves any WordPress content as clean
Markdown with YAML frontmatter. It supports posts, pages, taxonomy terms
(including WooCommerce product categories/tags), and WooCommerce products
out of the box, and is fully extensible via hooks.

## Reaching the Markdown Version

**1. URL suffix** — works regardless of the `Accept` header:
```
https://yoursite.com/hello-world/md/
https://yoursite.com/category/news/md/
https://yoursite.com/product/shoes/md/
```
`/markdown/` is an alias for `/md/`.

**2. Query parameter:**
```
https://yoursite.com/?p=123&format=md
```

**3. `Accept` header negotiation** (singular pages only):
```bash
curl -H "Accept: text/markdown" https://yoursite.com/hello-world/
```
The HTML URL answers with Markdown when the request's `Accept` header
prefers `text/markdown` over `text/html`, by q-value: Markdown must have a
q-value above 0 and greater than or equal to `text/html`'s (both default
to `q=1` when omitted). `Accept: text/markdown` alone, or
`Accept: text/markdown, text/html;q=0.5`, negotiates Markdown;
`Accept: text/html, text/markdown;q=0.5` does not.

**Static front page:** when a static page is set as the site's front
page, its Markdown is served at `/md/` (the site root plus the suffix),
the same way any other page's Markdown lives at `{page-url}/md/`.

### Explicit vs. Negotiated Requests

- **Explicit** (`/md`, `/markdown`, `?format=md`): always answers in
  Markdown, including error responses (404/403 bodies are themselves
  Markdown).
- **Negotiated** (`Accept: text/markdown` on the plain HTML URL): falls
  back silently to the normal HTML page whenever Markdown is not
  available (e.g. the request doesn't resolve to a supported object) —
  it never turns a working HTML page into a Markdown 404.

A negotiated response additionally sends:
```
Vary: Accept
Cache-Control: private, no-store
```
(and defines `DONOTCACHEPAGE` for page-cache plugins) so a shared cache
or CDN never serves the Markdown response to a browser, or vice versa,
for the same URL.

## Discovery Links

On every HTML singular page whose Markdown is servable, LW SEO advertises
it per the llms.txt v2 discovery convention, both as `<link>` tags in
`<head>` and as HTTP `Link` header entries:

```html
<link rel="alternate" type="text/markdown" href="https://yoursite.com/hello-world/md/" />
<link rel="describedby" href="https://yoursite.com/llms.txt" />
```
```
Link: <https://yoursite.com/hello-world/md/>; rel="alternate"; type="text/markdown", <https://yoursite.com/llms.txt>; rel="describedby"
```

- `rel="alternate"` (Markdown) is only added when the page is AI-visible
  (see Access Rules below) and points at its `/md` URL.
- `rel="describedby"` (the site's llms.txt) is added whenever llms.txt is
  enabled, independent of the current page's own eligibility.

The Markdown response itself carries the opposite direction: a `Link`
header pointing back at the canonical HTML URL:
```
Link: <https://yoursite.com/hello-world/>; rel="canonical"
```

## Access Rules

| Content | Condition | Status |
|---------|-----------|--------|
| Post/page | Published, no password, viewable post type, not eligible (noindex, `ai_input=no`, or removed by `lw_seo_post_is_eligible`) | **404** |
| Post/page | Not published, not private (draft, pending, future, trash) | **404** |
| Post/page | `private` status, current user lacks `read_post` | **404** (existence not revealed) |
| Post/page | `private` status, current user has `read_post` | 200 |
| Post/page | Password-protected, visitor has no valid cookie | **403** |
| Post/page | Password-protected, even with a valid password cookie | **404** — a password-protected post is never AI-visible, cookie or not |
| Taxonomy term | Non-public taxonomy, term/taxonomy noindex, or `ai-input=no` | **404** |
| Any object | Markdown not supported per the `lw_seo_markdown_is_supported` filter | **404** |

An explicit request (`/md`, `?format=md`) returns the matching status
with a short Markdown error body (`# 404 Not Found` /
`# Password Protected`). A negotiated request (`Accept: text/markdown` on
the HTML URL) falls through to the normal HTML response instead.

## Response Headers

| Header | Sent when | Description |
|--------|-----------|--------------|
| `Content-Type: text/markdown; charset=UTF-8` | Always | MIME type |
| `X-Content-Type-Options: nosniff` | Always | The body is unescaped post content; prevents MIME sniffing as HTML |
| `X-Robots-Tag: noindex` | Always | The Markdown URL itself is never indexed |
| `Link: <canonical>; rel="canonical"` | Always (object has a resolvable permalink) | Points back at the HTML page |
| `Content-Signal: ...` / `X-Content-Signals: ...` | Only signals that are set | Resolved Content Signals for the object — see `docs/settings-ai.md` |
| `X-Markdown-Tokens: 1250` | Always | Approximate token count (`mb_strlen( $output ) / 4`) |
| `Vary: Accept`, `Cache-Control: private, no-store` | Negotiated requests only | Stops the HTML/Markdown response pair from being cache-confused |

## Custom Markdown Override

Each post, page and taxonomy term has a **Custom Markdown** field
(post editor meta box / term edit screen). When filled, it is served
verbatim at `/md` instead of the auto-generated conversion — useful for
page-builder content (Elementor, Divi, etc.) that has no meaningful
`post_content`.

Because this text is served unescaped, only users with the
`unfiltered_html` capability may set or change it (the same capability
WordPress itself requires for raw HTML in `post_content`). For other
users the field is rendered read-only in the admin UI and any existing
value is preserved unchanged. Setting it through the `lw-seo/set-meta`
Site Manager ability applies the same rule — see
`docs/site-manager-abilities.md`.

## Frontmatter Keys

| Content Type | Renderer | Frontmatter keys |
|--------------|----------|-------------------|
| Post/Page | `PostRenderer` | `title`, `url`, `date`, `modified`, `author`, `language`, `categories` (if any), `tags` (if any), `featured_image` (if set), `excerpt` (if any) |
| WC Product | `ProductRenderer` | `title`, `url`, `language`, `price`, `sku`, `stock_status`, `add_to_cart_url`, `categories` (if any), `featured_image` (if set) |
| Taxonomy term | `TaxonomyRenderer` | `title`, `url`, `type`, `post_count`, `language`, `parent` (if the term has one) |

Every value is written as a YAML double-quoted scalar via `Frontmatter`,
including titles with apostrophes/quotes/backslashes and values with
control characters or invalid UTF-8 — these always produce parseable
YAML, and characters that would otherwise let a CommonMark renderer treat
a value as a heading, link or raw HTML (`<`, `>`, `[`, `]`, `` ` ``) are
`\u`-escaped.

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

Add or modify YAML frontmatter fields. The second argument is the `WP_Post` (post, page, product) or the `WP_Term` (category, tag or other term archive) being rendered, so type-hint it as `WP_Post|WP_Term`.

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

Modify or replace the markdown body content. The second argument is the `WP_Post` (post, page, product) or the `WP_Term` (category, tag or other term archive) being rendered, so type-hint it as `WP_Post|WP_Term`.

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

Full output override (escape hatch). Runs after frontmatter and body are
assembled, so it sees the complete document.

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

Custom post types with `public => true` work automatically via
`PostRenderer`. To customize the output:

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

Public custom taxonomies work automatically via `TaxonomyRenderer`. To
customize:

```php
add_filter( 'lw_seo_markdown_frontmatter', function ( array $data, WP_Post|WP_Term $object ): array {
    if ( ! $object instanceof WP_Term || 'location' !== $object->taxonomy ) {
        return $data;
    }

    $data['map_url'] = get_term_meta( $object->term_id, 'map_url', true );
    return $data;
}, 10, 2 );
```

## HTML to Markdown Conversion

The `HtmlToMarkdown` helper (backed by `BlockRenderer` / `InlineRenderer`)
converts `post_content` HTML to Markdown by walking the rendered DOM:

| HTML | Markdown |
|------|----------|
| `<h1>`-`<h6>` | `#`-`######` |
| `<p>` | Double newline |
| `<a>` | `[text](url)` (URL percent-encoded; `javascript:`, `vbscript:` and `data:` schemes are dropped) |
| `<strong>`, `<b>` | `**text**` |
| `<em>`, `<i>` | `*text*` |
| `<ul><li>` | `- item` (nested lists indented, not duplicated) |
| `<ol><li>` | `1. item` |
| `<blockquote>` | `> text` |
| `<code>` | `` `code` `` (fence length sized to the content) |
| `<pre>` | Fenced code block |
| `<img>` | `![alt](src)` |
| `<table>` | Markdown table |
| `<hr>` | `---` |
| `<br>` | Trailing backslash + newline |
| Script/style/nav/form/etc. | Dropped entirely |
| Unsupported | Plain text fallback |

Gutenberg block markup (images, tables, columns) renders through the same
DOM walk as classic content, so blocks are not treated as a special case.
Markdown-significant characters in plain text (post titles, headings,
attribute values) are escaped so post content can't inject links,
emphasis or raw HTML into the output.
