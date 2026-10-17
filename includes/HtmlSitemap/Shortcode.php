<?php
/**
 * HTML sitemap shortcode.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\HtmlSitemap;

use LightweightPlugins\SEO\Content\PostTypes;
use LightweightPlugins\SEO\Options;

/**
 * [lw_html_sitemap post_types="page,post" taxonomies="category" max="500" order="title|date"]
 *
 * Attributes override the settings. The markup is cached; the cache is
 * dropped whenever the content it lists may have changed.
 */
final class Shortcode {

	/**
	 * Shortcode tag.
	 */
	public const TAG = 'lw_html_sitemap';

	/**
	 * Allowed orders.
	 */
	private const ORDERS = [ 'title', 'date' ];

	/**
	 * Upper bound for the items per section.
	 */
	private const MAX_ITEMS = 5000;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( self::TAG, [ $this, 'render' ] );
		add_action( 'lw_seo_content_changed', [ Cache::class, 'flush' ] );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts = [] ): string {
		return self::output( is_array( $atts ) ? $atts : [] );
	}

	/**
	 * Markup for the given attributes (shared with the block).
	 *
	 * @param array<string, mixed> $atts post_types, taxonomies, max, order; empty = the setting.
	 * @return string
	 */
	public static function output( array $atts ): string {
		$args = self::resolve( $atts );
		$html = Cache::remember(
			Cache::key( $args ),
			static function () use ( $args ): string {
				/**
				 * Filter the sections of the HTML sitemap before they are rendered.
				 *
				 * @since 1.9.0
				 *
				 * @param array<int, array<string, mixed>> $sections Sections: label, type, items, archive_url.
				 * @param array<string, mixed>             $args     Resolved arguments.
				 */
				$sections = apply_filters(
					'lw_seo_html_sitemap_sections',
					Collector::sections( $args['post_types'], $args['taxonomies'], $args['max'], $args['order'] ),
					$args
				);

				return Renderer::render( $sections );
			}
		);

		/**
		 * Filter the HTML sitemap markup.
		 *
		 * @since 1.9.0
		 *
		 * @param string               $html Markup.
		 * @param array<string, mixed> $args Resolved arguments.
		 */
		return (string) apply_filters( 'lw_seo_html_sitemap_output', $html, $args );
	}

	/**
	 * Resolve attributes against the settings. Unknown post types and
	 * taxonomies are dropped.
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @return array{post_types: array<int, string>, taxonomies: array<int, string>, max: int, order: string}
	 */
	public static function resolve( array $atts ): array {
		$post_types = self::names( $atts['post_types'] ?? '', 'html_sitemap_post_types', array_keys( PostTypes::post_types() ) );
		$taxonomies = self::names( $atts['taxonomies'] ?? '', 'html_sitemap_taxonomies', array_keys( PostTypes::taxonomies() ) );

		// The setting is the ceiling: attributes can only lower it, so a
		// visitor-facing value cannot multiply cache entries or query size.
		$setting = (int) Options::get( 'html_sitemap_max_items' );
		$setting = $setting >= 1 ? min( $setting, self::MAX_ITEMS ) : 500;
		$max     = (int) ( $atts['max'] ?? 0 );
		$max     = $max >= 1 ? min( $max, $setting ) : $setting;

		$order = (string) ( $atts['order'] ?? '' );
		if ( ! in_array( $order, self::ORDERS, true ) ) {
			$order = (string) Options::get( 'html_sitemap_order' );
		}

		return [
			'post_types' => $post_types,
			'taxonomies' => $taxonomies,
			'max'        => $max,
			'order'      => in_array( $order, self::ORDERS, true ) ? $order : 'title',
		];
	}

	/**
	 * Names from a comma-separated attribute, or from the option's map when
	 * the attribute is empty; only existing ones.
	 *
	 * @param mixed              $attribute Attribute value.
	 * @param string             $option    Option holding a name => bool map.
	 * @param array<int, string> $known     Existing names.
	 * @return array<int, string>
	 */
	private static function names( mixed $attribute, string $option, array $known ): array {
		$attribute = is_string( $attribute ) ? trim( $attribute ) : '';

		if ( '' !== $attribute ) {
			$names = array_map( 'sanitize_key', explode( ',', $attribute ) );
		} else {
			$map   = Options::get( $option );
			$names = is_array( $map ) ? array_keys( array_filter( $map ) ) : [];
		}

		return array_values( array_intersect( array_unique( array_map( 'strval', $names ) ), $known ) );
	}
}
