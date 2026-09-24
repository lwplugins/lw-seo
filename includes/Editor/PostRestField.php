<?php
/**
 * `lw_seo` REST field on posts.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Editor;

use LightweightPlugins\SEO\Admin\MarkdownOverrideField;
use LightweightPlugins\SEO\Options;
use WP_Error;

/**
 * Exposes the post SEO meta to the block editor as one `lw_seo` object
 * field (edit context only) and writes partial updates back.
 */
final class PostRestField {

	/**
	 * Field name.
	 */
	public const FIELD = 'lw_seo';

	/**
	 * Hook the field registration.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_field' ] );
	}

	/**
	 * Register the field on every editor post type.
	 *
	 * @return void
	 */
	public function register_field(): void {
		register_rest_field(
			EditorPostTypes::get(),
			self::FIELD,
			[
				'get_callback'    => [ $this, 'get_value' ],
				'update_callback' => [ $this, 'update_value' ],
				'schema'          => $this->schema(),
			]
		);
	}

	/**
	 * Current values.
	 *
	 * @param array<string, mixed> $post Prepared post data.
	 * @return array<string, bool|string>
	 */
	public function get_value( array $post ): array {
		$post_id = (int) ( $post['id'] ?? 0 );
		$values  = MetaFields::read(
			MetaFields::POST,
			static fn( string $field ): mixed => Options::get_post_meta( $post_id, $field )
		);

		$values['can_edit_markdown'] = MarkdownOverrideField::may_set( MarkdownOverrideField::KEY );

		return $values;
	}

	/**
	 * Write the submitted keys only; absent keys stay as stored.
	 *
	 * The Markdown override is skipped silently without unfiltered_html;
	 * `can_edit_markdown` is read-only.
	 *
	 * @param mixed    $value Submitted field value.
	 * @param \WP_Post $post  Post being saved.
	 * @return true|WP_Error
	 */
	public function update_value( mixed $value, \WP_Post $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return new WP_Error(
				'rest_cannot_edit',
				__( 'Sorry, you are not allowed to edit the SEO settings of this post.', 'lw-seo' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		$value = is_array( $value ) ? $value : (array) $value;

		foreach ( MetaFields::POST as $field => $kind ) {
			if ( ! array_key_exists( $field, $value ) || ! MarkdownOverrideField::may_set( $field ) ) {
				continue;
			}

			Options::set_post_meta( $post->ID, $field, MetaFields::sanitize( $kind, $value[ $field ] ) );
		}

		return true;
	}

	/**
	 * Field schema: an object readable in the edit context.
	 *
	 * @return array<string, mixed>
	 */
	private function schema(): array {
		$properties = [];
		foreach ( MetaFields::POST as $field => $kind ) {
			$properties[ $field ] = [ 'type' => MetaFields::FLAG === $kind ? 'boolean' : 'string' ];
		}
		$properties['can_edit_markdown'] = [
			'type'     => 'boolean',
			'readonly' => true,
		];

		return [
			'description' => __( 'LW SEO fields.', 'lw-seo' ),
			'type'        => 'object',
			'context'     => [ 'edit' ],
			'properties'  => $properties,
		];
	}
}
