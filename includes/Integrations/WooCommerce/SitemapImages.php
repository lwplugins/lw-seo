<?php
/**
 * Product gallery images in the image sitemap.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

/**
 * Adds the product gallery to the images listed for a product.
 */
final class SitemapImages {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'lw_seo_sitemap_images', [ $this, 'append_gallery' ], 10, 2 );
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

		if ( 'product' !== $post->post_type || ! function_exists( 'wc_get_product' ) ) {
			return $urls;
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return $urls;
		}

		foreach ( $product->get_gallery_image_ids() as $id ) {
			$url = wp_get_attachment_image_url( (int) $id, 'full' );

			if ( is_string( $url ) && '' !== $url && ! in_array( $url, $urls, true ) ) {
				$urls[] = $url;
			}
		}

		return $urls;
	}
}
