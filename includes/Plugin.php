<?php
/**
 * Main Plugin class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

use LightweightPlugins\SEO\Admin\SettingsPage;
use LightweightPlugins\SEO\Blocks\FAQ\Block as FAQBlock;
use LightweightPlugins\SEO\Schema\Schema;
use LightweightPlugins\SEO\Sitemap\Sitemap;
use LightweightPlugins\SEO\WooCommerce\WooCommerce;
use LightweightPlugins\SEO\Local\Schema as LocalSchema;
use LightweightPlugins\SEO\Local\Shortcodes as LocalShortcodes;
use LightweightPlugins\SEO\Redirects\Handler as RedirectHandler;
use LightweightPlugins\SEO\Redirects\Ajax as RedirectAjax;
use LightweightPlugins\SEO\Migration\Ajax as MigrationAjax;
use LightweightPlugins\SEO\Migration\CleanupV1314;
use LightweightPlugins\SEO\NotFoundHandler;
use LightweightPlugins\SEO\Markdown\Endpoint as MarkdownEndpoint;
use LightweightPlugins\SEO\Meta\HeadMeta;
use LightweightPlugins\SEO\Meta\TitleFilter;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
		$this->init_components();
	}

	/**
	 * Load required files.
	 *
	 * PSR-4 autoloading handles all class loading via Composer.
	 * Only non-class files need to be required manually.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		// Note: All classes are loaded via PSR-4 autoloading (composer.json).
		// functions.php is loaded via Composer's "files" autoload.
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'init', [ $this, 'load_textdomain' ] );

		( new HeadMeta() )->register();
		( new TitleFilter() )->register();

		// Cleanup hooks.
		if ( Options::get( 'remove_shortlinks' ) ) {
			remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		}

		if ( Options::get( 'remove_rsd' ) ) {
			remove_action( 'wp_head', 'rsd_link' );
		}

		if ( Options::get( 'remove_wlw' ) ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function init_components(): void {
		// Admin components.
		if ( is_admin() ) {
			new MetaBox();
			new TermMetaBox();
			new SettingsPage();
		}

		// Frontend/shared components.
		new Sitemap();
		new Schema();
		new Breadcrumbs();
		new RobotsTxt();
		new LlmsTxt();

		// Content Signals.
		new ContentSignals();

		// Markdown endpoint.
		new MarkdownEndpoint();

		// Keep redirect_canonical away from the virtual endpoints above, and
		// re-flush rewrites (next request) when one of them is toggled.
		new CanonicalGuard();
		new RewriteFlusher();

		// LW Site Manager integration (no-op if Site Manager is not active).
		SiteManager\Integration::init();

		// Gutenberg blocks.
		new FAQBlock();

		// WooCommerce integration (self-checks if WooCommerce is active).
		new WooCommerce();

		// Local SEO.
		new LocalSchema();
		new LocalShortcodes();

		// Redirects.
		new RedirectHandler();
		if ( is_admin() ) {
			new RedirectAjax();
			new MigrationAjax();
		}

		// 404 handler.
		new NotFoundHandler();

		// One-time v1.3.13 og_image cleanup.
		( new CleanupV1314() )->register();

		// REST API for headless support.
		$rest_api = new RestApi();
		$rest_api->init();

		// WP-CLI commands.
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			CLI\Bootstrap::register();
		}
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'lw-seo',
			false,
			dirname( plugin_basename( LW_SEO_FILE ) ) . '/languages'
		);
	}
}
