<?php
/**
 * Yoast robots meta migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Options;

/**
 * Migrates Yoast robots flags into _lw_seo_noindex / _lw_seo_nofollow.
 *
 * Post level: _yoast_wpseo_meta-robots-noindex '1' = noindex (0=default,
 * 2=index); _yoast_wpseo_meta-robots-nofollow '1' = nofollow.
 * Term level: the string 'noindex' — resolved by TermMetaMigrator, which passes
 * booleans to apply_flags().
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
	 * Migrate robots flags for a post from Yoast post meta.
	 *
	 * @param int $post_id Post ID.
	 * @return array{migrated: bool, target_full: bool}
	 */
	public function migrate_post( int $post_id ): array {
		$noindex  = '1' === (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
		$nofollow = '1' === (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true );

		return $this->apply_flags( 'post', $post_id, $noindex, $nofollow );
	}

	/**
	 * Apply resolved noindex/nofollow booleans to LW SEO meta.
	 *
	 * @param string $type     'post' or 'term'.
	 * @param int    $id       Entity ID.
	 * @param bool   $noindex  Whether noindex should be set.
	 * @param bool   $nofollow Whether nofollow should be set.
	 * @return array{migrated: bool, target_full: bool}
	 */
	public function apply_flags( string $type, int $id, bool $noindex, bool $nofollow ): array {
		$migrated    = false;
		$target_full = false;

		foreach ( [
			'noindex'  => $noindex,
			'nofollow' => $nofollow,
		] as $flag => $wanted ) {
			if ( ! $wanted ) {
				continue;
			}
			$result      = $this->set_flag( $type, $id, $flag );
			$migrated    = $migrated || $result['migrated'];
			$target_full = $target_full || $result['target_full'];
		}

		return [
			'migrated'    => $migrated,
			'target_full' => $target_full,
		];
	}

	/**
	 * Set a single flag without overwriting existing LW SEO data.
	 *
	 * @param string $type Entity type ('post' or 'term').
	 * @param int    $id   Entity ID.
	 * @param string $flag 'noindex' or 'nofollow'.
	 * @return array{migrated: bool, target_full: bool}
	 */
	private function set_flag( string $type, int $id, string $flag ): array {
		$meta_key = Options::META_PREFIX . $flag;
		$getter   = 'get_' . $type . '_meta';
		$existing = $getter( $id, $meta_key, true );

		if ( '' !== $existing && false !== $existing ) {
			return [
				'migrated'    => false,
				'target_full' => true,
			];
		}

		if ( ! $this->dry_run ) {
			$updater = 'update_' . $type . '_meta';
			$updater( $id, $meta_key, '1' );
		}

		return [
			'migrated'    => true,
			'target_full' => false,
		];
	}
}
