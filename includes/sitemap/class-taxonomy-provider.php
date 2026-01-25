<?php
/**
 * Taxonomy Sitemap Provider.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

use LightweightPlugins\SEO\Options;

/**
 * Provides taxonomy terms for sitemap.
 */
final class Taxonomy_Provider implements Provider_Interface {

	/**
	 * Items per page.
	 */
	private const PER_PAGE = 1000;

	/**
	 * Taxonomy name.
	 *
	 * @var string
	 */
	private string $taxonomy;

	/**
	 * Constructor.
	 *
	 * @param string $taxonomy Taxonomy name.
	 */
	public function __construct( string $taxonomy ) {
		$this->taxonomy = $taxonomy;
	}

	/**
	 * Check if enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$key = 'sitemap_' . ( 'post_tag' === $this->taxonomy ? 'tags' : 'categories' );
		return (bool) Options::get( $key );
	}

	/**
	 * Get total pages.
	 *
	 * @return int
	 */
	public function get_total_pages(): int {
		$count = wp_count_terms(
			[
				'taxonomy'   => $this->taxonomy,
				'hide_empty' => true,
			]
		);

		if ( is_wp_error( $count ) ) {
			return 0;
		}

		return (int) ceil( (int) $count / self::PER_PAGE );
	}

	/**
	 * Get items for a page.
	 *
	 * @param int $page Page number.
	 * @return array<array{loc: string, lastmod?: string, changefreq?: string, priority?: string}>
	 */
	public function get_items( int $page ): array {
		$items = [];

		$terms = get_terms(
			[
				'taxonomy'   => $this->taxonomy,
				'hide_empty' => true,
				'number'     => self::PER_PAGE,
				'offset'     => ( $page - 1 ) * self::PER_PAGE,
			]
		);

		if ( is_wp_error( $terms ) ) {
			return $items;
		}

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$items[] = [
				'loc'        => $link,
				'changefreq' => 'weekly',
				'priority'   => '0.6',
			];
		}

		return $items;
	}
}
