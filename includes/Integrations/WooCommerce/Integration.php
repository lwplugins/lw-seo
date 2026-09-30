<?php
/**
 * WooCommerce integration.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

use LightweightPlugins\SEO\Integrations\IntegrationInterface;
use LightweightPlugins\SEO\Options;

/**
 * Product Open Graph and schema, product permalinks, the product Markdown
 * layout, and keeping the cart, checkout and my account pages out of the
 * sitemap. The WooCommerce SEO setting (woo_enabled) switches off the
 * product head output and permalinks only, as before.
 */
final class Integration implements IntegrationInterface {

	/**
	 * WooCommerce pages WooCommerce itself marks noindex (wc_page_no_robots()).
	 */
	private const EXCLUDED_PAGES = [ 'cart', 'checkout', 'myaccount' ];

	/**
	 * Integration ID.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'woocommerce';
	}

	/**
	 * WooCommerce is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return WooCommerce::is_active();
	}

	/**
	 * Register the WooCommerce hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		( new PermalinkFlagFlusher() )->register();
		add_filter( 'lw_seo_sitemap_default_excluded_ids', [ $this, 'exclude_pages' ] );
		add_filter( 'lw_seo_markdown_renderer', [ $this, 'markdown_renderer' ], 10, 2 );

		if ( ! Options::get( 'woo_enabled', true ) ) {
			return;
		}

		new OpenGraph();
		new Schema();
		new PermalinkWatcher();
	}

	/**
	 * Add the assigned cart, checkout and my account pages to the sitemap exclusions.
	 *
	 * @param mixed $ids Excluded post IDs.
	 * @return array<int, mixed>
	 */
	public function exclude_pages( $ids ): array {
		$ids = is_array( $ids ) ? $ids : [];

		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return $ids;
		}

		foreach ( self::EXCLUDED_PAGES as $page ) {
			$id = (int) wc_get_page_id( $page );

			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * The product Markdown layout for products.
	 *
	 * @param mixed    $renderer Renderer supplied so far (null = none).
	 * @param \WP_Post $post     Post object.
	 * @return mixed
	 */
	public function markdown_renderer( $renderer, \WP_Post $post ) {
		return null === $renderer && 'product' === $post->post_type ? new ProductRenderer( $post ) : $renderer;
	}
}
