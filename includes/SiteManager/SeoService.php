<?php
/**
 * SEO Service for LW Site Manager abilities.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\SiteManager;

use LightweightPlugins\SEO\Admin\MarkdownOverrideField;
use LightweightPlugins\SEO\ContentSignals;
use LightweightPlugins\SEO\Markdown\Dispatcher;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\SignalValue;

/**
 * Executes SEO abilities for the Site Manager.
 */
final class SeoService {

	/**
	 * SEO meta fields that can be read/written.
	 */
	private const META_FIELDS = [
		'title',
		'description',
		'noindex',
		'og_title',
		'og_description',
		'og_image',
		'ai_train',
		'ai_input',
		'search',
		'markdown_content',
	];

	/**
	 * Get SEO meta for a post or term.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_meta( array $input ): array|\WP_Error {
		if ( ! empty( $input['post_id'] ) ) {
			return self::get_post_meta( (int) $input['post_id'] );
		}

		if ( ! empty( $input['term_id'] ) ) {
			return self::get_term_meta( (int) $input['term_id'] );
		}

		return new \WP_Error( 'missing_id', __( 'Provide post_id or term_id.', 'lw-seo' ), [ 'status' => 400 ] );
	}

	/**
	 * Set SEO meta for a post or term.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function set_meta( array $input ): array|\WP_Error {
		$meta = $input['meta'] ?? [];

		if ( ! empty( $input['post_id'] ) ) {
			return self::set_post_meta( (int) $input['post_id'], $meta );
		}

		if ( ! empty( $input['term_id'] ) ) {
			return self::set_term_meta( (int) $input['term_id'], $meta );
		}

		return new \WP_Error( 'missing_id', __( 'Provide post_id or term_id.', 'lw-seo' ), [ 'status' => 400 ] );
	}

	/**
	 * Get resolved content signals for a post or term (the global signals
	 * when neither resolves).
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_content_signals( array $input ): array|\WP_Error {
		$object = self::resolve_object( $input );
		$denied = null === $object ? null : self::access_error( $object );
		if ( null !== $denied ) {
			return $denied;
		}

		return [
			'success' => true,
			'signals' => ContentSignals::resolve( $object ),
		];
	}

	/**
	 * Get markdown representation of a post or term.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_markdown( array $input ): array|\WP_Error {
		$object = self::resolve_object( $input );

		if ( null === $object ) {
			return new \WP_Error( 'not_found', __( 'Post or term not found.', 'lw-seo' ), [ 'status' => 404 ] );
		}

		$denied = self::access_error( $object );
		if ( null !== $denied ) {
			return $denied;
		}

		$output = Dispatcher::dispatch( $object );

		return [
			'success'  => true,
			'markdown' => $output,
			'tokens'   => (int) ( mb_strlen( $output ) / 4 ),
		];
	}

	/**
	 * Get global LW SEO options.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>
	 */
	public static function get_options( array $input ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by ability callback interface.
		return [
			'success' => true,
			'options' => Options::get_all(),
		];
	}

	/**
	 * Get SEO meta for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function get_post_meta( int $post_id ): array|\WP_Error {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'not_found', __( 'Post not found.', 'lw-seo' ), [ 'status' => 404 ] );
		}

		$denied = self::access_error( $post );
		if ( null !== $denied ) {
			return $denied;
		}

		$meta = [];
		foreach ( self::META_FIELDS as $field ) {
			$meta[ $field ] = Options::get_post_meta( $post_id, $field );
		}

		return [
			'success' => true,
			'type'    => 'post',
			'id'      => $post_id,
			'meta'    => $meta,
		];
	}

	/**
	 * Get SEO meta for a term.
	 *
	 * @param int $term_id Term ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function get_term_meta( int $term_id ): array|\WP_Error {
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_Error( 'not_found', __( 'Term not found.', 'lw-seo' ), [ 'status' => 404 ] );
		}

		$denied = self::access_error( $term );
		if ( null !== $denied ) {
			return $denied;
		}

		$meta = [];
		foreach ( self::META_FIELDS as $field ) {
			$meta[ $field ] = Options::get_term_meta( $term_id, $field );
		}

		return [
			'success' => true,
			'type'    => 'term',
			'id'      => $term_id,
			'meta'    => $meta,
		];
	}

	/**
	 * Set SEO meta for a post.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $meta    Meta fields to set.
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function set_post_meta( int $post_id, array $meta ): array|\WP_Error {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'not_found', __( 'Post not found.', 'lw-seo' ), [ 'status' => 404 ] );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'forbidden', __( 'You are not allowed to edit this post.', 'lw-seo' ), [ 'status' => 403 ] );
		}

		return self::write_fields( $meta, static fn( string $key, string $value ): bool => Options::set_post_meta( $post_id, $key, $value ) );
	}

	/**
	 * Set SEO meta for a term.
	 *
	 * @param int                  $term_id Term ID.
	 * @param array<string, mixed> $meta    Meta fields to set.
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function set_term_meta( int $term_id, array $meta ): array|\WP_Error {
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_Error( 'not_found', __( 'Term not found.', 'lw-seo' ), [ 'status' => 404 ] );
		}

		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return new \WP_Error( 'forbidden', __( 'You are not allowed to edit this term.', 'lw-seo' ), [ 'status' => 403 ] );
		}

		return self::write_fields( $meta, static fn( string $key, string $value ): bool => Options::set_term_meta( $term_id, $key, $value ) );
	}

	/**
	 * Write the known SEO fields in $meta and report what was updated and
	 * what was skipped: the Markdown override without unfiltered_html is
	 * left untouched and listed under "skipped".
	 *
	 * @param array<string, mixed>           $meta  Meta fields to set.
	 * @param callable(string, string): bool $write Writes one sanitized field.
	 * @return array<string, mixed>
	 */
	private static function write_fields( array $meta, callable $write ): array {
		$updated = [];
		$skipped = [];

		foreach ( $meta as $key => $value ) {
			if ( ! in_array( $key, self::META_FIELDS, true ) ) {
				continue;
			}

			if ( ! MarkdownOverrideField::may_set( $key ) ) {
				$skipped[] = $key;
				continue;
			}

			// Signal fields are whitelisted to yes/no; the multi-line Markdown
			// override keeps its newlines (as in the meta box); the rest is one line.
			$sanitized = match ( true ) {
				in_array( $key, [ 'ai_train', 'ai_input', 'search' ], true ) => SignalValue::sanitize( $value ),
				MarkdownOverrideField::KEY === $key                          => sanitize_textarea_field( (string) $value ),
				default                                                      => sanitize_text_field( (string) $value ),
			};
			$write( $key, $sanitized );
			$updated[] = $key;
		}

		return [
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of fields updated */
				__( '%d SEO fields updated.', 'lw-seo' ),
				count( $updated )
			),
			'updated' => $updated,
			'skipped' => $skipped,
		];
	}

	/**
	 * Access check shared by the read abilities. The abilities themselves
	 * only require edit_posts, so the target object is checked here:
	 * - a post is readable when it is publicly readable anyway (published,
	 *   no password, viewable post type) or the user can edit it;
	 * - a term is readable when its taxonomy is public or the user can edit
	 *   it.
	 *
	 * @param \WP_Post|\WP_Term $object Target object.
	 * @return \WP_Error|null 403 error, or null when readable.
	 */
	private static function access_error( \WP_Post|\WP_Term $object ): ?\WP_Error {
		if ( $object instanceof \WP_Term ) {
			$taxonomy = get_taxonomy( $object->taxonomy );
			$allowed  = ( $taxonomy instanceof \WP_Taxonomy && $taxonomy->public ) || current_user_can( 'edit_term', $object->term_id );
		} else {
			$public  = 'publish' === $object->post_status && '' === (string) $object->post_password && is_post_type_viewable( $object->post_type );
			$allowed = $public || current_user_can( 'edit_post', $object->ID );
		}

		return $allowed ? null : new \WP_Error( 'forbidden', __( 'You are not allowed to read this post or term.', 'lw-seo' ), [ 'status' => 403 ] );
	}

	/**
	 * Resolve a WP_Post or WP_Term from input.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return \WP_Post|\WP_Term|null
	 */
	private static function resolve_object( array $input ): \WP_Post|\WP_Term|null {
		if ( ! empty( $input['post_id'] ) ) {
			$post = get_post( (int) $input['post_id'] );
			return $post instanceof \WP_Post ? $post : null;
		}

		if ( ! empty( $input['term_id'] ) ) {
			$term = get_term( (int) $input['term_id'] );
			return $term instanceof \WP_Term ? $term : null;
		}

		return null;
	}
}
