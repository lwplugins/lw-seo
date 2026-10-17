<?php
/**
 * CommerceSanitizer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Admin;

use LightweightPlugins\SEO\Admin\CommerceSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\SEO\Admin\CommerceSanitizer
 */
final class CommerceSanitizerTest extends TestCase {

	public function test_countries(): void {
		$this->assertSame( 'HU,DE,AT', CommerceSanitizer::countries( 'hu, de  at,HU,x,123,' ) );
		$this->assertSame( '', CommerceSanitizer::countries( '' ) );
	}

	public function test_decimal_comma(): void {
		$this->assertSame( '12.5', CommerceSanitizer::decimal( '12,5' ) );
		$this->assertSame( '990', CommerceSanitizer::decimal( ' 990 ' ) );
	}

	public function test_decimal_invalid_empty(): void {
		foreach ( [ 'abc', '-3', '1.234', '1,2,3', '' ] as $bad ) {
			$this->assertSame( '', CommerceSanitizer::decimal( $bad ), $bad );
		}
	}

	public function test_days_capped(): void {
		$this->assertSame( 365, CommerceSanitizer::days( '9999' ) );
		$this->assertSame( 5, CommerceSanitizer::days( '-5' ) );
		$this->assertSame( 0, CommerceSanitizer::days( 'x' ) );
	}

	public function test_value_dispatch(): void {
		$this->assertSame( 'HU', CommerceSanitizer::value( 'woo_return_country', 'hu,de', '' ) );
		$this->assertSame( 14, CommerceSanitizer::value( 'woo_return_days', null, 14 ) );
		$this->assertSame( '', CommerceSanitizer::value( 'woo_shipping_rate', null, '' ) );
	}
}
