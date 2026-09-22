<?php
/**
 * Post type sitemap provider.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

use LightweightPlugins\SEO\Content\Eligibility;

/**
 * Provides the published, indexable posts of one post type.
 */
final class PostProvider implements ProviderInterface {

	/**
	 * Items per page.
	 */
	private const PER_PAGE = 1000;

	/**
	 * Post type.
	 *
	 * @var string
	 */
	private string $post_type;

	/**
	 * Constructor.
	 *
	 * @param string $post_type Post type name.
	 */
	public function __construct( string $post_type ) {
		$this->post_type = $post_type;
	}

	/**
	 * A post type set to noindex has no sitemap.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return Eligibility::is_type_indexable( $this->post_type );
	}

	/**
	 * Get total pages.
	 *
	 * @return int
	 */
	public function get_total_pages(): int {
		$counts = wp_count_posts( $this->post_type );

		return (int) ceil( (int) ( $counts->publish ?? 0 ) / self::PER_PAGE );
	}

	/**
	 * Get items for a page.
	 *
	 * @param int $page Page number.
	 * @return array<array{loc: string, lastmod?: string, changefreq?: string, priority?: string}>
	 */
	public function get_items( int $page ): array {
		$posts = get_posts(
			[
				'post_type'      => $this->post_type,
				'post_status'    => 'publish',
				'has_password'   => false,
				'posts_per_page' => self::PER_PAGE,
				'offset'         => ( $page - 1 ) * self::PER_PAGE,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
				'meta_query'     => Eligibility::noindex_meta_query(),
			]
		);

		$excluded = ExcludedPosts::ids( $this->post_type );

		$items = [];
		foreach ( $posts as $post ) {
			if ( in_array( (int) $post->ID, $excluded, true ) || ! Eligibility::is_post_eligible( $post ) ) {
				continue;
			}

			/**
			 * Exclude a specific post from the sitemap.
			 *
			 * @param bool $exclude Whether to exclude.
			 * @param int  $post_id Post ID.
			 */
			if ( apply_filters( 'lw_seo_sitemap_exclude_post', false, $post->ID ) ) {
				continue;
			}

			$items[] = [
				'loc'        => (string) get_permalink( $post ),
				'lastmod'    => (string) get_the_modified_date( 'c', $post ),
				'changefreq' => 'weekly',
				'priority'   => '0.8',
			];
		}

		return $items;
	}
}
