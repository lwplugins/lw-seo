<?php
/**
 * KeyFile unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\IndexNow;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\IndexNow\KeyFile;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class KeyFileTest extends MonkeyTestCase {

	use OptionsStubTrait;

	private const KEY = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_serves_matching_key(): void {
		$this->stub_options( [ 'indexnow_enabled' => true ], [ 'lw_seo_indexnow_key' => self::KEY ] );

		$this->assertSame( self::KEY, KeyFile::content_for( self::KEY ) );
	}

	public function test_mismatch_not_served(): void {
		$this->stub_options( [ 'indexnow_enabled' => true ], [ 'lw_seo_indexnow_key' => self::KEY ] );

		$this->assertNull( KeyFile::content_for( str_repeat( 'b', 32 ) ) );
	}

	public function test_disabled_not_served(): void {
		$this->stub_options( [ 'indexnow_enabled' => false ], [ 'lw_seo_indexnow_key' => self::KEY ] );

		$this->assertNull( KeyFile::content_for( self::KEY ) );
	}

	public function test_fixed_rewrite_pattern(): void {
		$this->stub_options( [ 'indexnow_enabled' => true ] );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'add_filter' )->justReturn( true );
		Functions\expect( 'add_rewrite_rule' )->once()->with( '^([a-f0-9]{32})\.txt$', 'index.php?lw_indexnow_key=$matches[1]', 'top' );

		( new KeyFile() )->add_rewrite_rules();
		$this->addToAssertionCount( 1 );
	}

	public function test_handle_request_mismatch_sets_404_without_output(): void {
		$this->stub_options( [ 'indexnow_enabled' => true ], [ 'lw_seo_indexnow_key' => self::KEY ] );
		$query = new class() {
			public int $set_404_calls = 0;

			public function set_404(): void {
				++$this->set_404_calls;
			}
		};
		$GLOBALS['wp_query'] = $query;
		$status              = 0;
		Functions\when( 'get_query_var' )->justReturn( str_repeat( 'b', 32 ) );
		Functions\when( 'status_header' )->alias(
			static function ( int $code ) use ( &$status ): void {
				$status = $code;
			}
		);
		Functions\when( 'nocache_headers' )->justReturn( null );

		ob_start();
		( new KeyFile() )->handle_request();
		$output = ob_get_clean();

		unset( $GLOBALS['wp_query'] );
		$this->assertSame( '', $output );
		$this->assertSame( 1, $query->set_404_calls );
		$this->assertSame( 404, $status );
	}

	public function test_handle_request_ignores_other_requests(): void {
		$this->stub_options( [ 'indexnow_enabled' => true ] );
		Functions\when( 'get_query_var' )->justReturn( '' );
		Functions\expect( 'status_header' )->never();

		( new KeyFile() )->handle_request();
		$this->addToAssertionCount( 1 );
	}
}
