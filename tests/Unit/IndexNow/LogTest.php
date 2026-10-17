<?php
/**
 * Log unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\IndexNow;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\IndexNow\Log;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class LogTest extends MonkeyTestCase {

	public function test_capped_at_20(): void {
		$store = [];
		Functions\when( 'get_option' )->alias( static function () use ( &$store ) {
			return $store;
		} );
		Functions\when( 'update_option' )->alias(
			static function ( string $name, $value, $autoload ) use ( &$store ): bool {
				$store = $value;
				return false === $autoload;
			}
		);

		for ( $i = 1; $i <= 25; $i++ ) {
			Log::add( [ 'time' => $i, 'count' => 1, 'code' => 200, 'ok' => true, 'message' => 'OK', 'source' => 'auto' ] );
		}

		$all = Log::all();
		$this->assertCount( 20, $all );
		$this->assertSame( 6, $all[0]['time'] );
		$this->assertSame( 25, $all[19]['time'] );
	}
}
