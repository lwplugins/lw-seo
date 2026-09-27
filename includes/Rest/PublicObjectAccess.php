<?php
/**
 * Read access for the public LW SEO REST routes.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Rest;

use LightweightPlugins\SEO\Content\PostTypes;
use WP_Error;
use WP_Post;
use WP_Term;
use WP_User;

/**
 * Decides whether the current caller may read the SEO data of a post,
 * term or author through the unauthenticated `lw-seo/v1` routes.
 *
 * An object that is not readable answers exactly like a missing one
 * (404, same error code), so its existence is not disclosed.
 */
final class PublicObjectAccess {

	/**
	 * Whether anyone may read the post: published, not behind a password
	 * and of a viewable post type.
	 *
	 * @param WP_Post $post Post.
	 * @return bool
	 */
	public static function is_public_post( WP_Post $post ): bool {
		return 'publish' === $post->post_status
			&& ! post_password_required( $post )
			&& is_post_type_viewable( $post->post_type );
	}

	/**
	 * Whether the current user may read the post. Beyond public posts:
	 * users who can edit it, and readers of private posts of viewable
	 * post types (read_post). Non-viewable post types (e.g. LMS lessons)
	 * and password-protected posts need edit_post, since read_post maps to
	 * the plain `read` capability for published posts.
	 *
	 * @param WP_Post $post Post.
	 * @return bool
	 */
	public static function can_read_post( WP_Post $post ): bool {
		if ( self::is_public_post( $post ) || current_user_can( 'edit_post', $post->ID ) ) {
			return true;
		}

		return 'publish' !== $post->post_status
			&& '' === (string) $post->post_password
			&& is_post_type_viewable( $post->post_type )
			&& current_user_can( 'read_post', $post->ID );
	}

	/**
	 * Whether the current user may read the term: its taxonomy exists and
	 * is viewable, or the user can edit the term.
	 *
	 * @param WP_Term $term Term.
	 * @return bool
	 */
	public static function can_read_term( WP_Term $term ): bool {
		if ( ! taxonomy_exists( $term->taxonomy ) ) {
			return false;
		}

		return is_taxonomy_viewable( $term->taxonomy ) || current_user_can( 'edit_term', $term->term_id );
	}

	/**
	 * Whether the current user may read the author: like core's users
	 * endpoint, a user is public only once they have published posts in a
	 * viewable post type; otherwise list_users is required.
	 *
	 * @param WP_User $user User.
	 * @return bool
	 */
	public static function can_read_author( WP_User $user ): bool {
		$post_types = array_keys( PostTypes::post_types() );

		if ( [] !== $post_types && (int) count_user_posts( $user->ID, $post_types, true ) > 0 ) {
			return true;
		}

		return current_user_can( 'list_users' );
	}

	/**
	 * Null when the post is readable, else the post-not-found error.
	 *
	 * @param WP_Post $post Post.
	 * @return WP_Error|null
	 */
	public static function post_error( WP_Post $post ): ?WP_Error {
		return self::can_read_post( $post ) ? null : self::post_not_found();
	}

	/**
	 * Null when the term is readable, else the term-not-found error.
	 *
	 * @param WP_Term $term Term.
	 * @return WP_Error|null
	 */
	public static function term_error( WP_Term $term ): ?WP_Error {
		return self::can_read_term( $term ) ? null : self::term_not_found();
	}

	/**
	 * Null when the author is readable, else the author-not-found error.
	 *
	 * @param WP_User $user User.
	 * @return WP_Error|null
	 */
	public static function author_error( WP_User $user ): ?WP_Error {
		return self::can_read_author( $user ) ? null : self::author_not_found();
	}

	/**
	 * The error for a missing (or unreadable) post.
	 *
	 * @return WP_Error
	 */
	public static function post_not_found(): WP_Error {
		return new WP_Error( 'post_not_found', __( 'Post not found.', 'lw-seo' ), [ 'status' => 404 ] );
	}

	/**
	 * The error for a missing (or unreadable) term.
	 *
	 * @return WP_Error
	 */
	public static function term_not_found(): WP_Error {
		return new WP_Error( 'term_not_found', __( 'Term not found.', 'lw-seo' ), [ 'status' => 404 ] );
	}

	/**
	 * The error for a missing (or unreadable) author.
	 *
	 * @return WP_Error
	 */
	public static function author_not_found(): WP_Error {
		return new WP_Error( 'author_not_found', __( 'Author not found.', 'lw-seo' ), [ 'status' => 404 ] );
	}
}
