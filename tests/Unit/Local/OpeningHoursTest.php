<?php
/**
 * OpeningHours unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Local;

use LightweightPlugins\SEO\Local\OpeningHours;
use PHPUnit\Framework\TestCase;

final class OpeningHoursTest extends TestCase {

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function time_provider(): array {
		return [
			'valid'               => [ '09:30', '09:30' ],
			'single-digit hour'   => [ '9:05', '09:05' ],
			'seconds dropped'     => [ '18:00:00', '18:00' ],
			'midnight'            => [ '00:00', '00:00' ],
			'last minute'         => [ '23:59', '23:59' ],
			'hour out of range'   => [ '24:00', '' ],
			'minute out of range' => [ '12:60', '' ],
			'empty'               => [ '', '' ],
			'text'                => [ '9am', '' ],
			'not a string'        => [ 930, '' ],
			'null'                => [ null, '' ],
		];
	}

	/**
	 * @dataProvider time_provider
	 *
	 * @param mixed  $value    Raw value.
	 * @param string $expected Normalised time.
	 */
	public function test_sanitize_time_normalises_to_hh_mm( $value, string $expected ): void {
		$this->assertSame( $expected, OpeningHours::sanitize_time( $value ) );
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function key_provider(): array {
		return [
			'open'          => [ 'local_hours_monday_open', true ],
			'close'         => [ 'local_hours_sunday_close', true ],
			'closed flag'   => [ 'local_hours_monday_closed', false ],
			'enabled flag'  => [ 'local_hours_enabled', false ],
			'unknown day'   => [ 'local_hours_funday_open', false ],
		];
	}

	/**
	 * @dataProvider key_provider
	 *
	 * @param string $key      Option key.
	 * @param bool   $expected Whether it is a time key.
	 */
	public function test_is_time_key_matches_only_open_and_close_keys( string $key, bool $expected ): void {
		$this->assertSame( $expected, OpeningHours::is_time_key( $key ) );
	}

	public function test_option_defaults_cover_every_day(): void {
		$defaults = OpeningHours::option_defaults();

		$this->assertCount( 21, $defaults );
		$this->assertFalse( $defaults['local_hours_wednesday_closed'] );
		$this->assertSame( '', $defaults['local_hours_wednesday_open'] );
		$this->assertSame( '', $defaults['local_hours_wednesday_close'] );
	}
}
