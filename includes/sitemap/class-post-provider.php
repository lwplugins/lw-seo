<?php
/**
 * Post Sitemap Provider.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

use LightweightPlugins\SEO\Options;

/**
 * Provides posts for sitemap.
 */
class Post_Provider implements Provider_Interface {

	/**
	 * Items per page.
	 */
	private const PER_PAGE = 1000;

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected string $post_type = 'post';

	/**
	 * Option key for enabled check.
	 *
	 * @var string
	 */
	protected string $option_key = 'sitemap_posts';

	/**
	 * Check if enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) Options::get( $this->option_key );
	}

	/**
	 * Get total pages.
	 *
	 * @return int
	 */
	public function get_total_pages(): int {
		$count = $this->get_total_items();
		return (int) ceil( $count / self::PER_PAGE );
	}

	/**
	 * Get total items count.
	 *
	 * @return int
	 */
	private function get_total_items(): int {
		$counts = wp_count_posts( $this->post_type );
		return (int) $counts->publish;
	}

	/**
	 * Get items for a page.
	 *
	 * @param int $page Page number.
	 * @return array<array{loc: string, lastmod?: string, changefreq?: string, priority?: string}>
	 */
	public function get_items( int $page ): array {
		$items = [];

		$posts = get_posts(
			[
				'post_type'      => $this->post_type,
				'post_status'    => 'publish',
				'posts_per_page' => self::PER_PAGE,
				'offset'         => ( $page - 1 ) * self::PER_PAGE,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
				'meta_query'     => [
					'relation' => 'OR',
					[
						'key'     => Options::META_PREFIX . 'noindex',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => Options::META_PREFIX . 'noindex',
						'value'   => '1',
						'compare' => '!=',
					],
				],
			]
		);

		foreach ( $posts as $post ) {
			$items[] = [
				'loc'        => get_permalink( $post ),
				'lastmod'    => get_the_modified_date( 'c', $post ),
				'changefreq' => 'weekly',
				'priority'   => '0.8',
			];
		}

		return $items;
	}
}
