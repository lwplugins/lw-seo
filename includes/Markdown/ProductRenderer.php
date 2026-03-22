<?php
/**
 * WooCommerce Product Markdown Renderer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

use LightweightPlugins\SEO\Helpers\HtmlToMarkdown;

/**
 * Renders WooCommerce product content as markdown.
 *
 * Only loaded when WooCommerce is active.
 */
final class ProductRenderer implements RendererInterface {

	/**
	 * The post object (product).
	 *
	 * @var \WP_Post
	 */
	private \WP_Post $post;

	/**
	 * Constructor.
	 *
	 * @param \WP_Post $post Product post object.
	 */
	public function __construct( \WP_Post $post ) {
		$this->post = $post;
	}

	/**
	 * Get frontmatter data.
	 *
	 * @return array<string, mixed>
	 */
	public function frontmatter(): array {
		$product = wc_get_product( $this->post->ID );

		$data = [
			'title'    => get_the_title( $this->post ),
			'url'      => get_permalink( $this->post ),
			'language' => get_locale(),
		];

		if ( $product ) {
			$price_html              = $product->get_price_html();
			$data['price']           = $price_html ? html_entity_decode( wp_strip_all_tags( $price_html ), ENT_QUOTES, 'UTF-8' ) : '';
			$data['sku']             = $product->get_sku();
			$data['stock_status']    = $product->get_stock_status();
			$data['add_to_cart_url'] = $product->add_to_cart_url();
		}

		// Product categories.
		$categories = get_the_terms( $this->post->ID, 'product_cat' );
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$data['categories'] = array_map( fn( $cat ) => $cat->name, $categories );
		}

		// Featured image.
		$thumbnail = get_the_post_thumbnail_url( $this->post, 'large' );
		if ( $thumbnail ) {
			$data['featured_image'] = $thumbnail;
		}

		/** This filter is documented in includes/Markdown/PostRenderer.php */
		return apply_filters( 'lw_seo_markdown_frontmatter', $data, $this->post );
	}

	/**
	 * Get markdown body.
	 *
	 * @return string
	 */
	public function body(): string {
		$product = wc_get_product( $this->post->ID );
		$body    = '# ' . get_the_title( $this->post ) . "\n\n";

		// Short description.
		$short_desc = $this->post->post_excerpt;
		if ( ! empty( $short_desc ) ) {
			$body .= HtmlToMarkdown::convert( $short_desc ) . "\n";
		}

		// Full description.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$content = apply_filters( 'the_content', $this->post->post_content );
		if ( ! empty( trim( $content ) ) ) {
			$body .= "## Description\n\n";
			$body .= HtmlToMarkdown::convert( $content ) . "\n";
		}

		// Attributes table.
		if ( $product ) {
			$body .= $this->render_attributes( $product );

			// Add to cart link.
			$cart_url = $product->add_to_cart_url();
			if ( $product->is_purchasable() && $product->is_in_stock() ) {
				$body .= '**[' . $product->add_to_cart_text() . '](' . $cart_url . ")**\n\n";
			}
		}

		/** This filter is documented in includes/Markdown/PostRenderer.php */
		return apply_filters( 'lw_seo_markdown_body', $body, $this->post );
	}

	/**
	 * Render product attributes as markdown table.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return string
	 */
	private function render_attributes( \WC_Product $product ): string {
		$attributes = $product->get_attributes();

		if ( empty( $attributes ) ) {
			return '';
		}

		$output  = "## Attributes\n\n";
		$output .= "| Attribute | Value |\n";
		$output .= "| --- | --- |\n";

		foreach ( $attributes as $attribute ) {
			$name = wc_attribute_label( $attribute->get_name() );
			if ( $attribute->is_taxonomy() ) {
				$values = wc_get_product_terms(
					$product->get_id(),
					$attribute->get_name(),
					[ 'fields' => 'names' ]
				);
				$value  = implode( ', ', $values );
			} else {
				$value = implode( ', ', $attribute->get_options() );
			}
			$output .= '| ' . $name . ' | ' . $value . " |\n";
		}

		return $output . "\n";
	}
}
