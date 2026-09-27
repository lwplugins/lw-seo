<?php
/**
 * SEOPress sitemap settings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

use LightweightPlugins\SEO\Migration\Support\OptionWriter;

/**
 * Maps seopress_xml_sitemap_option_name (src/Services/Options/SitemapOption.php)
 * onto LW SEO's sitemap toggles. A type is included when its list entry has
 * include = '1' (a legacy scalar entry counts as the include value).
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
	 * The seopress_xml_sitemap_option_name option.
	 *
	 * @var array<string, mixed>
	 */
	private array $sitemap;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $sitemap seopress_xml_sitemap_option_name.
	 */
	public function __construct( array $sitemap ) {
		$this->sitemap = $sitemap;
	}

	/**
	 * Apply the sitemap settings.
	 *
	 * @param OptionWriter $writer Option writer.
	 * @return void
	 */
	public function apply( OptionWriter $writer ): void {
		if ( empty( $this->sitemap ) ) {
			return;
		}

		$writer->set( 'sitemap_enabled', '1' === ( $this->sitemap['seopress_xml_sitemap_general_enable'] ?? '' ), 'seopress_xml_sitemap_general_enable' );
		$this->group( $writer, 'seopress_xml_sitemap_post_types_list', self::POST_TYPES );
		$this->group( $writer, 'seopress_xml_sitemap_taxonomies_list', self::TAXONOMIES );
	}

	/**
	 * Apply one include list.
	 *
	 * @param OptionWriter          $writer Option writer.
	 * @param string                $key    List setting key.
	 * @param array<string, string> $map    Source type → LW SEO option.
	 * @return void
	 */
	private function group( OptionWriter $writer, string $key, array $map ): void {
		$list = $this->sitemap[ $key ] ?? null;
		if ( ! is_array( $list ) ) {
			return;
		}

		foreach ( $map as $type => $lw_key ) {
			$entry   = $list[ $type ] ?? null;
			$include = is_array( $entry ) ? ( $entry['include'] ?? '' ) : $entry;
			$writer->set( $lw_key, '1' === (string) $include, $key . '[' . $type . ']' );
		}
	}
}
