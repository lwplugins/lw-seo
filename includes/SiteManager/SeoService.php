<?php
/**
 * SEO Service for LW Site Manager abilities.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\SiteManager;

use LightweightPlugins\SEO\ContentSignals;
use LightweightPlugins\SEO\Markdown\Dispatcher;
use LightweightPlugins\SEO\Options;

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
	 * Get resolved content signals for a post or term.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>
	 */
	public static function get_content_signals( array $input ): array {
		$object = self::resolve_object( $input );

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

		$output = Dispatcher::dispatch( $object );

		if ( null === $output ) {
			return new \WP_Error( 'not_supported', __( 'Markdown not available for this content.', 'lw-seo' ), [ 'status' => 400 ] );
		}

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

		$updated = [];
		foreach ( $meta as $key => $value ) {
			if ( in_array( $key, self::META_FIELDS, true ) ) {
				Options::set_post_meta( $post_id, $key, sanitize_text_field( (string) $value ) );
				$updated[] = $key;
			}
		}

		return [
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of fields updated */
				__( '%d SEO fields updated.', 'lw-seo' ),
				count( $updated )
			),
			'updated' => $updated,
		];
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

		$updated = [];
		foreach ( $meta as $key => $value ) {
			if ( in_array( $key, self::META_FIELDS, true ) ) {
				Options::set_term_meta( $term_id, $key, sanitize_text_field( (string) $value ) );
				$updated[] = $key;
			}
		}

		return [
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of fields updated */
				__( '%d SEO fields updated.', 'lw-seo' ),
				count( $updated )
			),
			'updated' => $updated,
		];
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
