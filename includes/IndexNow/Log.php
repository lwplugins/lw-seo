<?php
/**
 * IndexNow submission log.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\IndexNow;

/**
 * Last 20 submissions, oldest first.
 */
final class Log {

	/**
	 * Option that stores the log.
	 */
	public const OPTION = 'lw_seo_indexnow_log';

	/**
	 * Entries kept.
	 */
	public const LIMIT = 20;

	/**
	 * Append an entry: {time, count, code, ok, message, source}.
	 *
	 * @param array<string, mixed> $entry Entry.
	 * @return void
	 */
	public static function add( array $entry ): void {
		$entries   = self::all();
		$entries[] = $entry;

		update_option( self::OPTION, array_slice( $entries, -self::LIMIT ), false );
	}

	/**
	 * Stored entries, oldest first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all(): array {
		$entries = get_option( self::OPTION, [] );

		return is_array( $entries ) ? array_values( $entries ) : [];
	}
}
