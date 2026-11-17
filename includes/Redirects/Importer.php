<?php
/**
 * Redirect import (CSV and All in One SEO JSON).
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Redirects;

/**
 * Imports redirects from a file's text. Every row, whatever the format,
 * goes through add_row(): the same Validator and DuplicateSource checks as
 * a redirect added by hand.
 */
final class Importer {

	/**
	 * Import a CSV or All in One SEO JSON export.
	 *
	 * JSON is recognised by a .json file name or by content that starts
	 * with a JSON list; anything else is read as CSV.
	 *
	 * @param string $content  File content.
	 * @param string $filename Original file name, if known.
	 * @return array{imported: int, skipped: int, errors: array<string>}
	 * @throws \InvalidArgumentException When a JSON file cannot be parsed; nothing is imported then.
	 */
	public static function import( string $content, string $filename = '' ): array {
		return self::is_json( $content, $filename ) ? self::import_json( $content ) : self::import_csv( $content );
	}

	/**
	 * Import CSV lines (source,destination,type,regex; header optional).
	 *
	 * @param string $csv_content CSV content.
	 * @return array{imported: int, skipped: int, errors: array<string>}
	 */
	public static function import_csv( string $csv_content ): array {
		$result = self::empty_result();

		foreach ( explode( "\n", $csv_content ) as $line_num => $line ) {
			$line = trim( $line );
			if ( '' === $line || ( 0 === $line_num && str_contains( strtolower( $line ), 'source' ) ) ) {
				continue;
			}

			$parts = str_getcsv( $line, ',', '"', '\\' );
			$error = count( $parts ) < 2
				? __( 'Invalid format.', 'lw-seo' )
				: self::add_row( (string) $parts[0], (string) $parts[1], isset( $parts[2] ) ? (int) $parts[2] : 301, self::csv_flag( (string) ( $parts[3] ?? '' ) ) );

			if ( null === $error ) {
				++$result['imported'];
				continue;
			}

			/* translators: 1: CSV line number, 2: reason the line was skipped */
			$result['errors'][] = sprintf( __( 'Line %1$d: %2$s', 'lw-seo' ), $line_num + 1, $error );
			++$result['skipped'];
		}

		return $result;
	}

	/**
	 * Import an All in One SEO redirects export. Disabled redirects are
	 * skipped without an error message.
	 *
	 * @param string $json JSON content.
	 * @return array{imported: int, skipped: int, errors: array<string>}
	 * @throws \InvalidArgumentException When the content is not a JSON list.
	 */
	public static function import_json( string $json ): array {
		$parsed = AioseoJson::parse( $json );
		if ( null === $parsed ) {
			throw new \InvalidArgumentException( esc_html__( 'The file is not a valid All in One SEO redirects export (JSON list).', 'lw-seo' ) );
		}

		$result            = self::empty_result();
		$result['skipped'] = $parsed['disabled'];

		foreach ( $parsed['rows'] as $position => $row ) {
			$error = self::add_row( $row['source'], $row['destination'], $row['type'], $row['regex'] );
			if ( null === $error ) {
				++$result['imported'];
				continue;
			}

			/* translators: 1: position of the redirect in the JSON file, 2: reason it was skipped */
			$result['errors'][] = sprintf( __( 'Item %1$d: %2$s', 'lw-seo' ), $position, $error );
			++$result['skipped'];
		}

		return $result;
	}

	/**
	 * Validate and store one redirect.
	 *
	 * @param string $source      Source path or pattern.
	 * @param string $destination Destination URL.
	 * @param int    $type        Redirect type.
	 * @param bool   $regex       Whether the source is a regex.
	 * @return string|null Why the row was skipped, null when it was added.
	 */
	private static function add_row( string $source, string $destination, int $type, bool $regex ): ?string {
		$error = Validator::error( $source, $destination, $type, $regex );
		if ( null === $error && null !== DuplicateSource::find( Manager::get_all(), $source, $regex ) ) {
			$error = DuplicateSource::message();
		}

		if ( null === $error && false === Manager::add( $source, $destination, $type, $regex ) ) {
			$error = __( 'Could not be saved.', 'lw-seo' );
		}

		return $error;
	}

	/**
	 * Whether the content is JSON.
	 *
	 * @param string $content  File content.
	 * @param string $filename File name.
	 * @return bool
	 */
	private static function is_json( string $content, string $filename ): bool {
		if ( str_ends_with( strtolower( $filename ), '.json' ) ) {
			return true;
		}

		return str_starts_with( ltrim( $content, "\xEF\xBB\xBF \t\r\n" ), '[' );
	}

	/**
	 * Whether a CSV regex column is truthy.
	 *
	 * @param string $value Column value.
	 * @return bool
	 */
	private static function csv_flag( string $value ): bool {
		return in_array( strtolower( trim( $value ) ), [ '1', 'true', 'yes' ], true );
	}

	/**
	 * A result with nothing imported yet.
	 *
	 * @return array{imported: int, skipped: int, errors: array<string>}
	 */
	private static function empty_result(): array {
		return [
			'imported' => 0,
			'skipped'  => 0,
			'errors'   => [],
		];
	}
}
