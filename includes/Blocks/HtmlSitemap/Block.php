<?php
/**
 * HTML sitemap block registration and rendering.
 *
 * @package LightweightPlugins\SEO\Blocks\HtmlSitemap
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Blocks\HtmlSitemap;

use LightweightPlugins\SEO\HtmlSitemap\Shortcode;

/**
 * Dynamic block with the attributes of the [lw_html_sitemap] shortcode.
 */
final class Block {

	/**
	 * Block name.
	 */
	public const NAME = 'lw-seo/html-sitemap';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Register the block.
	 *
	 * @return void
	 */
	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			LW_SEO_PATH . 'includes/Blocks/HtmlSitemap',
			[
				'render_callback' => [ $this, 'render_block' ],
			]
		);
	}

	/**
	 * Enqueue the editor script.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		wp_enqueue_script(
			'lw-seo-html-sitemap-block',
			LW_SEO_URL . 'assets/js/blocks/html-sitemap.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ],
			LW_SEO_VERSION,
			true
		);
	}

	/**
	 * Render the block on the frontend.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render_block( array $attributes ): string {
		return Shortcode::output( $attributes );
	}
}
