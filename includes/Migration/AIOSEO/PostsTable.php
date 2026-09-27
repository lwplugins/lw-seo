<?php
/**
 * Read access to the All in One SEO posts table.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\AIOSEO;

/**
 * Reads {prefix}aioseo_posts (app/Common/Db/Schema.php:114-184) in batches.
 * Read-only: this importer never writes to All in One SEO's tables.
 */
final class PostsTable {

	/**
	 * Rows per batch.
	 */
	private const BATCH = 500;

	/**
	 * Columns the importer reads.
	 */
	private const COLUMNS = 'id, post_id, title, description, canonical_url, og_title, og_description, og_image_type, og_image_custom_url, twitter_use_og, twitter_title, twitter_description, twitter_image_type, twitter_image_custom_url, robots_default, robots_noindex, robots_nofollow, robots_noarchive, robots_nosnippet, robots_noimageindex, robots_noodp, robots_notranslate, primary_term';

	/**
	 * Condition matching rows that hold importable data.
	 */
	private const HAS_DATA = "(title <> '' OR description <> '' OR canonical_url <> '' OR og_title <> '' OR og_description <> '' OR og_image_custom_url <> '' OR twitter_title <> '' OR twitter_description <> '' OR twitter_image_custom_url <> '' OR robots_default = 0 OR primary_term <> '')";

	/**
	 * Full table name.
	 *
	 * @param string $suffix Table suffix.
	 * @return string
	 */
	public static function name( string $suffix = 'aioseo_posts' ): string {
		global $wpdb;
		return $wpdb->prefix . $suffix;
	}

	/**
	 * Whether a table exists on this site.
	 *
	 * @param string $suffix Table suffix.
	 * @return bool
	 */
	public static function exists( string $suffix = 'aioseo_posts' ): bool {
		global $wpdb;
		$table = self::name( $suffix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration check.
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	/**
	 * Number of rows holding importable data.
	 *
	 * @return int
	 */
	public function count_with_data(): int {
		global $wpdb;
		if ( ! self::exists() ) {
			return 0;
		}
		$table = self::name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Trusted table name ($wpdb->prefix + literal) and a constant condition.
		return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$table} WHERE " . self::HAS_DATA );
	}

	/**
	 * Count rows matching a constant condition (for warnings).
	 *
	 * @param string $condition SQL condition built from literals only.
	 * @return int
	 */
	public function count_where( string $condition ): int {
		global $wpdb;
		if ( ! self::exists() ) {
			return 0;
		}
		$table = self::name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Trusted table name; callers pass class-constant conditions only.
		return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$table} WHERE {$condition}" );
	}

	/**
	 * Every row with importable data, batch by batch.
	 *
	 * @return \Generator<int, array<string, mixed>>
	 */
	public function rows(): \Generator {
		global $wpdb;
		if ( ! self::exists() ) {
			return;
		}
		$table   = self::name();
		$last_id = 0;

		do {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Trusted table name and constant column list / condition.
			$batch = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT ' . self::COLUMNS . " FROM {$table} WHERE id > %d AND " . self::HAS_DATA . ' ORDER BY id ASC LIMIT %d', $last_id, self::BATCH ), ARRAY_A );

			foreach ( $batch as $row ) {
				$last_id = (int) $row['id'];
				yield $row;
			}
			$full = count( $batch ) === self::BATCH;
		} while ( $full );
	}
}
