<?php
/**
 * RankMath user-meta migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\RankMath;

/**
 * Migrates RankMath user meta (currently only the per-author robots flag) to LW SEO.
 */
final class UserMetaMigrator {

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
		$this->robots = new RobotsMigrator( $dry_run );
	}

	/**
	 * Run user meta migration.
	 *
	 * @return array{migrated: int, skipped_already_present: int, skipped_no_data: int}
	 */
	public function migrate(): array {
		$counts = [
			'migrated'                => 0,
			'skipped_already_present' => 0,
			'skipped_no_data'         => 0,
		];

		foreach ( $this->find_user_ids() as $user_id ) {
			$result = $this->robots->migrate( 'user', (int) $user_id );

			if ( $result['migrated'] ) {
				++$counts['migrated'];
			} elseif ( $result['target_full'] ) {
				++$counts['skipped_already_present'];
			} else {
				++$counts['skipped_no_data'];
			}
		}

		return $counts;
	}

	/**
	 * Find user IDs with any rank_math_* meta.
	 *
	 * @return array<string>
	 */
	private function find_user_ids(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		return (array) $wpdb->get_col(
			"SELECT DISTINCT user_id FROM {$wpdb->usermeta}
			 WHERE meta_key LIKE 'rank_math_%'"
		);
	}
}
