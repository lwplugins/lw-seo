<?php
/**
 * RankMath primary-term migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\RankMath;

use LightweightPlugins\SEO\Options;

/**
 * Migrates RankMath's per-post "primary term" selections to LW SEO meta.
 *
 * RankMath stores the term_id of the primary term per taxonomy:
 *   rank_math_primary_category      → _lw_seo_primary_category
 *   rank_math_primary_product_cat   → _lw_seo_primary_product_cat
 *   rank_math_primary_product_brand → _lw_seo_primary_product_brand
 *
 * Existing LW SEO meta is never overwritten.
 */
final class PrimaryTermMigrator {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run Whether to simulate without making changes.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->dry_run = $dry_run;
	}

	/**
	 * Run primary-term migration.
	 *
	 * @return array{migrated: int, taxonomies: array<string, int>}
	 */
	public function migrate(): array {
		$migrated   = 0;
		$taxonomies = array_fill_keys( array_values( Mappings::PRIMARY_TERM_MAP ), 0 );

		foreach ( $this->find_post_ids() as $post_id ) {
			$post_id = (int) $post_id;

			foreach ( Mappings::PRIMARY_TERM_MAP as $rm_key => $lw_field ) {
				if ( $this->migrate_one( $post_id, $rm_key, $lw_field ) ) {
					++$migrated;
					++$taxonomies[ $lw_field ];
				}
			}
		}

		return [
			'migrated'   => $migrated,
			'taxonomies' => $taxonomies,
		];
	}

	/**
	 * Migrate one primary-term key for a single post.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $rm_key   RankMath meta key.
	 * @param string $lw_field LW SEO meta field name (without prefix).
	 * @return bool True if a value was migrated for this key.
	 */
	private function migrate_one( int $post_id, string $rm_key, string $lw_field ): bool {
		$value = (int) get_post_meta( $post_id, $rm_key, true );
		if ( $value <= 0 ) {
			return false;
		}

		$lw_key   = Options::META_PREFIX . $lw_field;
		$existing = get_post_meta( $post_id, $lw_key, true );
		if ( '' !== $existing && false !== $existing ) {
			return false;
		}

		if ( ! $this->dry_run ) {
			update_post_meta( $post_id, $lw_key, $value );
		}

		return true;
	}

	/**
	 * Find post IDs with any rank_math_primary_* meta.
	 *
	 * @return array<string>
	 */
	private function find_post_ids(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		return (array) $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key LIKE 'rank_math_primary_%'"
		);
	}
}
