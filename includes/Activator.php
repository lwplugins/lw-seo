<?php
/**
 * Plugin activation / deactivation.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

use LightweightPlugins\SEO\Markdown\Endpoint as MarkdownEndpoint;
use LightweightPlugins\SEO\Sitemap\Sitemap;

/**
 * Registers every enabled virtual endpoint's rewrite rules and flushes once.
 *
 * Activation runs after `init`, so the plugin's own `init` callbacks never
 * fired in that request — the rules must be added explicitly before flushing,
 * otherwise /sitemap.xml, /robots.txt and /llms.txt 404 until the next flush.
 */
final class Activator {

	/**
	 * Option key → rewrite provider class, for option-gated endpoints.
	 */
	private const GATED_PROVIDERS = [
		'sitemap_enabled'    => Sitemap::class,
		'robots_txt_enabled' => RobotsTxt::class,
		'llms_txt_enabled'   => LlmsTxt::class,
	];

	/**
	 * Register enabled rewrite rules, then flush.
	 *
	 * @return void
	 */
	public static function activate(): void {
		foreach ( self::GATED_PROVIDERS as $option_key => $provider_class ) {
			if ( Options::get( $option_key ) ) {
				( new $provider_class() )->add_rewrite_rules();
			}
		}

		( new MarkdownEndpoint() )->add_rewrite_rules();

		flush_rewrite_rules();
	}

	/**
	 * Drop the plugin's rewrite rules.
	 *
	 * A flush here would regenerate them — the plugin's `init` callbacks
	 * already registered the rules in this request. Deleting the stored rules
	 * makes the next request rebuild them without this plugin loaded.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		delete_option( 'rewrite_rules' );
	}
}
