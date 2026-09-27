<?php
/**
 * Finds and reads SEOPress post and term meta.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

/**
 * Read-only access to SEOPress's `_seopress_*` post and term meta.
 * SEOPress PRO redirect entries (post type seopress_404) are not posts with
 * SEO data and are excluded here; RedirectsMigrator reads them.
 */
final class MetaSource {

	/**
	 * Post IDs with at least one of the given meta keys.
	 *
	 * @param array<string> $keys Meta keys.
	 * @return array<int>
	 */
	public function post_ids( array $keys ): array {
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- One-time migration query; placeholders are built from the key count.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT pm.post_id FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key IN ({$placeholders}) AND pm.meta_value <> '' AND p.post_type <> 'seopress_404' ORDER BY pm.post_id", $keys ) );

		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Term IDs with at least one of the given meta keys.
	 *
	 * @param array<string> $keys Meta keys.
	 * @return array<int>
	 */
	public function term_ids( array $keys ): array {
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- One-time migration query; placeholders are built from the key count.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT term_id FROM {$wpdb->termmeta} WHERE meta_key IN ({$placeholders}) AND meta_value <> '' ORDER BY term_id", $keys ) );

		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Values of the given meta keys for one object.
	 *
	 * @param string        $type 'post' or 'term'.
	 * @param int           $id   Object ID.
	 * @param array<string> $keys Meta keys.
	 * @return array<string, mixed>
	 */
	public function values( string $type, int $id, array $keys ): array {
		$values = [];
		foreach ( $keys as $key ) {
			$values[ $key ] = 'term' === $type ? get_term_meta( $id, $key, true ) : get_post_meta( $id, $key, true );
		}

		return $values;
	}

	/**
	 * Number of objects carrying a meta key with a non-empty value.
	 *
	 * @param string $type 'post' or 'term'.
	 * @param string $key  Meta key.
	 * @return int
	 */
	public function count( string $type, string $key ): int {
		global $wpdb;
		$table  = 'term' === $type ? $wpdb->termmeta : $wpdb->postmeta;
		$column = 'term' === $type ? 'term_id' : 'post_id';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core meta table name and a literal column.
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT {$column}) FROM {$table} WHERE meta_key = %s AND meta_value <> ''", $key ) );
	}
}
