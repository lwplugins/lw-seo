<?php
/**
 * PHPStan-only bootstrap.
 *
 * The main plugin file defines these constants at runtime with dynamic
 * values (`plugin_dir_path()` / `plugin_dir_url()`), which static analysis
 * cannot resolve. Declaring them here — with representative string values —
 * lets PHPStan type them as strings wherever they are used, without touching
 * runtime code. Not part of the analysed paths; loaded via `bootstrapFiles`.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

define( 'LW_SEO_VERSION', '1.4.0' );
define( 'LW_SEO_FILE', __DIR__ . '/lw-seo.php' );
define( 'LW_SEO_PATH', __DIR__ . '/' );
define( 'LW_SEO_URL', 'https://example.test/wp-content/plugins/lw-seo/' );
