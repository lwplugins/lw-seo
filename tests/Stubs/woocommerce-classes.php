<?php
/**
 * WooCommerce API doubles for unit tests.
 *
 * Only the members LW SEO calls, with the signatures of WooCommerce 11.1
 * (includes/abstracts/abstract-wc-product.php, class-wc-comments.php).
 * PHPStan uses the php-stubs/woocommerce-stubs package instead; this file
 * is loaded by the unit test bootstrap only.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

// phpcs:disable -- test-only doubles, WPCS does not apply to tests/.

if ( ! class_exists( 'WC_Product' ) ) {
	/**
	 * WC_Product double: the cached rating values come from the constructor.
	 */
	class WC_Product {
		/**
		 * @param int   $id             Product ID.
		 * @param int   $rating_count   Cached rating count (_wc_rating_count).
		 * @param float $average_rating Cached average rating (_wc_average_rating).
		 */
		public function __construct( private int $id = 0, private int $rating_count = 0, private float $average_rating = 0.0, public array $test_props = [] ) {}

		private function prop( string $key, $fallback = '' ) {
			return $this->test_props[ $key ] ?? $fallback;
		}

		public function get_name( $context = 'view' ) {
			return $this->prop( 'name' );
		}

		public function get_sku( $context = 'view' ) {
			return $this->prop( 'sku' );
		}

		public function get_price( $context = 'view' ) {
			return $this->prop( 'price' );
		}

		public function get_global_unique_id( $context = 'view' ) {
			return $this->prop( 'global_unique_id' );
		}

		public function get_attribute( $attribute ) {
			return $this->prop( 'attribute_' . $attribute );
		}

		public function get_image_id( $context = 'view' ) {
			return $this->prop( 'image_id', 0 );
		}

		public function get_gallery_image_ids( $context = 'view' ) {
			return $this->prop( 'gallery_image_ids', [] );
		}

		public function is_in_stock() {
			return $this->prop( 'in_stock', true );
		}

		public function is_on_backorder( $qty_in_cart = 0 ) {
			return false;
		}

		public function get_id() {
			return $this->id;
		}

		public function get_rating_count( $value = null ) {
			return $this->rating_count;
		}

		public function get_average_rating( $context = 'view' ) {
			return $this->average_rating;
		}
	}
}

if ( ! class_exists( 'WC_Comments' ) ) {
	/**
	 * WC_Comments double.
	 */
	class WC_Comments {
		/**
		 * Test: live rating counts (rating value => number of reviews) by product ID.
		 *
		 * @var array<int, array<int|string, int>>
		 */
		public static array $test_rating_counts = [];

		/**
		 * Rating counts of a product's approved rated reviews, not cached.
		 *
		 * @param WC_Product $product Product instance.
		 * @return int[]
		 */
		public static function get_rating_counts_for_product( &$product ) {
			return self::$test_rating_counts[ $product->get_id() ] ?? [];
		}
	}
}

if ( ! class_exists( 'WC_Structured_Data' ) ) {
	/**
	 * WC_Structured_Data double: collects nodes like the real one.
	 */
	class WC_Structured_Data {
		/** @var array<int, array<string, mixed>> */
		public array $test_data = [];

		/** @var array<int, mixed> Products generate_product_data() was called with. */
		public array $test_generated = [];

		public function get_data() {
			return $this->test_data;
		}

		public function generate_product_data( $product = null ) {
			$this->test_generated[] = $product;
		}

		public function output_structured_data() {}
	}
}
