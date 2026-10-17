<?php
/**
 * Turns queued IndexNow posts and terms into URLs.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\IndexNow;

use LightweightPlugins\SEO\Content\Eligibility;
use LightweightPlugins\SEO\Sitemap\ExcludedPosts;

/**
 * Runs at flush time, once every meta, REST field and WooCommerce visibility
 * term of the saved object is in place, and keeps only eligible objects.
 */
final class QueueResolver {

	/**
	 * URLs to send for a queue.
	 *
	 * @param array{posts: array<int, int>, terms: array<int, int>, urls: array<int, string>} $queue Queue.
	 * @return array<int, string>
	 */
	public static function urls( array $queue ): array {
		$urls = $queue['urls'];

		if ( array() !== $queue['posts'] ) {
			// One query for the posts, their meta and terms instead of one per post.
			_prime_post_caches( $queue['posts'], true, true );
		}

		$excluded = [];

		foreach ( $queue['posts'] as $id ) {
			$url = self::post_url( $id, $excluded );
			if ( null !== $url ) {
				$urls[] = $url;
			}
		}

		foreach ( $queue['terms'] as $id ) {
			$url = self::term_url( $id );
			if ( null !== $url ) {
				$urls[] = $url;
			}
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * Permalink of an eligible post.
	 *
	 * @param int                            $id       Post ID.
	 * @param array<string, array<int, int>> $excluded Excluded IDs by post type (filled lazily).
	 * @return string|null
	 */
	private static function post_url( int $id, array &$excluded ): ?string {
		$post = get_post( $id );

		if ( ! $post instanceof \WP_Post || ! Eligibility::is_post_eligible( $post ) ) {
			return null;
		}

		$type = (string) $post->post_type;

		if ( ! isset( $excluded[ $type ] ) ) {
			$excluded[ $type ] = ExcludedPosts::ids( $type );
		}

		if ( in_array( $id, $excluded[ $type ], true ) ) {
			return null;
		}

		$url = (string) get_permalink( $post );

		return '' !== $url ? $url : null;
	}

	/**
	 * Archive link of an eligible term.
	 *
	 * @param int $id Term ID.
	 * @return string|null
	 */
	private static function term_url( int $id ): ?string {
		$term = get_term( $id );

		if ( ! $term instanceof \WP_Term || ! Eligibility::is_term_eligible( $term ) ) {
			return null;
		}

		$url = get_term_link( $term );

		return is_string( $url ) && '' !== $url ? $url : null;
	}
}
