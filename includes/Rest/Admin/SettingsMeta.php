<?php
/**
 * Read-only context for the settings screen.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Rest\Admin;

use LightweightPlugins\SEO\Admin\Data\BusinessTypes;
use LightweightPlugins\SEO\Content\PostTypes;
use LightweightPlugins\SEO\Crawlers\Registry;
use LightweightPlugins\SEO\Meta\HeadMeta;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\ReplaceVars;
use LightweightPlugins\SEO\RobotsTxt;
use LightweightPlugins\SEO\Sitemap\ProviderRegistry;
use LightweightPlugins\SEO\WooCommerce\PermalinkWatcher;
use LightweightPlugins\SEO\WooCommerce\WooCommerce;

/**
 * Builds the `meta` block of the settings response: choices, URLs and
 * environment facts the settings UI needs but never writes.
 */
final class SettingsMeta {

	/**
	 * Build the meta block. Computed on every request (the robots.txt
	 * preview depends on the just-saved options).
	 *
	 * @return array<string, mixed>
	 */
	public static function build(): array {
		$post_types = PostTypes::post_types();

		return [
			'woo_active'             => WooCommerce::is_active(),
			'conflict_plugin'        => HeadMeta::conflicting_plugin_name(),
			'urls'                   => [
				'sitemap' => home_url( '/sitemap.xml' ),
				'llms'    => home_url( '/llms.txt' ),
				'robots'  => home_url( '/robots.txt' ),
			],
			'separators'             => Options::get_separators(),
			'post_types'             => self::pairs( $post_types ),
			'sitemap_post_types'     => self::pairs( array_diff_key( $post_types, ProviderRegistry::POST_TYPE_TOGGLES ) ),
			'sitemap_taxonomies'     => self::pairs( array_diff_key( PostTypes::taxonomies(), ProviderRegistry::TAXONOMY_TOGGLES ) ),
			'business_types'         => BusinessTypeOptions::build(
				BusinessTypes::get_grouped(),
				BusinessTypes::get_all(),
				(string) Options::get( 'local_business_type', '' ),
				__( 'Other', 'lw-seo' )
			),
			'crawlers'               => self::crawlers(),
			'robots'                 => [
				'physical_file' => RobotsTxt::physical_file(),
				'preview'       => RobotsTxt::preview(),
			],
			'woo_skipped_categories' => array_values( array_map( 'strval', PermalinkWatcher::get_skipped() ) ),
			'variables'              => self::variables(),
		];
	}

	/**
	 * Name => label map as a list of { name, label }.
	 *
	 * @param array<string, string> $items Name => label.
	 * @return array<int, array{name: string, label: string}>
	 */
	private static function pairs( array $items ): array {
		$list = [];
		foreach ( $items as $name => $label ) {
			$list[] = [
				'name'  => (string) $name,
				'label' => (string) $label,
			];
		}
		return $list;
	}

	/**
	 * Built-in crawlers with their first purpose.
	 *
	 * @return array<int, array{key: string, agent: string, company: string, purpose: string}>
	 */
	private static function crawlers(): array {
		$list = [];
		foreach ( Registry::builtin() as $key => $crawler ) {
			$list[] = [
				'key'     => (string) $key,
				'agent'   => $crawler['agent'],
				'company' => $crawler['company'],
				'purpose' => (string) ( $crawler['purposes'][0] ?? '' ),
			];
		}
		return $list;
	}

	/**
	 * Replacement variables keyed by name without the %% delimiters.
	 *
	 * @return array<string, string>
	 */
	private static function variables(): array {
		$variables = [];
		foreach ( ReplaceVars::get_available_variables() as $token => $label ) {
			$variables[ trim( (string) $token, '%' ) ] = $label;
		}
		return $variables;
	}
}
