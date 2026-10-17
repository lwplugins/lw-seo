<?php
/**
 * WooCommerce Schema class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

use LightweightPlugins\SEO\Options;

/**
 * Handles WooCommerce Product Schema.org JSON-LD.
 *
 * A product page carries one Product node. When WooCommerce prints its own
 * structured data, LW SEO adds its data to WooCommerce's Product (and makes
 * WooCommerce build it when the theme's template skipped that); only when
 * WooCommerce's output is gone does LW SEO print a Product of its own.
 */
final class Schema {

	/**
	 * Product schema data.
	 *
	 * @var ProductSchema
	 */
	private ProductSchema $product_schema;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->product_schema = new ProductSchema();

		// After WooCommerce Brands (priority 20), which adds the brand.
		add_filter( 'woocommerce_structured_data_product', [ $this, 'enrich_woocommerce_markup' ], 30, 2 );
		// Before WooCommerce prints its structured data (priority 10).
		add_action( 'wp_footer', [ $this, 'output_product_schema' ], 5 );
		// WooCommerce prints only the node types on this list.
		add_filter( 'woocommerce_structured_data_type_for_page', [ $this, 'allow_product_group_type' ] );
	}

	/**
	 * Let WooCommerce print ProductGroup nodes: it groups its nodes by
	 * lowercased @type and drops every type missing from this list.
	 *
	 * @param mixed $types The node types WooCommerce prints on this page.
	 * @return mixed
	 */
	public function allow_product_group_type( $types ) {
		if ( ! is_array( $types ) || ! $this->is_enabled() || ! Options::get( 'woo_product_group' ) || in_array( 'productgroup', $types, true ) ) {
			return $types;
		}

		$types[] = 'productgroup';

		return $types;
	}

	/**
	 * Add LW SEO's data to WooCommerce's Product markup.
	 *
	 * @param mixed $markup  WooCommerce's Product markup.
	 * @param mixed $product The product.
	 * @return mixed
	 */
	public function enrich_woocommerce_markup( $markup, $product = null ) {
		if ( ! is_array( $markup ) || ! $product instanceof \WC_Product || ! $this->is_enabled() ) {
			return $markup;
		}

		/**
		 * Filters the product page's Product markup, built by WooCommerce or by LW SEO.
		 *
		 * @param array<string, mixed> $markup  Product markup.
		 * @param \WC_Product          $product The product.
		 */
		return apply_filters( 'lw_seo_woo_product_schema', $this->as_group( $this->product_schema->enrich( $markup, $product ), $product ), $product );
	}

	/**
	 * Make sure the product page has its Product node.
	 *
	 * @return void
	 */
	public function output_product_schema(): void {
		if ( ! $this->is_enabled() || ! WooCommerce::is_product() ) {
			return;
		}

		$product = WooCommerce::get_product();

		if ( $product ) {
			$this->ensure_product_node( $product );
		}
	}

	/**
	 * Give the product page its one Product node.
	 *
	 * @param \WC_Product $product The product.
	 * @return void
	 */
	public function ensure_product_node( \WC_Product $product ): void {
		$structured_data = $this->woocommerce_structured_data();

		if ( $structured_data ) {
			// Templates that skip woocommerce_single_product_summary never build it.
			if ( ! $this->has_product_node( $structured_data->get_data() ) ) {
				$structured_data->generate_product_data( $product );
			}
			return;
		}

		/** This filter is documented in includes/Integrations/WooCommerce/Schema.php */
		$schema = apply_filters( 'lw_seo_woo_product_schema', $this->as_group( $this->product_schema->build( $product ), $product ), $product );

		if ( ! is_array( $schema ) || empty( $schema ) ) {
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
	 * LW SEO's product schema is on.
	 *
	 * @return bool
	 */
	private function is_enabled(): bool {
		return Options::get( 'schema_enabled' ) && Options::get( 'woo_schema_enabled', true );
	}

	/**
	 * WooCommerce's structured data, when it will be printed on this page.
	 *
	 * @return \WC_Structured_Data|null
	 */
	private function woocommerce_structured_data(): ?\WC_Structured_Data {
		$structured_data = function_exists( 'WC' ) ? WC()->structured_data : null;

		if ( ! $structured_data instanceof \WC_Structured_Data ) {
			return null;
		}

		return false !== has_action( 'wp_footer', [ $structured_data, 'output_structured_data' ] ) ? $structured_data : null;
	}

	/**
	 * A variable product's node as a ProductGroup, when that is on.
	 *
	 * @param array<string, mixed> $node    The Product node.
	 * @param \WC_Product          $product The product.
	 * @return array<string, mixed>
	 */
	private function as_group( array $node, \WC_Product $product ): array {
		return ProductGroupSchema::applies( $product ) ? ProductGroupSchema::convert( $node, $product ) : $node;
	}

	/**
	 * Whether WooCommerce's collected data holds a Product (or ProductGroup) node.
	 *
	 * @param mixed $data WooCommerce's structured data.
	 * @return bool
	 */
	private function has_product_node( $data ): bool {
		foreach ( is_array( $data ) ? $data : [] as $node ) {
			if ( is_array( $node ) && array_intersect( [ 'Product', 'ProductGroup' ], (array) ( $node['@type'] ?? [] ) ) ) {
				return true;
			}
		}

		return false;
	}
}
