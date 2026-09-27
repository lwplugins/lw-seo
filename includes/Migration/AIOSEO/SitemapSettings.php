<?php
/**
 * All in One SEO sitemap settings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\AIOSEO;

use LightweightPlugins\SEO\Migration\Support\OptionWriter;

/**
 * Maps All in One SEO's sitemap settings (sitemap.general.*,
 * app/Common/Options/Options.php:107-124) onto LW SEO's sitemap toggles.
 * "all: true" includes every type, otherwise "included" lists them.
 */
final class SitemapSettings {

	/**
	 * Post type → LW SEO sitemap option.
	 */
	private const POST_TYPES = [
		'post'    => 'sitemap_posts',
		'page'    => 'sitemap_pages',
		'product' => 'sitemap_products',
	];

	/**
	 * Taxonomy → LW SEO sitemap option.
	 */
	private const TAXONOMIES = [
		'category'    => 'sitemap_categories',
		'post_tag'    => 'sitemap_tags',
		'product_cat' => 'sitemap_product_cat',
		'product_tag' => 'sitemap_product_tag',
	];

	/**
	 * Settings reader.
	 *
	 * @var SettingsReader
	 */
	private SettingsReader $settings;

	/**
	 * Constructor.
	 *
	 * @param SettingsReader $settings Settings reader.
	 */
	public function __construct( SettingsReader $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Apply the sitemap settings.
	 *
	 * @param OptionWriter $writer Option writer.
	 * @return void
	 */
	public function apply( OptionWriter $writer ): void {
		$enable = $this->settings->get( 'sitemap.general.enable' );
		if ( null !== $enable ) {
			$writer->set( 'sitemap_enabled', (bool) $enable, 'sitemap.general.enable' );
		}

		$this->group( $writer, 'postTypes', self::POST_TYPES );
		$this->group( $writer, 'taxonomies', self::TAXONOMIES );
	}

	/**
	 * Apply one include group.
	 *
	 * @param OptionWriter          $writer Option writer.
	 * @param string                $group  "postTypes" or "taxonomies".
	 * @param array<string, string> $map    Source type → LW SEO option.
	 * @return void
	 */
	private function group( OptionWriter $writer, string $group, array $map ): void {
		$settings = $this->settings->get( 'sitemap.general.' . $group );
		if ( ! is_array( $settings ) ) {
			return;
		}

		$all      = ! empty( $settings['all'] );
		$included = is_array( $settings['included'] ?? null ) ? $settings['included'] : [];

		foreach ( $map as $type => $lw_key ) {
			$writer->set( $lw_key, $all || in_array( $type, $included, true ), 'sitemap.general.' . $group );
		}
	}
}
