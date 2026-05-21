<?php
/**
 * RankMath robots meta migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\RankMath;

use LightweightPlugins\SEO\Options;

/**
 * Migrates rank_math_robots (array of flags) into _lw_seo_noindex/_lw_seo_nofollow.
 *
 * RankMath stores robots as a serialized array — common values are
 * ["index","follow"] (default — nothing to do) or ["noindex"], ["nofollow"].
 */
final class RobotsMigrator {

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
	 * Migrate robots for an entity.
	 *
	 * @param string $type 'post', 'term', or 'user'.
	 * @param int    $id   Entity ID.
	 * @return array{migrated: bool, data_present: bool, target_full: bool}
	 */
	public function migrate( string $type, int $id ): array {
		$robots = $this->get_meta( $type, $id, 'rank_math_robots' );
		if ( ! is_array( $robots ) || empty( $robots ) ) {
			return [
				'migrated'     => false,
				'data_present' => false,
				'target_full'  => false,
			];
		}

		$migrated    = false;
		$target_full = false;

		foreach ( [ 'noindex', 'nofollow' ] as $flag ) {
			if ( ! in_array( $flag, $robots, true ) ) {
				continue;
			}
			$result      = $this->set_flag( $type, $id, $flag );
			$migrated    = $migrated || $result['migrated'];
			$target_full = $target_full || $result['target_full'];
		}

		return [
			'migrated'     => $migrated,
			'data_present' => $migrated || $target_full,
			'target_full'  => $target_full,
		];
	}

	/**
	 * Set a single robot flag.
	 *
	 * @param string $type Entity type.
	 * @param int    $id   Entity ID.
	 * @param string $flag 'noindex' or 'nofollow'.
	 * @return array{migrated: bool, target_full: bool}
	 */
	private function set_flag( string $type, int $id, string $flag ): array {
		$meta_key = Options::META_PREFIX . $flag;
		$existing = $this->get_meta( $type, $id, $meta_key );

		if ( '' !== $existing && false !== $existing ) {
			return [
				'migrated'    => false,
				'target_full' => true,
			];
		}

		if ( ! $this->dry_run ) {
			$this->update_meta( $type, $id, $meta_key, '1' );
		}

		return [
			'migrated'    => true,
			'target_full' => false,
		];
	}

	/**
	 * Get meta value for an entity.
	 *
	 * @param string $type Entity type.
	 * @param int    $id   Entity ID.
	 * @param string $key  Meta key.
	 * @return mixed
	 */
	private function get_meta( string $type, int $id, string $key ): mixed {
		$getter = 'get_' . $type . '_meta';
		return $getter( $id, $key, true );
	}

	/**
	 * Update meta value for an entity.
	 *
	 * @param string $type  Entity type.
	 * @param int    $id    Entity ID.
	 * @param string $key   Meta key.
	 * @param mixed  $value Meta value.
	 * @return void
	 */
	private function update_meta( string $type, int $id, string $key, mixed $value ): void {
		$updater = 'update_' . $type . '_meta';
		$updater( $id, $key, $value );
	}
}
