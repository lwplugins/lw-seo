<?php
/**
 * Term Meta Box class for taxonomy term editor.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

use LightweightPlugins\SEO\Admin\BuildAssets;
use LightweightPlugins\SEO\Admin\MarkdownOverrideField;
use LightweightPlugins\SEO\Editor\MetaFields;
use LightweightPlugins\SEO\Editor\TermScreen;

/**
 * Adds SEO fields to taxonomy term edit screens.
 */
final class TermMetaBox {

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'lw_seo_save_term_meta';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'lw_seo_term_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_hooks' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register edit form hooks for all public taxonomies.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		foreach ( $this->taxonomies() as $taxonomy ) {
			add_action( $taxonomy . '_edit_form_fields', [ $this, 'render_fields' ], 10, 1 );
			add_action( 'edited_' . $taxonomy, [ $this, 'save_fields' ], 10, 1 );
		}
	}

	/**
	 * Enqueue the React term fields on the term edit screen.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		TermScreen::enqueue( $hook, $this->taxonomies() );
	}

	/**
	 * Render the SEO fields mount point on the term edit screen.
	 *
	 * Without the build nothing is printed, not even the nonce, so a save
	 * cannot wipe the stored values with absent fields.
	 *
	 * @param \WP_Term $term Current term object.
	 * @return void
	 */
	public function render_fields( \WP_Term $term ): void {
		if ( ! BuildAssets::exists( 'term' ) ) {
			return;
		}

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		TermScreen::render( $term );
	}

	/**
	 * Save all SEO fields.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_fields( int $term_id ): void {
		if (
			! isset( $_POST[ self::NONCE_NAME ] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ),
				self::NONCE_ACTION
			)
		) {
			return;
		}

		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		foreach ( MetaFields::TERM as $field => $kind ) {
			if ( ! MarkdownOverrideField::may_set( $field ) ) {
				continue;
			}

			$input_name = 'lw_seo_' . $field;
			$value      = '';

			if ( isset( $_POST[ $input_name ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via MetaFields::sanitize().
				$value = MetaFields::sanitize( $kind, wp_unslash( $_POST[ $input_name ] ) );
			}

			Options::set_term_meta( $term_id, $field, $value );
		}
	}

	/**
	 * Taxonomies with SEO fields.
	 *
	 * @return array<int, string>
	 */
	private function taxonomies(): array {
		return array_values( get_taxonomies( [ 'public' => true ], 'names' ) );
	}
}
