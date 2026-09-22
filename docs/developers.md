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
 * @param string $url    Canonical URL.
 * @param mixed  $object Queried object (WP_Post, WP_Term, WP_User,
 *                       WP_Post_Type), or null (e.g. a front page listing posts).
 */
$url = apply_filters( 'lw_seo_canonical_url', $url, $object );
```

LW SEO also filters core's `get_canonical_url`, so `wp_get_canonical_url()`
returns a post's custom canonical from the meta box.

### Sitemap Filters

```php
/**
 * Filter sitemap URLs before output.
 *
 * @param array  $items Array of URL entries for this sitemap page.
 * @param string $name  Sitemap name (post type or taxonomy).
 * @param int    $page  Page number.
 */
$items = apply_filters( 'lw_seo_sitemap_urls', $items, $name, $page );

/**
 * Filter post types included in sitemap.
 *
 * @param array $post_types Post type names.
 */
$post_types = apply_filters( 'lw_seo_sitemap_post_types', $post_types );

/**
 * Exclude specific post from sitemap.
 *
 * @param bool $exclude Whether to exclude.
 * @param int  $post_id Post ID.
 */
$exclude = apply_filters( 'lw_seo_sitemap_exclude_post', false, $post_id );
```

### Content & AI Filters

```php
/**
 * Restrict-only: consulted after a post already passed the built-in
 * eligibility checks (published, no password, indexable type, not
 * noindex). Can remove a post from the sitemap, llms.txt and the
 * Markdown endpoint, but can never add an otherwise-ineligible one.
 *
 * @param bool    $eligible Always true when this filter runs.
 * @param WP_Post $post     The post.
 */
$eligible = apply_filters( 'lw_seo_post_is_eligible', true, $post );

/**
 * Post types listed in llms.txt, one section per entry.
 *
 * @param array<string, string> $types Post type name => section heading.
 */
$types = apply_filters( 'lw_seo_llms_txt_post_types', $types );

/**
 * The AI crawler registry: key => {name, company, agent, purposes}.
 * `purposes` is a list of 'training' | 'search' | 'user'; the first
 * entry is the crawler's primary purpose for grouping in the admin UI.
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
