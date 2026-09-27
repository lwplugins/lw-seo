<?php
/**
 * Yoast Premium redirects migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Migration\Support\RedirectWriter;

/**
 * Migrates Yoast Premium redirects (wpseo-premium-redirects-base) into LW SEO.
 * Yoast free has no redirects, so this is a no-op there. Sources LW SEO
 * already redirects are skipped, so a repeated import adds no duplicates.
 */
final class RedirectsMigrator {

	/**
	 * Redirect writer.
	 *
	 * @var RedirectWriter
	 */
	private RedirectWriter $writer;

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run Whether to simulate without making changes.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->writer = new RedirectWriter( $dry_run );
	}

	/**
	 * Count migratable Yoast Premium redirects.
	 *
	 * @return int
	 */
	public function count(): int {
		$data = get_option( 'wpseo-premium-redirects-base', [] );
		return is_array( $data ) ? count( $data ) : 0;
	}

	/**
	 * Run redirects migration.
	 *
	 * @return array{migrated: int, skipped: int, skipped_already_present: int, errors: array<string>}
	 */
	public function migrate(): array {
		$result = [
			'migrated'                => 0,
			'skipped'                 => 0,
			'skipped_already_present' => 0,
			'errors'                  => [],
		];

		$data = get_option( 'wpseo-premium-redirects-base', [] );
		if ( ! is_array( $data ) ) {
			return $result;
		}

		foreach ( $data as $entry ) {
			$this->migrate_entry( $entry, $result );
		}

		return $result;
	}

	/**
	 * Migrate a single redirect entry.
	 *
	 * @param mixed                                                                                   $entry  Redirect entry.
	 * @param array{migrated: int, skipped: int, skipped_already_present: int, errors: array<string>} $result Accumulator (by reference).
	 * @return void
	 */
	private function migrate_entry( mixed $entry, array &$result ): void {
		if ( ! is_array( $entry ) || empty( $entry['origin'] ) ) {
			++$result['skipped'];
			return;
		}

		$outcome = $this->writer->add(
			(string) $entry['origin'],
			(string) ( $entry['url'] ?? '' ),
			(int) ( $entry['type'] ?? 301 ),
			isset( $entry['format'] ) && 'regex' === $entry['format']
		);
		RedirectWriter::tally( $outcome, $result );
	}
}
