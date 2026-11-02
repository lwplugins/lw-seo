<?php
/**
 * UrlField unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Helpers;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Helpers\UrlField;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Helpers\UrlField
 */
final class UrlFieldTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Mirrors core's octet stripping, the behaviour UrlField must survive.
		Functions\when( 'sanitize_text_field' )->alias(
			static fn( string $value ): string => trim( (string) preg_replace( '/%[a-f0-9]{2}/i', '', wp_strip_all_tags_stub( $value ) ) )
		);
	}

	public function test_keeps_percent_encoded_octets(): void {
		$this->assertSame( '/tag/caf%C3%A9/', UrlField::clean( '/tag/caf%C3%A9/' ) );
	}

	public function test_still_strips_markup(): void {
		$this->assertSame( '/old', UrlField::clean( ' <b>/old</b> ' ) );
	}
}

/**
 * Tag stripping stand-in for the sanitize_text_field stub.
 *
 * @param string $value Value.
 * @return string
 */
function wp_strip_all_tags_stub( string $value ): string {
	return strip_tags( $value ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Test stub.
}
