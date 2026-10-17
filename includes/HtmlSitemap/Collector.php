<?php
/**
 * HTML sitemap content collector.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\HtmlSitemap;

use LightweightPlugins\SEO\Content\Eligibility;
use LightweightPlugins\SEO\Content\PostTypes;
use LightweightPlugins\SEO\Content\QueryArgs;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Sitemap\ExcludedPosts;
use LightweightPlugins\SEO\Sitemap\PostProvider;

/**
 * Gathers the posts and terms the HTML sitemap lists: the same content the
 * XML sitemap exposes (noindex types, taxonomies and posts never appear),
 * capped per section, without running any content filter.
 */
final class Collector {

	/**
	 * Post types listed when none are chosen.
	 */
	private const DEFAULT_POST_TYPES = [ 'post', 'page' ];

	/**
	 * Build the sections.
	 *
	 * @param array<int, string> $post_types Post type names; empty = posts and pages.
	 * @param array<int, string> $taxonomies Taxonomy names.
	 * @param int                $max        Items per section.
	 * @param string             $order      title|date.
	 * @return array<int, array{label: string, type: string, items: array<int, array{title: string, url: string, depth: int}>, archive_url: ?string}>
	 */
	public static function sections( array $post_types, array $taxonomies, int $max, string $order ): array {
		$post_types = array() === $post_types ? self::DEFAULT_POST_TYPES : $post_types;
		$known      = PostTypes::post_types();
		$sections   = [];

		foreach ( array_values( array_unique( $post_types ) ) as $post_type ) {
			if ( isset( $known[ $post_type ] ) && Eligibility::is_type_indexable( $post_type ) ) {
				$sections[] = self::post_section( $post_type, $known[ $post_type ], $max, $order );
			}
		}

		$known = PostTypes::taxonomies();
		foreach ( array_values( array_unique( $taxonomies ) ) as $taxonomy ) {
			if ( isset( $known[ $taxonomy ] ) && ! Options::get( 'noindex_' . $taxonomy ) ) {
				$sections[] = self::term_section( $taxonomy, $known[ $taxonomy ], $max );
			}
		}

		return $sections;
	}

	/**
	 * One post type's section.
	 *
	 * @param string $post_type Post type name.
	 * @param string $label     Plural label.
	 * @param int    $max       Items cap.
	 * @param string $order     title|date.
	 * @return array{label: string, type: string, items: array<int, array{title: string, url: string, depth: int}>, archive_url: ?string}
	 */
	private static function post_section( string $post_type, string $label, int $max, string $order ): array {
		$excluded = ExcludedPosts::ids( $post_type );
		$args     = PostProvider::query_args( $post_type, $max + 1 + count( $excluded ), 0 );

		$args['orderby']                = 'date' === $order ? 'date' : 'title';
		$args['order']                  = 'date' === $order ? 'DESC' : 'ASC';
		$args['update_post_term_cache'] = false;

		$rows = [];
		foreach ( get_posts( $args ) as $post ) {
			if ( in_array( (int) $post->ID, $excluded, true ) || ! Eligibility::is_post_eligible( $post ) ) {
				continue;
			}

			/** This filter is documented in includes/Sitemap/PostProvider.php */
			if ( apply_filters( 'lw_seo_sitemap_exclude_post', false, $post->ID ) ) {
				continue;
			}

			$rows[] = [
				'id'     => (int) $post->ID,
				'parent' => (int) $post->post_parent,
				'title'  => (string) get_the_title( $post ),
				'url'    => (string) get_permalink( $post ),
			];
		}

		$capped = count( $rows ) > $max;
		$rows   = array_slice( $rows, 0, $max );

		return [
			'label'       => $label,
			'type'        => 'post_type',
			'items'       => Tree::arrange( $rows, is_post_type_hierarchical( $post_type ) ),
			'archive_url' => $capped ? self::archive_url( $post_type ) : null,
		];
	}

	/**
	 * One taxonomy's section.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $label    Plural label.
	 * @param int    $max      Items cap.
	 * @return array{label: string, type: string, items: array<int, array{title: string, url: string, depth: int}>, archive_url: ?string}
	 */
	private static function term_section( string $taxonomy, string $label, int $max ): array {
		$terms = get_terms(
			QueryArgs::filter(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => true,
					// Explicit id order, sorted by name below: with a name or the
					// default order WooCommerce sorts product taxonomies by `order`
					// meta, and its termmeta join combined with the noindex
					// meta_query drops terms without that meta (see TaxonomyProvider).
					'orderby'    => 'term_id',
					'order'      => 'ASC',
					'number'     => $max,
					'meta_query' => Eligibility::noindex_meta_query(),
				],
				QueryArgs::SITEMAP_TERMS
			)
		);

		$rows = [];
		foreach ( is_array( $terms ) ? $terms : [] as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$url = get_term_link( $term );
			if ( is_wp_error( $url ) ) {
				continue;
			}
			$rows[] = [
				'id'     => (int) $term->term_id,
				'parent' => (int) $term->parent,
				'title'  => (string) $term->name,
				'url'    => (string) $url,
			];
		}

		usort( $rows, static fn( array $a, array $b ): int => strcasecmp( $a['title'], $b['title'] ) );

		return [
			'label'       => $label,
			'type'        => 'taxonomy',
			'items'       => Tree::arrange( $rows, true ),
			'archive_url' => null,
		];
	}

	/**
	 * Archive page of a post type: the posts page for posts.
	 *
	 * @param string $post_type Post type name.
	 * @return string|null
	 */
	private static function archive_url( string $post_type ): ?string {
		$page_id = 'post' === $post_type ? (int) get_option( 'page_for_posts' ) : 0;
		$url     = $page_id > 0 ? get_permalink( $page_id ) : get_post_type_archive_link( $post_type );

		return is_string( $url ) && '' !== $url ? $url : null;
	}
}
