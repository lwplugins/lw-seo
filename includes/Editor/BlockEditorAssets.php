<?php
/**
 * Block editor SEO panel assets.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Editor;

use LightweightPlugins\SEO\Admin\BuildAssets;

/**
 * Loads the React SEO panel (build/editor) in the block editor.
 */
final class BlockEditorAssets {

	/**
	 * Script handle.
	 */
	private const HANDLE = 'lw-seo-editor';

	/**
	 * Hook the enqueue.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue' ] );
	}

	/**
	 * Enqueue when the edited post type has SEO fields.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		$screen     = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$post_types = EditorPostTypes::get();

		if ( ! $screen || ! in_array( $screen->post_type, $post_types, true ) ) {
			return;
		}

		if ( ! BuildAssets::enqueue( 'editor', self::HANDLE ) ) {
			return;
		}

		wp_add_inline_script(
			self::HANDLE,
			'window.lwSeoEditor = ' . wp_json_encode(
				[
					'postTypes' => $post_types,
					'titleMax'  => 60,
					'descMax'   => 160,
				]
			) . ';',
			'before'
		);
	}
}
