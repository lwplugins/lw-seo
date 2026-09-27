<?php
/**
 * Per-object migration result counter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Support;

/**
 * Buckets per-object results into the three counts the migration UI and
 * WP-CLI report:
 *   - migrated:                at least one field was copied
 *   - skipped_already_present: the source had data but every LW SEO target was filled
 *   - skipped_no_data:         nothing actionable in the source row
 */
final class ResultTally {

	/**
	 * Running counts.
	 *
	 * @var array{migrated: int, skipped_already_present: int, skipped_no_data: int}
	 */
	private array $counts = [
		'migrated'                => 0,
		'skipped_already_present' => 0,
		'skipped_no_data'         => 0,
	];

	/**
	 * Add one object's result.
	 *
	 * @param array{migrated: bool, target_full: bool} $result Per-object result.
	 * @return void
	 */
	public function add( array $result ): void {
		if ( $result['migrated'] ) {
			++$this->counts['migrated'];
		} elseif ( $result['target_full'] ) {
			++$this->counts['skipped_already_present'];
		} else {
			++$this->counts['skipped_no_data'];
		}
	}

	/**
	 * Merge several per-object results into one.
	 *
	 * @param array<array{migrated: bool, target_full: bool}> $results Results.
	 * @return array{migrated: bool, target_full: bool}
	 */
	public static function combine( array $results ): array {
		$combined = [
			'migrated'    => false,
			'target_full' => false,
		];

		foreach ( $results as $result ) {
			$combined['migrated']    = $combined['migrated'] || $result['migrated'];
			$combined['target_full'] = $combined['target_full'] || $result['target_full'];
		}

		return $combined;
	}

	/**
	 * The counts.
	 *
	 * @return array{migrated: int, skipped_already_present: int, skipped_no_data: int}
	 */
	public function to_array(): array {
		return $this->counts;
	}
}
