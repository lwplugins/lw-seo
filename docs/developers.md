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

### Title Filters

```php
/**
 * Filter the generated title.
 *
 * @param string $title The generated title.
 * @param int    $post_id Post ID (0 for non-singular pages).
 */
$title = apply_filters( 'lw_seo_title', $title, $post_id );

/**
 * Filter the title separator.
 *
 * @param string $separator The separator character.
 */
$separator = apply_filters( 'lw_seo_title_separator', $separator );
```

### Meta Description Filters

```php
/**
 * Filter the meta description.
 *
 * @param string $description The meta description.
 * @param int    $post_id Post ID.
 */
$description = apply_filters( 'lw_seo_meta_description', $description, $post_id );

/**
 * Filter auto-generated description length.
 *
 * @param int $length Maximum characters.
 */
$length = apply_filters( 'lw_seo_description_length', 155 );
```

### Open Graph Filters

```php
/**
 * Filter Open Graph meta tags.
 *
 * @param array $og_tags Array of OG tags.
 * @param int   $post_id Post ID.
 */
$og_tags = apply_filters( 'lw_seo_og_tags', $og_tags, $post_id );

/**
 * Filter the default social image URL.
 *
 * @param string $image_url Image URL.
 */
$image_url = apply_filters( 'lw_seo_default_social_image', $image_url );
```

### Schema Filters

```php
/**
 * Filter JSON-LD schema output.
 *
 * @param array $schema Schema data array.
 * @param string $type Schema type (Organization, LocalBusiness, etc).
 */
$schema = apply_filters( 'lw_seo_schema', $schema, $type );

/**
 * Filter LocalBusiness schema.
 *
 * @param array $schema LocalBusiness schema array.
 */
$schema = apply_filters( 'lw_seo_local_schema', $schema );
```

### Sitemap Filters

```php
/**
 * Filter sitemap URLs before output.
 *
 * @param array $urls Array of URL entries.
 */
$urls = apply_filters( 'lw_seo_sitemap_urls', $urls );

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

### Breadcrumb Filters

```php
/**
 * Filter breadcrumb items.
 *
 * @param array $items Breadcrumb items array.
 */
$items = apply_filters( 'lw_seo_breadcrumb_items', $items );

/**
 * Filter breadcrumb separator.
 *
 * @param string $separator Separator HTML.
 */
$separator = apply_filters( 'lw_seo_breadcrumb_separator', '»' );
```

### Action Hooks

```php
/**
 * Fires before SEO meta tags are output.
 */
do_action( 'lw_seo_before_meta' );

/**
 * Fires after SEO meta tags are output.
 */
do_action( 'lw_seo_after_meta' );

/**
 * Fires when plugin settings are saved.
 *
 * @param array $old_options Previous options.
 * @param array $new_options New options.
 */
do_action( 'lw_seo_settings_saved', $old_options, $new_options );
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
'title_separator'     // string: |, -, –, —, •, », /
'title_home'          // string: Homepage title template
'title_post'          // string: Post title template
'title_page'          // string: Page title template
'title_archive'       // string: Archive title template
'title_search'        // string: Search title template

// Social
'og_enabled'          // bool: Enable Open Graph
'twitter_enabled'     // bool: Enable Twitter Cards
'twitter_card_type'   // string: summary, summary_large_image
'twitter_username'    // string: Twitter handle
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
 *     @type string $home_text  Home link text. Default 'Home'.
 *     @type bool   $schema     Include schema markup. Default true.
 *     @type bool   $echo       Echo or return. Default true.
 * }
 * @return string|void
 */
lw_seo_breadcrumbs( $args = [] );

// Example
lw_seo_breadcrumbs( [
    'separator' => ' / ',
    'home_text' => 'Start',
    'schema'    => true,
] );
```

## Extending the Plugin

### Add Custom Schema Type

```php
add_filter( 'lw_seo_schema', function( $schema, $type ) {
    if ( is_singular( 'product' ) ) {
        $schema['@type'] = 'Product';
        $schema['name'] = get_the_title();
        $schema['description'] = get_the_excerpt();
        // Add more product properties...
    }
    return $schema;
}, 10, 2 );
```

### Add Custom Template Variable

```php
add_filter( 'lw_seo_title', function( $title, $post_id ) {
    // Replace custom variable
    if ( strpos( $title, '%%custom%%' ) !== false ) {
        $custom_value = 'My Custom Value';
        $title = str_replace( '%%custom%%', $custom_value, $title );
    }
    return $title;
}, 10, 2 );
```

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

### Conditionally Disable Output

```php
add_filter( 'lw_seo_title', function( $title, $post_id ) {
    // Disable on specific pages
    if ( $post_id === 42 ) {
        return ''; // Return empty to use default WP title
    }
    return $title;
}, 10, 2 );
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
