<?php
/**
 * WooCommerce integration main class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\WooCommerce;

use LightweightPlugins\SEO\Options;

/**
 * Main WooCommerce integration class.
 *
 * Handles detection and coordinates WooCommerce-specific SEO features.
 */
final class WooCommerce {

	/**
	 * Whether WooCommerce is active.
	 *
	 * @var bool|null
	 */
	private static ?bool $is_active = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! self::is_active() ) {
			return;
		}

		if ( ! Options::get( 'woo_enabled', true ) ) {
			return;
		}

		$this->init_components();
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		if ( null === self::$is_active ) {
			self::$is_active = class_exists( 'WooCommerce' ) || defined( 'WC_VERSION' );
		}

		return self::$is_active;
	}

	/**
	 * Initialize WooCommerce SEO components.
	 *
	 * @return void
	 */
	private function init_components(): void {
		new OpenGraph();
		new Schema();
	}

	/**
	 * Get the current product.
	 *
	 * @return \WC_Product|null
	 */
	public static function get_product(): ?\WC_Product {
		if ( ! self::is_active() ) {
			return null;
		}

		if ( ! is_singular( 'product' ) ) {
			return null;
		}

		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		$product = wc_get_product( $post->ID );

		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * Check if current page is a WooCommerce page.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_page(): bool {
		if ( ! self::is_active() ) {
			return false;
		}

		if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
			return true;
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if current page is a product page.
	 *
	 * @return bool
	 */
	public static function is_product(): bool {
		if ( ! self::is_active() ) {
			return false;
		}

		return is_singular( 'product' );
	}

	/**
	 * Check if current page is a product category.
	 *
	 * @return bool
	 */
	public static function is_product_category(): bool {
		if ( ! self::is_active() ) {
			return false;
		}

		return function_exists( 'is_product_category' ) && is_product_category();
	}

	/**
	 * Get WooCommerce shop page URL.
	 *
	 * @return string
	 */
	public static function get_shop_url(): string {
		if ( ! self::is_active() ) {
			return '';
		}

		if ( function_exists( 'wc_get_page_permalink' ) ) {
			return wc_get_page_permalink( 'shop' );
		}

		return '';
	}
}
