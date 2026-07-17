<?php
/**
 * Yoast term-meta migrator (reads the wpseo_taxonomy_meta option).
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\Options;

/**
 * Migrates Yoast term SEO from the wpseo_taxonomy_meta option into LW SEO term
 * meta. Yoast stores taxonomy SEO as [taxonomy][term_id][field], NOT in the
 * termmeta table.
 */
final class TermMetaMigrator {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Robots sub-migrator.
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

		$data = get_option( 'wpseo_taxonomy_meta', [] );
		if ( ! is_array( $data ) ) {
			return $counts;
		}

		foreach ( $data as $terms ) {
			if ( ! is_array( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term_id => $fields ) {
				if ( ! is_array( $fields ) || ! get_term( (int) $term_id ) ) {
					continue;
				}
				$this->bucket( $counts, $this->migrate_term( (int) $term_id, $fields ) );
			}
		}

		return $counts;
	}

	/**
	 * Bucket a per-term result into running counts.
	 *
	 * @param array{migrated: int, skipped_already_present: int, skipped_no_data: int} $counts Counts by reference.
	 * @param array{migrated: bool, target_full: bool}                                 $result Per-term result.
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
	 * Migrate a single term's Yoast fields.
	 *
	 * @param int                  $term_id Term ID.
	 * @param array<string, mixed> $fields  Yoast field array for this term.
	 * @return array{migrated: bool, target_full: bool}
	 */
	private function migrate_term( int $term_id, array $fields ): array {
		$result = $this->migrate_fields( $term_id, $fields );

		$noindex = isset( $fields['wpseo_noindex'] ) && 'noindex' === $fields['wpseo_noindex'];
		$robots  = $this->robots->apply_flags( 'term', $term_id, $noindex, false );

		return [
			'migrated'    => $result['migrated'] || $robots['migrated'],
			'target_full' => $result['target_full'] || $robots['target_full'],
		];
	}

	/**
	 * Migrate mapped simple fields for a term.
	 *
	 * @param int                  $term_id Term ID.
	 * @param array<string, mixed> $fields  Yoast field array.
	 * @return array{migrated: bool, target_full: bool}
	 */
	private function migrate_fields( int $term_id, array $fields ): array {
		$migrated    = false;
		$target_full = false;

		foreach ( Mappings::TERM_META_MAP as $yoast_key => $lw_field ) {
			$value = $fields[ $yoast_key ] ?? '';
			if ( ! is_string( $value ) || '' === $value ) {
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
