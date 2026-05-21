<?php
/**
 * One-time cleanup for v1.3.13's bad og_image migration.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration;

use LightweightPlugins\SEO\Helpers\MetaCoerce;

/**
 * Scans `_lw_seo_og_image` post/term meta written by the v1.3.13 migrator and
 * fixes any rows that contain arrays (RankMath's `rank_math_og_content_image`
 * cache shape was copied verbatim). Each row is either normalized to a URL
 * string or deleted when no URL can be extracted.
 *
 * Runs once on `init` after the `lw_seo_cleanup_v1314_done` option is missing,
 * then sets the option to skip future runs.
 */
final class CleanupV1314 {

	private const DONE_OPTION = 'lw_seo_cleanup_v1314_done';
	private const META_KEY    = '_lw_seo_og_image';

	/**
	 * Register the one-shot cleanup hook.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( get_option( self::DONE_OPTION ) ) {
			return;
		}
		add_action( 'init', [ $this, 'run' ], 99 );
	}

	/**
	 * Walk every row with an array value in `_lw_seo_og_image` and fix it.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( get_option( self::DONE_OPTION ) ) {
			return;
		}

		$this->cleanup_post_meta();
		$this->cleanup_term_meta();

		update_option( self::DONE_OPTION, time(), false );
	}

	/**
	 * Cleanup affected post meta rows.
	 *
	 * @return void
	 */
	private function cleanup_post_meta(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time cleanup.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
				self::META_KEY,
				'a:%'
			),
			ARRAY_A
		);

		foreach ( $rows as $row ) {
			$post_id = (int) ( $row['post_id'] ?? 0 );
			$value   = maybe_unserialize( (string) ( $row['meta_value'] ?? '' ) );
			$url     = MetaCoerce::as_url( $value );

			if ( '' !== $url ) {
				update_post_meta( $post_id, self::META_KEY, $url );
			} else {
				delete_post_meta( $post_id, self::META_KEY );
			}
		}
	}

	/**
	 * Cleanup affected term meta rows.
	 *
	 * @return void
	 */
	private function cleanup_term_meta(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time cleanup.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key = %s AND meta_value LIKE %s",
				self::META_KEY,
				'a:%'
			),
			ARRAY_A
		);

		foreach ( $rows as $row ) {
			$term_id = (int) ( $row['term_id'] ?? 0 );
			$value   = maybe_unserialize( (string) ( $row['meta_value'] ?? '' ) );
			$url     = MetaCoerce::as_url( $value );

			if ( '' !== $url ) {
				update_term_meta( $term_id, self::META_KEY, $url );
			} else {
				delete_term_meta( $term_id, self::META_KEY );
			}
		}
	}
}
