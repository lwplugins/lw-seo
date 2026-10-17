<?php
/**
 * Logger unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\NotFound;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\NotFound\Logger;
use LightweightPlugins\SEO\NotFound\LogRepository;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\NotFound\Logger
 */
final class LoggerTest extends MonkeyTestCase {

	use OptionsStubTrait;

	/** @var array<string, mixed> */
	private array $transients = [];

	protected function setUp(): void {
		parent::setUp();
		$this->transients = [];
		$_SERVER          = [ 'REQUEST_URI' => '/missing-page/?utm=1', 'REQUEST_METHOD' => 'GET' ];

		foreach ( [ 'is_404' => true, 'is_admin' => false, 'wp_doing_ajax' => false, 'wp_doing_cron' => false ] as $fn => $value ) {
			Functions\when( $fn )->justReturn( $value );
		}
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'absint' )->alias( static fn( $v ) => abs( (int) $v ) );
		Functions\when( 'apply_filters' )->alias( static fn( string $hook, $value ) => $value );
		Functions\when( 'get_transient' )->alias( fn( string $key ) => $this->transients[ $key ] ?? false );
		Functions\when( 'set_transient' )->alias(
			function ( string $key, $value ): bool {
				$this->transients[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_transient' )->alias(
			function ( string $key ): bool {
				unset( $this->transients[ $key ] );
				return true;
			}
		);
		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 86400 );
		}
	}

	protected function tearDown(): void {
		Options::clear_cache();
		$_SERVER = [];
		parent::tearDown();
	}

	/**
	 * @param array<string, mixed> $saved Saved options (logging on by default).
	 */
	private function logger( LogRepository $repo, array $saved = [] ): Logger {
		$this->stub_options( $saved + [ 'not_found_log_enabled' => true ] );
		return new Logger( $repo );
	}

	public function test_runs_after_core_and_before_the_homepage_fallback(): void {
		$repo   = $this->createMock( LogRepository::class );
		$logger = $this->logger( $repo );
		$logger->register();

		$this->assertSame( 1000, has_action( 'template_redirect', [ $logger, 'maybe_log' ] ) );
	}

	public function test_logs_path_without_query_string(): void {
		$repo = $this->createMock( LogRepository::class );
		$repo->expects( $this->once() )->method( 'record' )->with( '/missing-page/', '', '' );

		$this->logger( $repo )->maybe_log();
	}

	public function test_skips_when_disabled(): void {
		$repo = $this->createMock( LogRepository::class );
		$repo->expects( $this->never() )->method( 'record' );

		$this->logger( $repo, [ 'not_found_log_enabled' => false ] )->maybe_log();
	}

	public function test_skips_non_404(): void {
		Functions\when( 'is_404' )->justReturn( false );
		$repo = $this->createMock( LogRepository::class );
		$repo->expects( $this->never() )->method( 'record' );

		$this->logger( $repo )->maybe_log();
	}

	public function test_skips_asset_extension(): void {
		$_SERVER['REQUEST_URI'] = '/wp-content/missing.PNG';
		$repo                   = $this->createMock( LogRepository::class );
		$repo->expects( $this->never() )->method( 'record' );

		$this->logger( $repo )->maybe_log();
	}

	public function test_skips_head_admin_ajax_cron_and_rest(): void {
		$repo = $this->createMock( LogRepository::class );
		$repo->expects( $this->never() )->method( 'record' );

		$_SERVER['REQUEST_METHOD'] = 'HEAD';
		$this->logger( $repo )->maybe_log();

		$_SERVER['REQUEST_METHOD'] = 'GET';
		foreach ( [ 'is_admin', 'wp_doing_ajax', 'wp_doing_cron' ] as $fn ) {
			Functions\when( $fn )->justReturn( true );
			$this->logger( $repo )->maybe_log();
			Functions\when( $fn )->justReturn( false );
		}
	}

	public function test_throttles_same_url(): void {
		$repo = $this->createMock( LogRepository::class );
		$repo->expects( $this->once() )->method( 'record' );
		$logger = $this->logger( $repo );

		$logger->maybe_log();
		$logger->maybe_log();

		$this->assertArrayHasKey( 'lw_seo_404_t_' . md5( '/missing-page/' ), $this->transients );
	}

	public function test_no_user_agent_unless_enabled(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 test';
		$repo                       = $this->createMock( LogRepository::class );
		$repo->expects( $this->once() )->method( 'record' )->with( '/missing-page/', '', '' );
		$this->logger( $repo )->maybe_log();

		$this->transients = [];
		$repo             = $this->createMock( LogRepository::class );
		$repo->expects( $this->once() )->method( 'record' )->with( '/missing-page/', '', 'Mozilla/5.0 test' );
		$this->logger( $repo, [ 'not_found_log_store_ua' => true ] )->maybe_log();
	}

	public function test_referrer_is_stored_without_its_query_string(): void {
		$_SERVER['HTTP_REFERER'] = 'https://example.com/blog/?token=secret#top';
		$repo                    = $this->createMock( LogRepository::class );
		$repo->expects( $this->once() )->method( 'record' )->with( '/missing-page/', 'https://example.com/blog/', '' );

		$this->logger( $repo )->maybe_log();
	}

	public function test_filter_can_extend_the_skipped_extensions(): void {
		Functions\when( 'apply_filters' )->alias(
			static fn( string $hook, $value ) => 'lw_seo_404_log_skip_extensions' === $hook ? array_merge( $value, [ 'html' ] ) : $value
		);
		$_SERVER['REQUEST_URI'] = '/old.html';
		$repo                   = $this->createMock( LogRepository::class );
		$repo->expects( $this->never() )->method( 'record' );

		$this->logger( $repo )->maybe_log();
	}
}
