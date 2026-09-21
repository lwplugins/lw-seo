<?php
/**
 * SignalValue unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use LightweightPlugins\SEO\SignalValue;

final class SignalValueTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function value_provider(): array {
		return [
			'yes'          => [ 'yes', 'yes' ],
			'no uppercase' => [ ' NO ', 'no' ],
			'default'      => [ 'default', '' ],
			'empty'        => [ '', '' ],
			'garbage'      => [ '<script>', '' ],
			'legacy true'  => [ true, 'yes' ],
			'legacy false' => [ false, 'no' ],
			'null'         => [ null, '' ],
		];
	}

	/**
	 * @dataProvider value_provider
	 *
	 * @param mixed  $value    Input.
	 * @param string $expected Normalised signal.
	 */
	public function test_sanitize( $value, string $expected ): void {
		$this->assertSame( $expected, SignalValue::sanitize( $value ) );
	}
}
