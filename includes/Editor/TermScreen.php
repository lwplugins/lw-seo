<?php
/**
 * Term edit screen SEO fields (React).
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Editor;

use LightweightPlugins\SEO\Admin\BuildAssets;
use LightweightPlugins\SEO\Admin\MarkdownOverrideField;
use LightweightPlugins\SEO\Options;

/**
 * Mounts the React term fields inside the core term form. The app renders
 * real `lw_seo_{field}` inputs, so TermMetaBox's form save handler stores
 * them.
 */
final class TermScreen {

	/**
	 * Script handle.
	 */
	public const HANDLE = 'lw-seo-term';

	/**
	 * Enqueue the app on term.php for the given taxonomies.
	 *
	 * @param string             $hook       Current admin page.
	 * @param array<int, string> $taxonomies Taxonomies with SEO fields.
	 * @return void
	 */
	public static function enqueue( string $hook, array $taxonomies ): void {
		if ( 'term.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->taxonomy, $taxonomies, true ) ) {
			return;
		}

		if ( BuildAssets::enqueue( 'term', self::HANDLE ) ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Print the mount point and hand the stored values to the app.
	 *
	 * @param \WP_Term $term Term being edited.
	 * @return void
	 */
	public static function render( \WP_Term $term ): void {
		$term_id = (int) $term->term_id;
		$data    = [
			'values'          => MetaFields::read(
				MetaFields::TERM,
				static fn( string $field ): mixed => Options::get_term_meta( $term_id, $field )
			),
			'canEditMarkdown' => MarkdownOverrideField::may_set( MarkdownOverrideField::KEY ),
			'titleMax'        => 60,
			'descMax'         => 160,
			'termName'        => $term->name,
		];

		wp_add_inline_script( self::HANDLE, 'window.lwSeoTerm = ' . wp_json_encode( $data ) . ';', 'before' );

		echo '<tr class="lw-seo-term-row"><td colspan="2"><div id="lw-seo-term-root"></div></td></tr>';
	}
}
