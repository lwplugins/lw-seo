<?php
/**
 * Main Plugin class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

use LightweightPlugins\SEO\Admin\Hub\Hub;
use LightweightPlugins\SEO\Admin\SettingsPage;
use LightweightPlugins\SEO\Editor\BlockEditorAssets;
use LightweightPlugins\SEO\Editor\PostRestField;
use LightweightPlugins\SEO\Rest\Admin\Routes as AdminRoutes;
use LightweightPlugins\SEO\Blocks\FAQ\Block as FAQBlock;
use LightweightPlugins\SEO\Blocks\HtmlSitemap\Block as HtmlSitemapBlock;
use LightweightPlugins\SEO\Schema\Schema;
use LightweightPlugins\SEO\Sitemap\Sitemap;
use LightweightPlugins\SEO\Local\Schema as LocalSchema;
use LightweightPlugins\SEO\Local\Shortcodes as LocalShortcodes;
use LightweightPlugins\SEO\Redirects\Handler as RedirectHandler;
use LightweightPlugins\SEO\Migration\CleanupV1314;
use LightweightPlugins\SEO\NotFoundHandler;
use LightweightPlugins\SEO\NotFound\Logger as NotFoundLogger;
use LightweightPlugins\SEO\NotFound\LogRepository as NotFoundLogRepository;
use LightweightPlugins\SEO\NotFound\Pruner as NotFoundPruner;
use LightweightPlugins\SEO\HtmlSitemap\Shortcode as HtmlSitemapShortcode;
use LightweightPlugins\SEO\IndexNow\Key as IndexNowKey;
use LightweightPlugins\SEO\IndexNow\KeyFile as IndexNowKeyFile;
use LightweightPlugins\SEO\IndexNow\Queue as IndexNowQueue;
use LightweightPlugins\SEO\IndexNow\Watcher as IndexNowWatcher;
use LightweightPlugins\SEO\LlmsTxt\Endpoint as LlmsTxtEndpoint;
use LightweightPlugins\SEO\Markdown\Endpoint as MarkdownEndpoint;
use LightweightPlugins\SEO\Markdown\Discovery as MarkdownDiscovery;
use LightweightPlugins\SEO\Images\ImageSeo;
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
		Hub::init( LW_SEO_FILE );
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

		( new Upgrader() )->register();

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
		// Integrations (Bricks, Polylang, WooCommerce, LW Site Manager): each
		// is registered only when its theme or plugin is present.
		( new Integrations\Loader() )->register();

		// Admin components.
		if ( is_admin() ) {
			new MetaBox();
			new TermMetaBox();
			new SettingsPage();
			( new BlockEditorAssets() )->register();
		}

		// Admin REST API (React settings screen) and the block editor field.
		( new AdminRoutes() )->register();
		( new PostRestField() )->register();

		// Frontend/shared components.
		new Sitemap();
		new Schema();
		new Breadcrumbs();
		new RobotsTxt();
		new LlmsTxtEndpoint();
		$this->init_crawl_tools();
		new ContentSignals();

		// Markdown endpoint.
		new MarkdownEndpoint();
		new MarkdownDiscovery();

		// Keep redirect_canonical away from the virtual endpoints above, and
		// re-flush rewrites (next request) when one of them is toggled.
		new CanonicalGuard();
		new RewriteFlusher();

		// Gutenberg blocks.
		new FAQBlock();

		// Local SEO.
		new LocalSchema();
		new LocalShortcodes();

		// Redirects.
		new RedirectHandler();

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
	 * Crawl and discovery tools: image alt/title attributes, IndexNow
	 * notifications, the HTML sitemap (block and shortcode) and the 404 log.
	 *
	 * @return void
	 */
	private function init_crawl_tools(): void {
		( new ImageSeo() )->register();

		// IndexNow (opt-in): the flush handler is always hooked so a stale queue is dropped.
		IndexNowQueue::register();
		if ( Options::get( 'indexnow_enabled' ) ) {
			IndexNowKey::ensure();
		}
		new IndexNowKeyFile();
		new IndexNowWatcher();

		new HtmlSitemapBlock();
		new HtmlSitemapShortcode();

		// 404 log (opt-in) and its daily pruning.
		$not_found_log = new NotFoundLogRepository();
		( new NotFoundLogger( $not_found_log ) )->register();
		( new NotFoundPruner( $not_found_log ) )->register();
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
