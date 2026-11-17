<?php
/**
 * Parser for the All in One SEO redirects JSON export.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Redirects;

/**
 * Turns the export (a JSON list of redirect objects) into plain redirect
 * rows. Only source_url, target_url, type, regex and enabled are read; the
 * other fields (group, query_param, ignore_case, …) have no equivalent and
 * are ignored. Validation is left to the importer, so every row goes
 * through the same checks as a CSV line.
 */
final class AioseoJson {

	/**
	 * Parse the export.
	 *
	 * @param string $json File content.
	 * @return array{rows: array<int, array{source: string, destination: string, type: int, regex: bool}>, disabled: int}|null
	 *         Rows keyed by their 1-based position in the file; null when the
	 *         content is not a JSON list.
	 */
	public static function parse( string $json ): ?array {
		$items = json_decode( self::strip_bom( $json ), true );
		if ( ! is_array( $items ) || ( [] !== $items && array_keys( $items ) !== range( 0, count( $items ) - 1 ) ) ) {
			return null;
		}

		$rows     = [];
		$disabled = 0;
		foreach ( array_values( $items ) as $index => $item ) {
			$item = is_array( $item ) ? $item : [];
			if ( array_key_exists( 'enabled', $item ) && ! self::flag( $item['enabled'] ) ) {
				++$disabled;
				continue;
			}

			$rows[ $index + 1 ] = [
				'source'      => self::text( $item['source_url'] ?? '' ),
				'destination' => self::text( $item['target_url'] ?? '' ),
				'type'        => is_numeric( $item['type'] ?? null ) ? (int) $item['type'] : 301,
				'regex'       => self::flag( $item['regex'] ?? false ),
			];
		}

		return [
			'rows'     => $rows,
			'disabled' => $disabled,
		];
	}

	/**
	 * Whether an export flag ("1", 1, true) is set.
	 *
	 * @param mixed $value Flag value.
	 * @return bool
	 */
	private static function flag( mixed $value ): bool {
		return in_array( is_string( $value ) ? strtolower( trim( $value ) ) : $value, [ '1', 'true', 1, true ], true );
	}

	/**
	 * A scalar field as trimmed text.
	 *
	 * @param mixed $value Field value.
	 * @return string
	 */
	private static function text( mixed $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Drop a UTF-8 byte order mark and surrounding whitespace.
	 *
	 * @param string $json File content.
	 * @return string
	 */
	private static function strip_bom( string $json ): string {
		return trim( str_starts_with( $json, "\xEF\xBB\xBF" ) ? substr( $json, 3 ) : $json );
	}
}
