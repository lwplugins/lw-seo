<?php
/**
 * Yoast Premium redirects migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Redirects\Manager;

/**
 * Migrates Yoast Premium redirects (wpseo-premium-redirects-base) into LW SEO.
 * Yoast free has no redirects, so this is a no-op there.
 */
final class RedirectsMigrator {

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
	 * @return array{migrated: int, skipped: int, errors: array<string>}
	 */
	public function migrate(): array {
		$result = [
			'migrated' => 0,
			'skipped'  => 0,
			'errors'   => [],
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
	 * @param mixed                                                     $entry  Redirect entry.
	 * @param array{migrated: int, skipped: int, errors: array<string>} $result Accumulator (by reference).
	 * @return void
	 */
	private function migrate_entry( mixed $entry, array &$result ): void {
		if ( ! is_array( $entry ) || empty( $entry['origin'] ) ) {
			++$result['skipped'];
			return;
		}

		$source = (string) $entry['origin'];
		$dest   = (string) ( $entry['url'] ?? '' );
		$type   = (int) ( $entry['type'] ?? 301 );
		$regex  = isset( $entry['format'] ) && 'regex' === $entry['format'];

		if ( $this->dry_run ) {
			++$result['migrated'];
			return;
		}

		if ( false !== Manager::add( $source, $dest, $type, $regex ) ) {
			++$result['migrated'];
		} else {
			++$result['skipped'];
		}
	}
}
