<?php
/**
 * RankMath term-meta migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\RankMath;

use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\Migration\VariableConverter;
use LightweightPlugins\SEO\Options;

/**
 * Migrates RankMath term meta to LW SEO term meta with honest accounting.
 *
 * Term meta uses the same source/target shape as post meta, plus the
 * rank_math_robots flag — see PostMetaMigrator for bucket semantics.
 */
final class TermMetaMigrator {

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
	 * Run term meta migration.
	 *
	 * @return array{migrated: int, skipped_already_present: int, skipped_no_data: int}
	 */
	public function migrate(): array {
		$counts = [
			'migrated'                => 0,
			'skipped_already_present' => 0,
			'skipped_no_data'         => 0,
		];

		foreach ( $this->find_term_ids() as $term_id ) {
			$result = $this->migrate_term( (int) $term_id );

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
	 * Find term IDs with any rank_math_* meta.
	 *
	 * @return array<string>
	 */
	private function find_term_ids(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		return (array) $wpdb->get_col(
			"SELECT DISTINCT term_id FROM {$wpdb->termmeta}
			 WHERE meta_key LIKE 'rank_math_%'"
		);
	}

	/**
	 * Migrate a single term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{migrated: bool, target_full: bool}
	 */
	private function migrate_term( int $term_id ): array {
		$fields = $this->migrate_fields( $term_id );
		$robots = $this->robots->migrate( 'term', $term_id );

		return [
			'migrated'    => $fields['migrated'] || $robots['migrated'],
			'target_full' => $fields['target_full'] || $robots['target_full'],
		];
	}

	/**
	 * Migrate mapped simple fields for a term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{migrated: bool, target_full: bool}
	 */
	private function migrate_fields( int $term_id ): array {
		$migrated    = false;
		$target_full = false;

		foreach ( Mappings::POST_META_MAP as $rm_key => $lw_field ) {
			$value = get_term_meta( $term_id, $rm_key, true );
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
			$existing = get_term_meta( $term_id, $lw_key, true );

			if ( '' !== $existing && false !== $existing ) {
				$target_full = true;
				continue;
			}

			if ( in_array( $lw_field, [ 'title', 'description' ], true ) ) {
				$value = VariableConverter::convert( $value );
			}

			if ( ! $this->dry_run ) {
				update_term_meta( $term_id, $lw_key, $value );
			}

			$migrated = true;
		}

		return [
			'migrated'    => $migrated,
			'target_full' => $target_full,
		];
	}
}
