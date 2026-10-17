<?php
/**
 * Key unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\IndexNow;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\IndexNow\Key;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class KeyTest extends MonkeyTestCase {

	public function test_format(): void {
		$saved = null;
		Functions\when( 'get_option' )->justReturn( '' );
		Functions\when( 'update_option' )->alias(
			static function ( string $name, $value ) use ( &$saved ): bool {
				$saved = [ $name, $value ];
				return true;
			}
		);

		$key = Key::regenerate();

		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $key );
		$this->assertSame( [ 'lw_seo_indexnow_key', $key ], $saved );
	}

	public function test_ensure_idempotent(): void {
		$existing = str_repeat( 'ab', 16 );
		Functions\when( 'get_option' )->justReturn( $existing );
		Functions\expect( 'update_option' )->never();

		$this->assertSame( $existing, Key::ensure() );
		$this->assertSame( $existing, Key::ensure() );
	}

	public function test_ensure_generates_when_malformed(): void {
		Functions\when( 'get_option' )->justReturn( 'nope' );
		Functions\expect( 'update_option' )->once()->andReturn( true );

		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', Key::ensure() );
	}

	public function test_location(): void {
		$key = str_repeat( '0f', 16 );
		Functions\when( 'get_option' )->justReturn( $key );
		Functions\when( 'home_url' )->alias( static fn( string $path = '' ): string => 'https://example.com' . $path );

		$this->assertSame( 'https://example.com/' . $key . '.txt', Key::location() );
	}
}
