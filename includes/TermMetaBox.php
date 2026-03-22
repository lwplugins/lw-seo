<?php
/**
 * Term Meta Box class for taxonomy term editor.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

use LightweightPlugins\SEO\Admin\TermFields;

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
	 * Saveable fields with their sanitize callbacks.
	 */
	private const FIELDS = [
		'title'            => 'sanitize_text_field',
		'description'      => 'sanitize_textarea_field',
		'noindex'          => 'sanitize_text_field',
		'og_title'         => 'sanitize_text_field',
		'og_description'   => 'sanitize_textarea_field',
		'og_image'         => 'esc_url_raw',
		'ai_train'         => 'sanitize_text_field',
		'ai_input'         => 'sanitize_text_field',
		'search'           => 'sanitize_text_field',
		'markdown_content' => 'sanitize_textarea_field',
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_hooks' ] );
	}

	/**
	 * Register edit form hooks for all public taxonomies.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		$taxonomies = get_taxonomies( [ 'public' => true ], 'names' );

		foreach ( $taxonomies as $taxonomy ) {
			add_action( $taxonomy . '_edit_form_fields', [ $this, 'render_fields' ], 10, 1 );
			add_action( 'edited_' . $taxonomy, [ $this, 'save_fields' ], 10, 1 );
		}
	}

	/**
	 * Render all SEO fields on term edit screen.
	 *
	 * @param \WP_Term $term Current term object.
	 * @return void
	 */
	public function render_fields( \WP_Term $term ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		TermFields::render( $term );
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

		foreach ( self::FIELDS as $field => $sanitize_callback ) {
			$input_name = 'lw_seo_' . $field;
			$value      = '';

			if ( isset( $_POST[ $input_name ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via $sanitize_callback.
				$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $input_name ] ) );
			}

			Options::set_term_meta( $term_id, $field, $value );
		}
	}
}
