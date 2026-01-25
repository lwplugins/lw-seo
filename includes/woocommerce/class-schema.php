<?php
/**
 * WooCommerce Schema class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\WooCommerce;

use LightweightPlugins\SEO\Options;

/**
 * Handles WooCommerce Product Schema.org JSON-LD.
 */
final class Schema {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', [ $this, 'output_product_schema' ], 100 );
	}

	/**
	 * Output product Schema.org JSON-LD.
	 *
	 * @return void
	 */
	public function output_product_schema(): void {
		if ( ! Options::get( 'schema_enabled' ) ) {
			return;
		}

		if ( ! Options::get( 'woo_schema_enabled', true ) ) {
			return;
		}

		if ( ! WooCommerce::is_product() ) {
			return;
		}

		$product = WooCommerce::get_product();

		if ( ! $product ) {
			return;
		}

		$schema = $this->build_product_schema( $product );

		if ( empty( $schema ) ) {
			return;
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		echo "\n<!-- LW SEO WooCommerce Schema -->\n";
		echo '<script type="application/ld+json">' . "\n";
		echo $json . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</script>' . "\n";
		echo "<!-- /LW SEO WooCommerce Schema -->\n";
	}

	/**
	 * Build product Schema.org data.
	 *
	 * @param \WC_Product $product The product.
	 * @return array<string, mixed>
	 */
	private function build_product_schema( \WC_Product $product ): array {
		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'@id'         => get_permalink( $product->get_id() ) . '#product',
			'name'        => $product->get_name(),
			'description' => $this->get_product_description( $product ),
			'url'         => get_permalink( $product->get_id() ),
		];

		// SKU.
		$sku = $product->get_sku();
		if ( $sku ) {
			$schema['sku'] = $sku;
		}

		// GTIN/EAN/UPC (if set as meta).
		$gtin = get_post_meta( $product->get_id(), '_gtin', true );
		if ( $gtin ) {
			$schema['gtin'] = $gtin;
		}

		// Images.
		$images = $this->get_product_images( $product );
		if ( ! empty( $images ) ) {
			$schema['image'] = $images;
		}

		// Brand.
		$brand = $this->get_product_brand( $product );
		if ( $brand ) {
			$schema['brand'] = [
				'@type' => 'Brand',
				'name'  => $brand,
			];
		}

		// Reviews and ratings.
		$rating = $this->get_aggregate_rating( $product );
		if ( $rating ) {
			$schema['aggregateRating'] = $rating;
		}

		// Reviews.
		$reviews = $this->get_product_reviews( $product );
		if ( ! empty( $reviews ) ) {
			$schema['review'] = $reviews;
		}

		// Offer(s).
		$schema['offers'] = $this->get_product_offers( $product );

		return $schema;
	}

	/**
	 * Get product description.
	 *
	 * @param \WC_Product $product The product.
	 * @return string
	 */
	private function get_product_description( \WC_Product $product ): string {
		$description = $product->get_short_description();

		if ( empty( $description ) ) {
			$description = $product->get_description();
		}

		$description = wp_strip_all_tags( $description );
		$description = wp_trim_words( $description, 50, '...' );

		return $description;
	}

	/**
	 * Get product images.
	 *
	 * @param \WC_Product $product The product.
	 * @return array<string>
	 */
	private function get_product_images( \WC_Product $product ): array {
		$images = [];

		// Main image.
		$main_image = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
		if ( $main_image ) {
			$images[] = $main_image;
		}

		// Gallery images.
		$gallery_ids = $product->get_gallery_image_ids();
		foreach ( $gallery_ids as $image_id ) {
			$image_url = wp_get_attachment_image_url( $image_id, 'large' );
			if ( $image_url ) {
				$images[] = $image_url;
			}
		}

		return $images;
	}

	/**
	 * Get product brand.
	 *
	 * @param \WC_Product $product The product.
	 * @return string
	 */
	private function get_product_brand( \WC_Product $product ): string {
		// Try product attribute.
		$brand = $product->get_attribute( 'brand' );

		if ( $brand ) {
			return $brand;
		}

		// Try taxonomy.
		$brand_terms = get_the_terms( $product->get_id(), 'product_brand' );

		if ( is_array( $brand_terms ) && ! empty( $brand_terms ) ) {
			return $brand_terms[0]->name;
		}

		return '';
	}

	/**
	 * Get aggregate rating.
	 *
	 * @param \WC_Product $product The product.
	 * @return array<string, mixed>|null
	 */
	private function get_aggregate_rating( \WC_Product $product ): ?array {
		$rating_count = $product->get_rating_count();
		$average      = $product->get_average_rating();

		if ( $rating_count < 1 || empty( $average ) ) {
			return null;
		}

		return [
			'@type'       => 'AggregateRating',
			'ratingValue' => floatval( $average ),
			'ratingCount' => $rating_count,
			'bestRating'  => 5,
			'worstRating' => 1,
		];
	}

	/**
	 * Get product reviews.
	 *
	 * @param \WC_Product $product The product.
	 * @return array<array<string, mixed>>
	 */
	private function get_product_reviews( \WC_Product $product ): array {
		if ( ! Options::get( 'woo_schema_reviews', true ) ) {
			return [];
		}

		$reviews = [];

		$comments = get_comments(
			[
				'post_id' => $product->get_id(),
				'status'  => 'approve',
				'type'    => 'review',
				'number'  => 5,
			]
		);

		foreach ( $comments as $comment ) {
			$rating = get_comment_meta( $comment->comment_ID, 'rating', true );

			if ( empty( $rating ) ) {
				continue;
			}

			$reviews[] = [
				'@type'         => 'Review',
				'author'        => [
					'@type' => 'Person',
					'name'  => $comment->comment_author,
				],
				'datePublished' => get_comment_date( 'c', $comment ),
				'reviewBody'    => $comment->comment_content,
				'reviewRating'  => [
					'@type'       => 'Rating',
					'ratingValue' => intval( $rating ),
					'bestRating'  => 5,
					'worstRating' => 1,
				],
			];
		}

		return $reviews;
	}

	/**
	 * Get product offers.
	 *
	 * @param \WC_Product $product The product.
	 * @return array<string, mixed>
	 */
	private function get_product_offers( \WC_Product $product ): array {
		$offer = [
			'@type'           => 'Offer',
			'url'             => get_permalink( $product->get_id() ),
			'priceCurrency'   => get_woocommerce_currency(),
			'price'           => $product->get_price(),
			'availability'    => $this->get_availability_url( $product ),
			'itemCondition'   => 'https://schema.org/NewCondition',
			'priceValidUntil' => gmdate( 'Y-12-31' ),
		];

		// Seller.
		$knowledge_type = Options::get( 'knowledge_type' );
		$knowledge_name = Options::get( 'knowledge_name' );

		if ( $knowledge_type ) {
			$seller_name     = ! empty( $knowledge_name ) ? $knowledge_name : get_bloginfo( 'name' );
			$offer['seller'] = [
				'@type' => ucfirst( $knowledge_type ),
				'name'  => $seller_name,
				'url'   => home_url( '/' ),
			];
		}

		return $offer;
	}

	/**
	 * Get Schema.org availability URL.
	 *
	 * @param \WC_Product $product The product.
	 * @return string
	 */
	private function get_availability_url( \WC_Product $product ): string {
		if ( ! $product->is_in_stock() ) {
			return 'https://schema.org/OutOfStock';
		}

		if ( $product->is_on_backorder() ) {
			return 'https://schema.org/BackOrder';
		}

		return 'https://schema.org/InStock';
	}
}
