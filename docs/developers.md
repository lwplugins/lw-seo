# For Developers

Technical documentation for developers extending or integrating with LW SEO.

## Namespace & Autoloading

The plugin uses PSR-4 autoloading:

```php
namespace LightweightPlugins\SEO;

// Classes are in includes/ directory
// LightweightPlugins\SEO\Plugin → includes/Plugin.php
// LightweightPlugins\SEO\Admin\Settings → includes/Admin/Settings.php
```

## Hooks & Filters

### Head Meta Filters

```php
/**
 * The canonical URL LW SEO prints in <head>; og:url follows it.
 *
 * Paginated archives (/page/2/) and paginated posts already get a
 * self-referencing canonical. Return an empty string to print no canonical
 * tag: og:url then keeps the unfiltered URL, and WordPress core's own
 * rel=canonical stays on singular views.
 *
 * @since 1.6.2
 *
 * @param string $url    Canonical URL.
 * @param mixed  $object Queried object (WP_Post, WP_Term, WP_User,
 *                       WP_Post_Type), or null (e.g. a front page listing posts).
 */
$url = apply_filters( 'lw_seo_canonical_url', $url, $object );
```

LW SEO also filters core's `get_canonical_url`, so `wp_get_canonical_url()`
returns a post's custom canonical from the meta box.

### Description Filter (content restriction)

```php
/**
 * A post description, right before LW SEO outputs it.
 *
 * Runs for descriptions typed in the meta box and for generated ones, so a
 * membership / paywall plugin can replace or empty it (return '' to print
 * no description).
 *
 * Contexts:
 *  - 'meta'     <meta name="description"> (also the REST API `description`)
 *  - 'og'       og:description (also the REST API `og.description`)
 *  - 'twitter'  twitter:description (also the REST API `twitter.description`)
 *  - 'schema'   WooCommerce Product JSON-LD `description`
 *  - 'markdown' `excerpt` in the Markdown endpoint (/md) frontmatter
 *  - 'llms'     the post's description in llms.txt and llms-full.txt
 *               (the only context that is not plain text yet: llms.txt
 *               makes it inert Markdown afterwards)
 *
 * @since 1.7.3
 *
 * @param string  $description Description.
 * @param WP_Post $post        The post.
 * @param string  $context     'meta'|'og'|'twitter'|'schema'|'markdown'|'llms'.
 */
$description = apply_filters( 'lw_seo_meta_description', $description, $post, $context );
```

Generated text never comes from the raw `post_content`: LW SEO reads the
excerpt through `get_the_excerpt()`, so a restriction plugin that masks the
excerpt (via the `get_the_excerpt` filter) masks the descriptions and the
`%%excerpt%%` template variable too. A manual excerpt is used as is; an
automatic one is cut to 30 words (50 in the product schema). The Markdown
frontmatter, llms.txt and the product short description in `/md` only use a
manual excerpt, still through `get_the_excerpt()`. A password-protected post
gets no generated description. The Markdown body is built with `the_content`
(restriction plugins that filter it apply); a Bricks page's body is rendered
by Bricks directly, so mask it with `lw_seo_markdown_body` or remove the
post with `lw_seo_post_is_eligible`.

### Sitemap Filters

```php
/**
 * Filter sitemap URLs before output.
 *
 * @since 1.6.0
 *
 * @param array  $items Array of URL entries for this sitemap page.
 * @param string $name  Sitemap name (post type or taxonomy).
 * @param int    $page  Page number.
 */
$items = apply_filters( 'lw_seo_sitemap_urls', $items, $name, $page );

/**
 * Filter post types included in sitemap.
 *
 * @since 1.6.0
 *
 * @param array $post_types Post type names.
 */
$post_types = apply_filters( 'lw_seo_sitemap_post_types', $post_types );

/**
 * Exclude specific post from sitemap.
 *
 * @since 1.6.0
 *
 * @param bool $exclude Whether to exclude.
 * @param int  $post_id Post ID.
 */
$exclude = apply_filters( 'lw_seo_sitemap_exclude_post', false, $post_id );

/**
 * IDs of posts left out of the sitemap. Holds the WooCommerce cart,
 * checkout and my account pages (noindex by WooCommerce) when WooCommerce
 * is active.
 *
 * @since 1.6.2
 *
 * @param int[]  $ids       Post IDs.
 * @param string $post_type Post type of the sitemap being built.
 */
$ids = apply_filters( 'lw_seo_sitemap_excluded_ids', $ids, $post_type );
```

### Content & AI Filters

```php
/**
 * Restrict-only: consulted after a post already passed the built-in
 * eligibility checks (published, no password, indexable type, not
 * noindex). Can remove a post from the sitemap, llms.txt and the
 * Markdown endpoint, but can never add an otherwise-ineligible one.
 *
 * @since 1.6.0
 *
 * @param bool    $eligible Always true when this filter runs.
 * @param WP_Post $post     The post.
 */
$eligible = apply_filters( 'lw_seo_post_is_eligible', true, $post );

/**
 * Post types listed in llms.txt, one section per entry.
 *
 * @since 1.6.0
 *
 * @param array<string, string> $types Post type name => section heading.
 */
$types = apply_filters( 'lw_seo_llms_txt_post_types', $types );

/**
 * The AI crawler registry: key => {name, company, agent, purposes}.
 * `purposes` is a list of 'training' | 'search' | 'user'; the first
 * entry is the crawler's primary purpose for grouping in the admin UI.
 *
 * @since 1.6.0
 *
 * @param array<string, array{name: string, company: string, agent: string, purposes: array<int, string>}> $crawlers Crawlers.
 */
$crawlers = apply_filters( 'lw_seo_ai_crawlers', $crawlers );

/**
 * Content Signals for a post, term, or null (global values). Runs last,
 * after the per-post/term overrides were applied to the global values.
 * $signals holds only the signals that are set: a signal left "Not
 * specified" is absent, so read keys with isset() / ??. Set a key
 * ('search', 'ai-input', 'ai-train') to 'yes' or 'no' to change it, or
 * unset() it to send no value for that signal.
 *
 * @param array<string, string>  $signals Signal key => 'yes'|'no'.
 * @param WP_Post|WP_Term|null   $object  Current object.
 */
$signals = apply_filters( 'lw_seo_content_signals', $signals, $object );
```

The Markdown endpoint filters (`lw_seo_markdown_is_supported`,
`lw_seo_markdown_frontmatter`, `lw_seo_markdown_body`,
`lw_seo_markdown_output`) are documented in
[markdown-endpoint.md](markdown-endpoint.md).
`lw_seo_markdown_frontmatter` and `lw_seo_markdown_body` receive
`WP_Post|WP_Term $object` as their second argument: a `WP_Term` when `/md`
renders a category, tag or other term archive. Don't type-hint it as
`WP_Post`, or the callback throws a `TypeError` on term archives.

Examples:

```php
// Point every filtered/sorted shop URL at the clean shop page.
add_filter( 'lw_seo_canonical_url', function ( string $url, $object ): string {
	return $object instanceof WP_Post_Type && 'product' === $object->name ? strtok( $url, '?' ) : $url;
}, 10, 2 );

// Exclude an internal-only custom post type from the sitemap, llms.txt
// and the Markdown endpoint in one place.
add_filter( 'lw_seo_post_is_eligible', function ( bool $eligible, WP_Post $post ): bool {
	return 'internal_doc' === $post->post_type ? false : $eligible;
}, 10, 2 );

// Keep members-only posts' teaser out of every description.
add_filter( 'lw_seo_meta_description', function ( string $description, WP_Post $post, string $context ): string {
	return has_term( 'members-only', 'category', $post ) ? '' : $description;
}, 10, 3 );

// Add a crawler the built-in registry doesn't know about yet.
add_filter( 'lw_seo_ai_crawlers', function ( array $crawlers ): array {
	$crawlers['examplebot'] = [ 'name' => 'ExampleBot', 'company' => 'Example', 'agent' => 'ExampleBot', 'purposes' => [ 'training' ] ];
	return $crawlers;
} );

// Deny AI training for anything tagged "premium", regardless of the
// per-post setting.
add_filter( 'lw_seo_content_signals', function ( array $signals, WP_Post|WP_Term|null $object ): array {
	if ( $object instanceof WP_Post && has_tag( 'premium', $object ) ) {
		$signals['ai-train'] = 'no';
	}
	return $signals;
}, 10, 2 );

// Drop a post type's section from llms.txt entirely.
add_filter( 'lw_seo_llms_txt_post_types', function ( array $types ): array {
	unset( $types['attachment_gallery'] );
	return $types;
} );
```

## Options API

```php
use LightweightPlugins\SEO\Options;

// Get option with default
$value = Options::get( 'option_name', 'default_value' );

// Get all options
$all_options = Options::get_all();

// Option name constant
$option_key = Options::OPTION_NAME; // 'lw_seo_options'
```

### Available Options

```php
// General
'title_separator'     // string: |, -, >, », ·, —, /
'title_home'          // string: Homepage title template
'title_post'          // string: Post title template
'title_page'          // string: Page title template
'title_archive'       // string: Archive title template
'title_search'        // string: Search title template

// Social
'og_enabled'          // bool: Enable Open Graph
'twitter_enabled'     // bool: Enable Twitter Cards
'twitter_card_type'   // string: summary, summary_large_image
'social_twitter'      // string: Twitter handle
'default_social_image'// int: Attachment ID

// Sitemap
'sitemap_enabled'     // bool: Enable sitemap
'sitemap_posts'       // bool: Include posts
'sitemap_pages'       // bool: Include pages

// Local SEO
'local_enabled'       // bool: Enable LocalBusiness schema
'local_business_type' // string: Schema.org type
'local_business_name' // string: Business name
'local_street'        // string: Street address
'local_city'          // string: City
'local_phone'         // string: Phone number
'local_lat'           // string: Latitude
'local_lng'           // string: Longitude
'local_hours_enabled' // bool: Enable opening hours
'local_hours_{day}_open'   // string: Opening time
'local_hours_{day}_close'  // string: Closing time
'local_hours_{day}_closed' // bool: Closed on this day

// AI/LLM
'llms_txt_enabled'    // bool: Enable llms.txt
'block_gptbot'        // bool: Block GPTBot
'block_claude'        // bool: Block Claude-Web
// ... other crawlers
```

## Post Meta

```php
// Meta key prefix
$prefix = '_lw_seo_';

// Available meta keys
'_lw_seo_title'           // Custom SEO title
'_lw_seo_description'     // Custom meta description
'_lw_seo_canonical'       // Custom canonical URL
'_lw_seo_noindex'         // bool: noindex this post
'_lw_seo_nofollow'        // bool: nofollow this post
'_lw_seo_og_title'        // Custom OG title
'_lw_seo_og_description'  // Custom OG description
'_lw_seo_og_image'        // Custom OG image (attachment ID)
'_lw_seo_exclude_sitemap' // bool: Exclude from sitemap

// Example usage
$custom_title = get_post_meta( $post_id, '_lw_seo_title', true );
```

## PHP Functions

### Breadcrumbs

```php
/**
 * Output breadcrumbs.
 *
 * @param array $args {
 *     @type string $separator  Separator between items. Default '»'.
 *     @type string $home       Home link text. Default 'Home'.
 *     @type bool   $schema     Include schema markup. Default true.
 *     @type bool   $echo       Echo or return. Default true.
 * }
 * @return string|void
 */
lw_seo_breadcrumbs( $args = [] );

// Example
lw_seo_breadcrumbs( [
    'separator' => ' / ',
    'home'      => 'Start',
    'schema'    => true,
] );
```

## Extending the Plugin

### Modify Sitemap Output

```php
add_filter( 'lw_seo_sitemap_urls', function( $urls ) {
    // Add custom URL
    $urls[] = [
        'loc'        => 'https://example.com/custom-page/',
        'lastmod'    => '2024-01-15',
        'changefreq' => 'monthly',
        'priority'   => '0.5',
    ];
    return $urls;
} );
```

## WooCommerce Integration

The plugin automatically detects WooCommerce and adds:

- Product schema markup
- Product Open Graph tags (price, availability)
- Product sitemap entries

```php
// Check if WooCommerce integration is active
if ( class_exists( 'LightweightPlugins\SEO\WooCommerce\Integration' ) ) {
    // WooCommerce features available
}
```

## Conflict Detection

The plugin checks for other SEO plugins:

```php
// Detected plugins that disable LW SEO output
$conflicting_plugins = [
    'wordpress-seo/wp-seo.php',           // Yoast SEO
    'seo-by-rank-math/rank-math.php',     // Rank Math
    'all-in-one-seo-pack/all_in_one_seo_pack.php', // AIOSEO
];
```

## File Structure

```
lw-seo/
├── lw-seo.php              # Main plugin file
├── composer.json           # Composer config
├── includes/
│   ├── Plugin.php          # Main plugin class
│   ├── Options.php         # Options handler
│   ├── Admin/
│   │   ├── Settings.php    # Settings page
│   │   └── Settings/
│   │       ├── TabGeneral.php
│   │       ├── TabContent.php
│   │       └── ...
│   ├── Frontend/
│   │   ├── Meta.php        # Meta tags output
│   │   ├── Schema.php      # JSON-LD output
│   │   └── ...
│   ├── Sitemap/
│   │   └── Generator.php
│   ├── Local/
│   │   ├── Schema.php
│   │   └── Shortcodes.php
│   └── WooCommerce/
│       └── Integration.php
└── assets/
    ├── css/
    └── js/
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Follow WordPress Coding Standards
4. Run `composer phpcs` before committing
5. Submit a pull request

Repository: https://github.com/lwplugins/lw-seo
