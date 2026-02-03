<?php
/**
 * RankMath Meta Migrator class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\RankMath;

use LightweightPlugins\SEO\Migration\VariableConverter;
use LightweightPlugins\SEO\Options;

/**
 * Migrates RankMath post/term/user meta to LW SEO meta.
 */
final class MetaMigrator {

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
	 * Migrate post meta.
	 *
	 * @return array{migrated: int, skipped: int}
	 */
	public function migrate_posts(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		$post_ids = $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key LIKE 'rank_math_%'
			 AND meta_key NOT LIKE 'rank_math_internal%'
			 AND meta_key NOT LIKE 'rank_math_seo_score%'"
		);

		$migrated = 0;
		$skipped  = 0;

		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			$post    = get_post( $post_id );

			if ( ! $post ) {
				continue;
			}

			$did_migrate  = $this->migrate_post_fields( $post_id );
			$did_migrate |= $this->migrate_post_robots( $post_id );

			if ( $did_migrate ) {
				++$migrated;
			} else {
				++$skipped;
			}
		}

		return [
			'migrated' => $migrated,
			'skipped'  => $skipped,
		];
	}

	/**
	 * Migrate simple post meta fields.
	 *
	 * @param int $post_id Post ID.
	 * @return bool Whether any field was migrated.
	 */
	private function migrate_post_fields( int $post_id ): bool {
		$did_migrate = false;

		foreach ( Mappings::POST_META_MAP as $rm_key => $lw_field ) {
			$lw_key   = Options::META_PREFIX . $lw_field;
			$existing = get_post_meta( $post_id, $lw_key, true );

			if ( '' !== $existing && false !== $existing ) {
				continue;
			}

			$value = get_post_meta( $post_id, $rm_key, true );
			if ( '' === $value || false === $value ) {
				continue;
			}

			if ( in_array( $lw_field, [ 'title', 'description' ], true ) ) {
				$value = VariableConverter::convert( $value );
			}

			if ( ! $this->dry_run ) {
				update_post_meta( $post_id, $lw_key, $value );
			}

			$did_migrate = true;
		}

		return $did_migrate;
	}

	/**
	 * Migrate post robots meta (noindex/nofollow).
	 *
	 * @param int $post_id Post ID.
	 * @return bool Whether any robot meta was migrated.
	 */
	private function migrate_post_robots( int $post_id ): bool {
		$robots = get_post_meta( $post_id, 'rank_math_robots', true );

		if ( ! is_array( $robots ) || empty( $robots ) ) {
			return false;
		}

		$did_migrate = false;
		$did_migrate = $this->set_robot_flag( 'post', $post_id, $robots, 'noindex' ) || $did_migrate;
		$did_migrate = $this->set_robot_flag( 'post', $post_id, $robots, 'nofollow' ) || $did_migrate;

		return $did_migrate;
	}

	/**
	 * Migrate term meta.
	 *
	 * @return array{migrated: int, skipped: int}
	 */
	public function migrate_terms(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		$term_ids = $wpdb->get_col(
			"SELECT DISTINCT term_id FROM {$wpdb->termmeta}
			 WHERE meta_key LIKE 'rank_math_%'"
		);

		$migrated = 0;
		$skipped  = 0;

		foreach ( $term_ids as $term_id ) {
			$term_id     = (int) $term_id;
			$did_migrate = $this->migrate_term_fields( $term_id );

			if ( $did_migrate ) {
				++$migrated;
			} else {
				++$skipped;
			}
		}

		return [
			'migrated' => $migrated,
			'skipped'  => $skipped,
		];
	}

	/**
	 * Migrate a single term's meta fields and robots.
	 *
	 * @param int $term_id Term ID.
	 * @return bool Whether any field was migrated.
	 */
	private function migrate_term_fields( int $term_id ): bool {
		$did_migrate = false;

		foreach ( Mappings::POST_META_MAP as $rm_key => $lw_field ) {
			$lw_key   = Options::META_PREFIX . $lw_field;
			$existing = get_term_meta( $term_id, $lw_key, true );

			if ( '' !== $existing && false !== $existing ) {
				continue;
			}

			$value = get_term_meta( $term_id, $rm_key, true );
			if ( '' === $value || false === $value ) {
				continue;
			}

			if ( in_array( $lw_field, [ 'title', 'description' ], true ) ) {
				$value = VariableConverter::convert( $value );
			}

			if ( ! $this->dry_run ) {
				update_term_meta( $term_id, $lw_key, $value );
			}

			$did_migrate = true;
		}

		$robots = get_term_meta( $term_id, 'rank_math_robots', true );
		if ( is_array( $robots ) && ! empty( $robots ) ) {
			$did_migrate = $this->set_robot_flag( 'term', $term_id, $robots, 'noindex' ) || $did_migrate;
		}

		return $did_migrate;
	}

	/**
	 * Migrate user meta.
	 *
	 * @return array{migrated: int}
	 */
	public function migrate_users(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		$user_ids = $wpdb->get_col(
			"SELECT DISTINCT user_id FROM {$wpdb->usermeta}
			 WHERE meta_key LIKE 'rank_math_%'"
		);

		$migrated = 0;

		foreach ( $user_ids as $user_id ) {
			$user_id = (int) $user_id;
			$robots  = get_user_meta( $user_id, 'rank_math_robots', true );

			if ( ! is_array( $robots ) || ! in_array( 'noindex', $robots, true ) ) {
				continue;
			}

			if ( $this->set_robot_flag( 'user', $user_id, $robots, 'noindex' ) ) {
				++$migrated;
			}
		}

		return [ 'migrated' => $migrated ];
	}

	/**
	 * Set a robot flag (noindex/nofollow) for an entity.
	 *
	 * @param string        $type    Entity type: 'post', 'term', or 'user'.
	 * @param int           $id      Entity ID.
	 * @param array<string> $robots  Robots array from RankMath.
	 * @param string        $flag    Flag name ('noindex' or 'nofollow').
	 * @return bool Whether the flag was set.
	 */
	private function set_robot_flag( string $type, int $id, array $robots, string $flag ): bool {
		if ( ! in_array( $flag, $robots, true ) ) {
			return false;
		}

		$meta_key = Options::META_PREFIX . $flag;
		$getter   = 'get_' . $type . '_meta';
		$updater  = 'update_' . $type . '_meta';
		$existing = $getter( $id, $meta_key, true );

		if ( '' !== $existing && false !== $existing ) {
			return false;
		}

		if ( ! $this->dry_run ) {
			$updater( $id, $meta_key, '1' );
		}

		return true;
	}
}
