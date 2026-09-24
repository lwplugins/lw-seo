<?php
/**
 * Opening hours option keys.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Local;

/**
 * The per-day opening hours options (local_hours_{day}_closed|open|close)
 * read by Local\Schema and Local\Shortcodes. Times are stored as 'HH:MM'
 * (24-hour clock), the format schema.org's opens/closes expects.
 */
final class OpeningHours {

	/**
	 * Day keys, Monday first.
	 */
	public const DAYS = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];

	/**
	 * Option defaults: closed flag plus open and close time for every day.
	 *
	 * @return array<string, bool|string>
	 */
	public static function option_defaults(): array {
		$defaults = [];

		foreach ( self::DAYS as $day ) {
			$defaults[ "local_hours_{$day}_closed" ] = false;
			$defaults[ "local_hours_{$day}_open" ]   = '';
			$defaults[ "local_hours_{$day}_close" ]  = '';
		}

		return $defaults;
	}

	/**
	 * Whether an option key holds an opening or closing time.
	 *
	 * @param string $key Option key.
	 * @return bool
	 */
	public static function is_time_key( string $key ): bool {
		return 1 === preg_match( '/^local_hours_(' . implode( '|', self::DAYS ) . ')_(open|close)$/', $key );
	}

	/**
	 * Normalise a time to 'HH:MM' (00:00-23:59), '' when invalid.
	 *
	 * Accepts a single-digit hour and a trailing ':SS' (what some browsers
	 * send from a time input) and drops the seconds.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_time( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		if ( 1 !== preg_match( '/^(\d{1,2}):(\d{2})(?::\d{2})?$/', trim( $value ), $match ) ) {
			return '';
		}

		$hour   = (int) $match[1];
		$minute = (int) $match[2];

		if ( $hour > 23 || $minute > 59 ) {
			return '';
		}

		return sprintf( '%02d:%02d', $hour, $minute );
	}
}
