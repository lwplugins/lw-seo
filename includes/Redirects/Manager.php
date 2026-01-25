<?php
/**
 * Redirect Manager class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Redirects;

/**
 * Handles redirect storage and retrieval.
 */
final class Manager {

	/**
	 * Option name for storing redirects.
	 */
	public const OPTION_NAME = 'lw_seo_redirects';

	/**
	 * Supported redirect types.
	 */
	public const TYPES = [
		301 => 'Moved Permanently',
		302 => 'Found (Temporary)',
		307 => 'Temporary Redirect',
		410 => 'Content Deleted',
		451 => 'Unavailable For Legal Reasons',
	];

	/**
	 * Get all redirects.
	 *
	 * @return array<int, array{source: string, destination: string, type: int, regex: bool, hits: int, last_accessed: string}>
	 */
	public static function get_all(): array {
		$redirects = get_option( self::OPTION_NAME, [] );
		return is_array( $redirects ) ? $redirects : [];
	}

	/**
	 * Get a single redirect by ID.
	 *
	 * @param int $id Redirect ID (array index).
	 * @return array{source: string, destination: string, type: int, regex: bool, hits: int, last_accessed: string}|null
	 */
	public static function get( int $id ): ?array {
		$redirects = self::get_all();
		return $redirects[ $id ] ?? null;
	}

	/**
	 * Add a new redirect.
	 *
	 * @param string $source      Source URL/path.
	 * @param string $destination Destination URL (empty for 410/451).
	 * @param int    $type        Redirect type (301, 302, 307, 410, 451).
	 * @param bool   $regex       Whether source is a regex pattern.
	 * @return int|false The new redirect ID or false on failure.
	 */
	public static function add( string $source, string $destination, int $type = 301, bool $regex = false ) {
		if ( empty( $source ) ) {
			return false;
		}

		// Validate type.
		if ( ! isset( self::TYPES[ $type ] ) ) {
			$type = 301;
		}

		// 410 and 451 don't need destination.
		if ( ! in_array( $type, [ 410, 451 ], true ) && empty( $destination ) ) {
			return false;
		}

		$redirects   = self::get_all();
		$redirects[] = [
			'source'        => self::normalize_source( $source ),
			'destination'   => $destination,
			'type'          => $type,
			'regex'         => $regex,
			'hits'          => 0,
			'last_accessed' => '',
			'created'       => current_time( 'mysql' ),
		];

		$new_id = array_key_last( $redirects );
		update_option( self::OPTION_NAME, $redirects, false );

		return $new_id;
	}

	/**
	 * Update an existing redirect.
	 *
	 * @param int    $id          Redirect ID.
	 * @param string $source      Source URL/path.
	 * @param string $destination Destination URL.
	 * @param int    $type        Redirect type.
	 * @param bool   $regex       Whether source is a regex pattern.
	 * @return bool Success.
	 */
	public static function update( int $id, string $source, string $destination, int $type = 301, bool $regex = false ): bool {
		$redirects = self::get_all();

		if ( ! isset( $redirects[ $id ] ) ) {
			return false;
		}

		if ( empty( $source ) ) {
			return false;
		}

		if ( ! isset( self::TYPES[ $type ] ) ) {
			$type = 301;
		}

		$redirects[ $id ]['source']      = self::normalize_source( $source );
		$redirects[ $id ]['destination'] = $destination;
		$redirects[ $id ]['type']        = $type;
		$redirects[ $id ]['regex']       = $regex;

		return update_option( self::OPTION_NAME, $redirects, false );
	}

	/**
	 * Delete a redirect.
	 *
	 * @param int $id Redirect ID.
	 * @return bool Success.
	 */
	public static function delete( int $id ): bool {
		$redirects = self::get_all();

		if ( ! isset( $redirects[ $id ] ) ) {
			return false;
		}

		unset( $redirects[ $id ] );
		// Re-index array.
		$redirects = array_values( $redirects );

		return update_option( self::OPTION_NAME, $redirects, false );
	}

	/**
	 * Delete all redirects.
	 *
	 * @return bool Success.
	 */
	public static function delete_all(): bool {
		return update_option( self::OPTION_NAME, [], false );
	}

	/**
	 * Record a hit for a redirect.
	 *
	 * @param int $id Redirect ID.
	 * @return void
	 */
	public static function record_hit( int $id ): void {
		$redirects = self::get_all();

		if ( ! isset( $redirects[ $id ] ) ) {
			return;
		}

		++$redirects[ $id ]['hits'];
		$redirects[ $id ]['last_accessed'] = current_time( 'mysql' );

		update_option( self::OPTION_NAME, $redirects, false );
	}

	/**
	 * Find a redirect matching the given URL.
	 *
	 * @param string $url URL to match.
	 * @return array{id: int, redirect: array}|null
	 */
	public static function find_match( string $url ): ?array {
		$redirects = self::get_all();
		$path      = self::normalize_source( $url );

		foreach ( $redirects as $id => $redirect ) {
			if ( $redirect['regex'] ) {
				// Regex match.
				$pattern = '@' . str_replace( '@', '\\@', $redirect['source'] ) . '@i';
				if ( preg_match( $pattern, $path ) ) {
					return [
						'id'       => $id,
						'redirect' => $redirect,
					];
				}
			} elseif ( $redirect['source'] === $path ) {
				// Exact match.
				return [
					'id'       => $id,
					'redirect' => $redirect,
				];
			}
		}

		return null;
	}

	/**
	 * Normalize source URL to path only.
	 *
	 * @param string $source Source URL or path.
	 * @return string Normalized path.
	 */
	public static function normalize_source( string $source ): string {
		// Remove site URL if present.
		$site_url = home_url();
		if ( str_starts_with( $source, $site_url ) ) {
			$source = substr( $source, strlen( $site_url ) );
		}

		// Ensure leading slash.
		if ( ! str_starts_with( $source, '/' ) ) {
			$source = '/' . $source;
		}

		// Remove trailing slash (except for root).
		if ( '/' !== $source ) {
			$source = rtrim( $source, '/' );
		}

		return $source;
	}

	/**
	 * Import redirects from CSV.
	 *
	 * @param string $csv_content CSV content.
	 * @return array{imported: int, skipped: int, errors: array<string>}
	 */
	public static function import_csv( string $csv_content ): array {
		$result = [
			'imported' => 0,
			'skipped'  => 0,
			'errors'   => [],
		];

		$lines = explode( "\n", $csv_content );

		foreach ( $lines as $line_num => $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			// Skip header row.
			if ( 0 === $line_num && str_contains( strtolower( $line ), 'source' ) ) {
				continue;
			}

			$parts = str_getcsv( $line );
			if ( count( $parts ) < 2 ) {
				$result['errors'][] = sprintf( 'Line %d: Invalid format', $line_num + 1 );
				++$result['skipped'];
				continue;
			}

			$source      = $parts[0] ?? '';
			$destination = $parts[1] ?? '';
			$type        = isset( $parts[2] ) ? (int) $parts[2] : 301;
			$regex       = isset( $parts[3] ) && in_array( strtolower( $parts[3] ), [ '1', 'true', 'yes' ], true );

			if ( empty( $source ) ) {
				$result['errors'][] = sprintf( 'Line %d: Empty source', $line_num + 1 );
				++$result['skipped'];
				continue;
			}

			$added = self::add( $source, $destination, $type, $regex );
			if ( false !== $added ) {
				++$result['imported'];
			} else {
				++$result['skipped'];
			}
		}

		return $result;
	}

	/**
	 * Export redirects to CSV.
	 *
	 * @return string CSV content.
	 */
	public static function export_csv(): string {
		$redirects = self::get_all();
		$csv       = "source,destination,type,regex\n";

		foreach ( $redirects as $redirect ) {
			$csv .= sprintf(
				'"%s","%s",%d,%s' . "\n",
				str_replace( '"', '""', $redirect['source'] ),
				str_replace( '"', '""', $redirect['destination'] ),
				$redirect['type'],
				$redirect['regex'] ? 'true' : 'false'
			);
		}

		return $csv;
	}

	/**
	 * Get redirect count.
	 *
	 * @return int Number of redirects.
	 */
	public static function count(): int {
		return count( self::get_all() );
	}
}
