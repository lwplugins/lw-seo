<?php
/**
 * RankMath redirects DB migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\RankMath;

use LightweightPlugins\SEO\Redirects\Manager;

/**
 * Migrates {prefix}rank_math_redirections rows into the LW SEO redirects option.
 *
 * RankMath stores redirects in a custom DB table with a serialized 'sources'
 * column — each source has 'pattern', 'comparison' (exact|regex|contains|
 * start|end) and optional 'ignore' (case). One row may have multiple sources;
 * each source becomes a separate LW SEO redirect entry.
 *
 * Trashed rows are skipped; inactive rows are migrated (with a note in the
 * source field) so users can re-enable them later.
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
	 * Check whether the RankMath redirects table exists.
	 *
	 * @return bool
	 */
	public function table_exists(): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'rank_math_redirections';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation is safe (controlled prefix + literal suffix).
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	/**
	 * Count migratable redirects (active + inactive, excluding trashed).
	 *
	 * @return int
	 */
	public function count(): int {
		global $wpdb;
		if ( ! $this->table_exists() ) {
			return 0;
		}
		$table = $wpdb->prefix . 'rank_math_redirections';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation is safe (controlled prefix + literal suffix).
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status != 'trashed'" );
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

		if ( ! $this->table_exists() ) {
			return $result;
		}

		foreach ( $this->fetch_rows() as $row ) {
			$this->migrate_row( $row, $result );
		}

		return $result;
	}

	/**
	 * Fetch all non-trashed rows from the RankMath redirects table.
	 *
	 * @return array<array<string, mixed>>
	 */
	private function fetch_rows(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'rank_math_redirections';
		$sql   = "SELECT id, sources, url_to, header_code, status FROM {$table} WHERE status != 'trashed'"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation is safe (controlled prefix + literal suffix).

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- One-time migration query; $sql contains only literal SQL + a trusted table name.
		return (array) $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Migrate a single redirect row into one or more LW SEO redirects.
	 *
	 * @param array<string, mixed>                                      $row    Row from rank_math_redirections.
	 * @param array{migrated: int, skipped: int, errors: array<string>} $result Result accumulator (by reference).
	 * @return void
	 */
	private function migrate_row( array $row, array &$result ): void {
		$sources = maybe_unserialize( (string) ( $row['sources'] ?? '' ) );
		if ( ! is_array( $sources ) || empty( $sources ) ) {
			++$result['skipped'];
			$result['errors'][] = sprintf( 'Row #%d: no parseable sources', (int) $row['id'] );
			return;
		}

		$type = (int) ( $row['header_code'] ?? 301 );
		$dest = (string) ( $row['url_to'] ?? '' );

		foreach ( $sources as $source ) {
			$converted = $this->convert_source( $source );
			if ( null === $converted ) {
				++$result['skipped'];
				continue;
			}

			if ( $this->insert_redirect( $converted['pattern'], $dest, $type, $converted['regex'] ) ) {
				++$result['migrated'];
			} else {
				++$result['skipped'];
			}
		}
	}

	/**
	 * Convert a RankMath source spec into a (pattern, regex) tuple.
	 *
	 * @param mixed $source Single source spec from the unserialized array.
	 * @return array{pattern: string, regex: bool}|null Null if unrecognized.
	 */
	private function convert_source( mixed $source ): ?array {
		if ( ! is_array( $source ) || empty( $source['pattern'] ) ) {
			return null;
		}

		$pattern    = (string) $source['pattern'];
		$comparison = (string) ( $source['comparison'] ?? 'exact' );

		if ( ! isset( Mappings::REDIRECT_COMPARISON_MAP[ $comparison ] ) ) {
			return null;
		}

		$regex = Mappings::REDIRECT_COMPARISON_MAP[ $comparison ];

		switch ( $comparison ) {
			case 'contains':
				$pattern = '.*' . preg_quote( $pattern, '@' ) . '.*';
				break;
			case 'start':
				$pattern = '^' . preg_quote( $pattern, '@' );
				break;
			case 'end':
				$pattern = preg_quote( $pattern, '@' ) . '$';
				break;
		}

		return [
			'pattern' => $pattern,
			'regex'   => $regex,
		];
	}

	/**
	 * Insert a single redirect via the LW SEO Manager (unless dry run).
	 *
	 * @param string $source      Source pattern.
	 * @param string $destination Destination URL.
	 * @param int    $type        HTTP code (301/302/307/410/451).
	 * @param bool   $regex       Whether the source is a regex pattern.
	 * @return bool True if inserted (or would have been, in dry run).
	 */
	private function insert_redirect( string $source, string $destination, int $type, bool $regex ): bool {
		if ( $this->dry_run ) {
			return ! empty( $source );
		}
		return false !== Manager::add( $source, $destination, $type, $regex );
	}
}
