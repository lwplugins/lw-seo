<?php
/**
 * Tests for the Rank Math migration warnings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\RankMath;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Migration\RankMath\WarningCollector;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use Mockery;

/**
 * @covers \LightweightPlugins\SEO\Migration\RankMath\WarningCollector
 */
final class WarningCollectorTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\stubTranslationFunctions();
		Functions\when( 'get_option' )->justReturn( [] );

		$wpdb           = Mockery::mock( 'wpdb' );
		$wpdb->postmeta = 'wp_postmeta';
		$wpdb->shouldReceive( 'prepare' )->andReturn( 'SELECT' );
		$wpdb->shouldReceive( 'get_var' )->andReturn( 3 );

		$GLOBALS['wpdb'] = $wpdb;
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	/**
	 * Without the Woo permalink warning the rest must still be a list,
	 * otherwise it reaches the admin UI as a JSON object.
	 */
	public function test_warnings_are_a_list_when_the_first_one_is_missing(): void {
		$warnings = ( new WarningCollector() )->collect();

		$this->assertSame( [ 0, 1 ], array_keys( $warnings ) );
		$this->assertSame( [ 'schema', 'non_migratable' ], array_column( $warnings, 'code' ) );
	}
}
