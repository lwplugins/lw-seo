<?php
/**
 * Fill empty LW SEO fields from existing Bricks SEO settings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\Bricks;

use LightweightPlugins\SEO\Editor\MetaFields;
use LightweightPlugins\SEO\Options;

/**
 * SeoSync only sees saves made after it was installed. This copies
 * the Bricks SEO settings saved before into the LW SEO fields, filling
 * empty fields only: an LW SEO value is never overwritten, and a robots
 * flag is only switched on.
 */
final class Backfill {

	/**
	 * Fill every post's empty LW SEO fields from its Bricks settings.
	 *
	 * @param bool $dry_run Count without writing.
	 * @return array{posts: int, fields: int} Posts changed and fields filled.
	 */
	public static function run( bool $dry_run = false ): array {
		$tally = [
			'posts'  => 0,
			'fields' => 0,
		];

		foreach ( self::post_ids() as $post_id ) {
			$current = [];
			foreach ( SeoMap::FIELDS as $field ) {
				$current[ $field ] = (string) Options::get_post_meta( $post_id, $field );
			}

			$fill = self::missing( SeoSync::settings( $post_id ), $current );
			if ( [] === $fill ) {
				continue;
			}

			++$tally['posts'];
			$tally['fields'] += count( $fill );

			if ( ! $dry_run ) {
				self::write( $post_id, $fill );
			}
		}

		return $tally;
	}

	/**
	 * The Bricks values of the LW SEO fields that are still empty.
	 *
	 * @param array<string, mixed>  $settings Bricks page settings.
	 * @param array<string, string> $current  LW SEO field => stored value.
	 * @return array<string, string>
	 */
	public static function missing( array $settings, array $current ): array {
		$fill = [];

		foreach ( SeoMap::to_lw( $settings ) as $field => $value ) {
			if ( '' !== $value && '' === ( $current[ $field ] ?? '' ) ) {
				$fill[ $field ] = $value;
			}
		}

		return $fill;
	}

	/**
	 * Store the filled values without syncing them back to Bricks.
	 *
	 * @param int                   $post_id Post ID.
	 * @param array<string, string> $fill    LW SEO field => value.
	 * @return void
	 */
	private static function write( int $post_id, array $fill ): void {
		SeoSync::paused(
			static function () use ( $post_id, $fill ): void {
				foreach ( $fill as $field => $value ) {
					Options::set_post_meta( $post_id, $field, MetaFields::sanitize( MetaFields::POST[ $field ], $value ) );
				}
			}
		);
	}

	/**
	 * IDs of the posts (no revisions) that have Bricks page settings.
	 *
	 * @return array<int, int>
	 */
	private static function post_ids(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off scan by meta key; no API lists posts of every type by meta key.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.post_id FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type <> 'revision'",
				SeoSync::BRICKS_KEY
			)
		);

		return array_map( 'intval', (array) $ids );
	}
}
