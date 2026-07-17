<?php
/**
 * Yoast post-meta migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\Options;

/**
 * Migrates Yoast post meta to LW SEO post meta with honest accounting.
 *
 * Returns three buckets per run:
 *   - migrated:                actually copied at least one field
 *   - skipped_already_present: Yoast had data but LW SEO target was filled
 *   - skipped_no_data:         no recognized Yoast data, or only no-op values
 */
final class PostMetaMigrator {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Robots-meta sub-migrator.
	 *
	 * @var RobotsMigrator
	 */
	private RobotsMigrator $robots;

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run Whether to simulate without making changes.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->dry_run = $dry_run;
		$this->robots  = new RobotsMigrator( $dry_run );
	}

	/**
	 * Run post meta migration.
	 *
	 * @return array{migrated: int, skipped_already_present: int, skipped_no_data: int}
	 */
	public function migrate(): array {
		$counts = [
			'migrated'                => 0,
			'skipped_already_present' => 0,
			'skipped_no_data'         => 0,
		];

		foreach ( $this->find_post_ids() as $post_id ) {
			$post_id = (int) $post_id;
			if ( ! get_post( $post_id ) ) {
				continue;
			}

			$this->bucket( $counts, $this->migrate_post( $post_id ) );
		}

		return $counts;
	}

	/**
	 * Bucket a per-post result into the running counts.
	 *
	 * @param array{migrated: int, skipped_already_present: int, skipped_no_data: int} $counts Counts by reference.
	 * @param array{migrated: bool, target_full: bool}                                 $result Per-post result.
	 * @return void
	 */
	private function bucket( array &$counts, array $result ): void {
		if ( $result['migrated'] ) {
			++$counts['migrated'];
		} elseif ( $result['target_full'] ) {
			++$counts['skipped_already_present'];
		} else {
			++$counts['skipped_no_data'];
		}
	}

	/**
	 * Find all post IDs that have any _yoast_wpseo_* meta worth examining.
	 *
	 * @return array<string>
	 */
	private function find_post_ids(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		return (array) $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key LIKE '_yoast_wpseo_%'"
		);
	}

	/**
	 * Migrate a single post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{migrated: bool, target_full: bool}
	 */
	private function migrate_post( int $post_id ): array {
		$fields = $this->migrate_fields( $post_id );
		$robots = $this->robots->migrate_post( $post_id );

		return [
			'migrated'    => $fields['migrated'] || $robots['migrated'],
			'target_full' => $fields['target_full'] || $robots['target_full'],
		];
	}

	/**
	 * Migrate mapped simple fields for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{migrated: bool, target_full: bool}
	 */
	private function migrate_fields( int $post_id ): array {
		$migrated    = false;
		$target_full = false;

		foreach ( Mappings::POST_META_MAP as $yoast_key => $lw_field ) {
			$value = get_post_meta( $post_id, $yoast_key, true );
			if ( '' === $value || false === $value ) {
				continue;
			}

			if ( ! MetaCoerce::is_writable_string( $value, $lw_field ) ) {
				continue;
			}

			if ( 'og_image' === $lw_field ) {
				$value = MetaCoerce::as_url( $value );
				if ( '' === $value ) {
					continue;
				}
			}

			$lw_key   = Options::META_PREFIX . $lw_field;
			$existing = get_post_meta( $post_id, $lw_key, true );

			if ( '' !== $existing && false !== $existing ) {
				$target_full = true;
				continue;
			}

			if ( in_array( $lw_field, [ 'title', 'description' ], true ) ) {
				$value = VariableConverter::convert( $value );
			}

			if ( ! $this->dry_run ) {
				update_post_meta( $post_id, $lw_key, $value );
			}

			$migrated = true;
		}

		return [
			'migrated'    => $migrated,
			'target_full' => $target_full,
		];
	}
}
