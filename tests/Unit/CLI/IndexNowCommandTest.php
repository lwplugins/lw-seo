<?php
/**
 * IndexNowCommand unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\CLI;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\CLI\IndexNowCommand;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\CLI\IndexNowCommand
 */
final class IndexNowCommandTest extends MonkeyTestCase {

	/** @var array<string, mixed> */
	private array $store = [];

	/** @var int */
	private int $posts = 0;

	protected function setUp(): void {
		parent::setUp();
		\WP_CLI::$log = [];
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	private function stub_site( bool $enabled ): void {
		$this->store = [ 'lw_seo_indexnow_key' => str_repeat( 'b', 32 ) ];
		$this->posts = 0;
		Options::clear_cache();
		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ) => array_merge( $defaults, (array) $args ) );
		Functions\when( 'get_option' )->alias(
			function ( string $name, $fallback = false ) use ( $enabled ) {
				if ( Options::OPTION_NAME === $name ) {
					return [ 'indexnow_enabled' => $enabled ];
				}
				return $this->store[ $name ] ?? $fallback;
			}
		);
		Functions\when( 'update_option' )->alias(
			function ( string $name, $value ): bool {
				$this->store[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'home_url' )->alias( static fn( string $path = '' ): string => 'https://example.com' . $path );
		Functions\when( 'wp_parse_url' )->alias( static fn( string $url, int $component = -1 ) => parse_url( $url, $component ) );
		Functions\when( 'wp_json_encode' )->alias( static fn( $data ) => json_encode( $data ) );
		Functions\when( 'is_wp_error' )->alias( static fn( $thing ): bool => $thing instanceof \WP_Error );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_post' )->alias(
			function (): array {
				++$this->posts;
				return [ 'response' => [ 'code' => 200 ] ];
			}
		);
		Functions\when( 'wp_next_scheduled' )->justReturn( 123 );
	}

	public function test_submit_direct(): void {
		$this->stub_site( true );

		( new IndexNowCommand() )->submit( [ 'https://example.com/a', 'https://example.com/b' ], [] );

		$this->assertSame( 1, $this->posts );
		$this->assertSame( 'manual', $this->store['lw_seo_indexnow_log'][0]['source'] );
		$this->assertStringStartsWith( 'success: Submitted 2 URL(s)', \WP_CLI::$log[0] );
	}

	public function test_submit_queue_flag(): void {
		$this->stub_site( true );

		( new IndexNowCommand() )->submit( [ 'https://example.com/a' ], [ 'queue' => true ] );

		$this->assertSame( 0, $this->posts );
		$this->assertSame( [ 'https://example.com/a' ], $this->store['lw_seo_indexnow_queue'] );
		$this->assertSame( [ 'success: Queued 1 URL(s).' ], \WP_CLI::$log );
	}

	public function test_submit_refused_when_disabled_or_foreign(): void {
		$this->stub_site( false );
		( new IndexNowCommand() )->submit( [ 'https://example.com/a' ], [] );
		$this->assertStringStartsWith( 'error: Turn on IndexNow first', \WP_CLI::$log[0] );

		\WP_CLI::$log = [];
		$this->stub_site( true );
		( new IndexNowCommand() )->submit( [ 'https://evil.test/a' ], [] );
		$this->assertStringStartsWith( 'error: Only addresses on this site', \WP_CLI::$log[0] );
		$this->assertSame( 0, $this->posts );
	}

	public function test_key_regenerate(): void {
		$this->stub_site( true );

		( new IndexNowCommand() )->key( [], [ 'regenerate' => true ] );

		$new = $this->store['lw_seo_indexnow_key'];
		$this->assertNotSame( str_repeat( 'b', 32 ), $new );
		$this->assertSame( [ 'success: Key regenerated: https://example.com/' . $new . '.txt' ], \WP_CLI::$log );
	}

	public function test_key_regenerate_refused_when_disabled(): void {
		$this->stub_site( false );

		( new IndexNowCommand() )->key( [], [ 'regenerate' => true ] );

		$this->assertSame( str_repeat( 'b', 32 ), $this->store['lw_seo_indexnow_key'] );
		$this->assertStringStartsWith( 'error:', \WP_CLI::$log[0] );
	}
}
