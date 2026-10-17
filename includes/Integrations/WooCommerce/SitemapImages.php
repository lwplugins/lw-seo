<?php
/**
 * Product gallery images in the image sitemap.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

use LightweightPlugins\SEO\Sitemap\ImageCollector;

/**
 * Adds the product gallery to the images listed for a product. Gallery ids
 * come from the (already cached) post meta and the attachments of a whole
 * sitemap page are primed in one go, so no query runs per product.
 */
final class SitemapImages {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'lw_seo_sitemap_posts', [ $this, 'prime' ] );
		add_filter( 'lw_seo_sitemap_images', [ $this, 'append_gallery' ], 10, 2 );
	}

	/**
	 * Prime the gallery attachments of all products on a sitemap page.
	 *
	 * @param mixed $posts Posts of the page.
	 * @return void
	 */
	public function prime( $posts ): void {
		$ids = [];

		foreach ( is_array( $posts ) ? $posts : [] as $post ) {
			if ( $post instanceof \WP_Post && 'product' === $post->post_type ) {
				$ids = array_merge( $ids, self::gallery_ids( $post ) );
			}
		}

		if ( [] !== $ids && function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( array_values( array_unique( $ids ) ), false, true );
		}
	}

	/**
	 * Append the gallery image URLs of a product.
	 *
	 * @param mixed    $urls Image URLs so far.
	 * @param \WP_Post $post Post.
	 * @return array<int, string>
	 */
	public function append_gallery( $urls, \WP_Post $post ): array {
		$urls = is_array( $urls ) ? array_values( $urls ) : [];

		if ( 'product' !== $post->post_type || ! ImageCollector::content_allowed( $post ) ) {
			return $urls;
		}

		foreach ( self::gallery_ids( $post ) as $id ) {
			$url = wp_get_attachment_image_url( $id, 'full' );

			if ( is_string( $url ) && '' !== $url && ! in_array( $url, $urls, true ) ) {
				$urls[] = $url;
			}
		}

		return $urls;
	}

	/**
	 * Gallery attachment IDs from the product's post meta.
	 *
	 * @param \WP_Post $post Product post.
	 * @return array<int, int>
	 */
	private static function gallery_ids( \WP_Post $post ): array {
		$raw = (string) get_post_meta( $post->ID, '_product_image_gallery', true );

		return array_values( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) );
	}
}
