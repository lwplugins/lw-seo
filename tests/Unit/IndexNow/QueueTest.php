<?php
/**
 * Queue unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\IndexNow;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\IndexNow\Queue;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class QueueTest extends MonkeyTestCase {

	use OptionsStubTrait;

	/** @var array<string, mixed> */
	private array $store = [];

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	private function stub_store( bool $enabled, bool $scheduled = false ): void {
		$this->store = [];
		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ) => array_merge( $defaults, (array) $args ) );
		Functions\when( 'get_option' )->alias(
			function ( string $name, $fallback = false ) use ( $enabled ) {
				if ( Options::OPTION_NAME === $name ) {
					return [ 'indexnow_enabled' => $enabled ];
				}
				return $this->store[ $name ] ?? ( 'lw_seo_indexnow_key' === $name ? str_repeat( 'd', 32 ) : $fallback );
			}
		);
		Functions\when( 'update_option' )->alias(
			function ( string $name, $value ): bool {
				$this->store[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_option' )->alias(
			function ( string $name ): bool {
				unset( $this->store[ $name ] );
				return true;
			}
		);
		Functions\when( 'wp_next_scheduled' )->justReturn( $scheduled ? 123 : false );
	}

	public function test_dedupes(): void {
		$this->stub_store( true );
		Functions\when( 'wp_schedule_single_event' )->justReturn( true );

		Queue::add( [ 'https://e.com/a/', 'https://e.com/b/' ] );
		Queue::add( [ 'https://e.com/a/' ] );

		$this->assertSame( [ 'https://e.com/a/', 'https://e.com/b/' ], $this->store['lw_seo_indexnow_queue'] );
	}

	public function test_single_event_scheduled(): void {
		$this->stub_store( true );
		Functions\expect( 'wp_schedule_single_event' )->once()->with( \Mockery::type( 'int' ), 'lw_seo_indexnow_flush' );

		Queue::add( [ 'https://e.com/a/' ] );
		$this->addToAssertionCount( 1 );
	}

	public function test_no_second_event_when_pending(): void {
		$this->stub_store( true, true );
		Functions\expect( 'wp_schedule_single_event' )->never();

		Queue::add( [ 'https://e.com/a/' ] );
		$this->addToAssertionCount( 1 );
	}

	public function test_flush_submits_and_clears(): void {
		$this->stub_store( true );
		$this->store['lw_seo_indexnow_queue'] = [ 'https://e.com/a/' ];
		$sent                                 = 0;
		Functions\when( 'home_url' )->alias( static fn( string $path = '' ): string => 'https://e.com' . $path );
		Functions\when( 'wp_parse_url' )->alias( static fn( string $url, int $component = -1 ) => parse_url( $url, $component ) );
		Functions\when( 'wp_json_encode' )->alias( static fn( $data ) => json_encode( $data ) );
		Functions\when( 'wp_remote_post' )->alias(
			static function () use ( &$sent ) {
				++$sent;
				return [];
			}
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );

		Queue::flush();

		$this->assertSame( 1, $sent );
		$this->assertArrayNotHasKey( 'lw_seo_indexnow_queue', $this->store );
	}

	public function test_flush_when_disabled_clears_without_sending(): void {
		$this->stub_store( false );
		$this->store['lw_seo_indexnow_queue'] = [ 'https://e.com/a/' ];
		Functions\expect( 'wp_remote_post' )->never();

		Queue::flush();

		$this->assertArrayNotHasKey( 'lw_seo_indexnow_queue', $this->store );
	}

	public function test_disabling_clears_queue_and_event(): void {
		Functions\expect( 'delete_option' )->once()->with( 'lw_seo_indexnow_queue' );
		Functions\expect( 'wp_clear_scheduled_hook' )->once()->with( 'lw_seo_indexnow_flush' );

		Queue::on_options_updated( [ 'indexnow_enabled' => true ], [ 'indexnow_enabled' => false ] );
		$this->addToAssertionCount( 1 );
	}

	public function test_other_changes_keep_queue(): void {
		Functions\expect( 'delete_option' )->never();
		Functions\expect( 'wp_clear_scheduled_hook' )->never();

		Queue::on_options_updated( [ 'indexnow_enabled' => true ], [ 'indexnow_enabled' => true, 'x' => 1 ] );
		Queue::on_options_updated( [], [ 'indexnow_enabled' => true ] );
		$this->addToAssertionCount( 1 );
	}
}
