<?php
/**
 * IndexNow REST controller unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Rest\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Rest\Admin\IndexNowController;
use LightweightPlugins\SEO\Rest\Admin\Routes;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Rest\Admin\IndexNowController
 * @covers \LightweightPlugins\SEO\IndexNow\ManualUrls
 */
final class IndexNowControllerTest extends MonkeyTestCase {

	/** @var array<string, mixed> */
	private array $store = [];

	/** @var array<int, array{0: string, 1: array<string, mixed>}> */
	private array $requests = [];

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	private function stub_site( bool $enabled ): void {
		$this->store    = [ 'lw_seo_indexnow_key' => str_repeat( 'a', 32 ) ];
		$this->requests = [];
		Options::clear_cache();
		Functions\stubTranslationFunctions();
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
		Functions\when( 'home_url' )->alias( static fn( string $path = '' ): string => 'https://Example.com' . $path );
		Functions\when( 'wp_parse_url' )->alias( static fn( string $url, int $component = -1 ) => parse_url( $url, $component ) );
		Functions\when( 'wp_json_encode' )->alias( static fn( $data ) => json_encode( $data ) );
		Functions\when( 'is_wp_error' )->alias( static fn( $thing ): bool => $thing instanceof \WP_Error );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_post' )->alias(
			function ( string $url, array $args ): array {
				$this->requests[] = [ $url, $args ];
				return [ 'response' => [ 'code' => 200 ] ];
			}
		);
	}

	public function test_requires_manage_options(): void {
		$routes = [];
		Functions\when( 'register_rest_route' )->alias(
			static function ( string $ns, string $route, array $args ) use ( &$routes ): bool {
				$routes[ $route ] = [ $ns, $args ];
				return true;
			}
		);

		( new IndexNowController() )->register_routes();

		$this->assertSame( [ '/admin/indexnow/submit', '/admin/indexnow/regenerate-key' ], array_keys( $routes ) );
		foreach ( $routes as [ $ns, $endpoints ] ) {
			$this->assertSame( 'lw-seo/v1', $ns );
			foreach ( $endpoints as $endpoint ) {
				$this->assertSame( [ Routes::class, 'can_manage' ], $endpoint['permission_callback'] );
			}
		}
	}

	public function test_rejects_foreign_host(): void {
		$this->stub_site( true );

		$result = ( new IndexNowController() )->submit(
			new \WP_REST_Request( [ 'urls' => [ 'https://example.com/a', 'https://evil.test/b' ] ] )
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( [ 'status' => 400 ], $result->get_error_data() );
		$this->assertStringContainsString( 'https://evil.test/b', $result->get_error_message() );
		$this->assertSame( [], $this->requests );
	}

	public function test_rejects_when_disabled(): void {
		$this->stub_site( false );

		$result = ( new IndexNowController() )->submit( new \WP_REST_Request( [ 'urls' => [ 'https://example.com/a' ] ] ) );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'Turn on IndexNow first.', $result->get_error_message() );
		$this->assertSame( [], $this->requests );

		$result = ( new IndexNowController() )->regenerate_key();
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( str_repeat( 'a', 32 ), $this->store['lw_seo_indexnow_key'] );
	}

	public function test_caps_at_100_urls(): void {
		$this->stub_site( true );
		$urls = [];
		for ( $i = 0; $i < 101; $i++ ) {
			$urls[] = 'https://example.com/p' . $i;
		}

		$result = ( new IndexNowController() )->submit( new \WP_REST_Request( [ 'urls' => $urls ] ) );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( [], $this->requests );
	}

	public function test_submit_logs_manual(): void {
		$this->stub_site( true );

		$response = ( new IndexNowController() )->submit(
			new \WP_REST_Request( [ 'urls' => [ ' https://EXAMPLE.com/a ', '', ' https://EXAMPLE.com/a', 'https://example.com/b' ] ] )
		);

		$this->assertCount( 1, $this->requests );
		$body = json_decode( $this->requests[0][1]['body'], true );
		$this->assertSame( [ 'https://EXAMPLE.com/a', 'https://example.com/b' ], $body['urlList'] );

		$data = $response->get_data();
		$this->assertTrue( $data['ok'] );
		$this->assertSame( 'manual', $data['log'][0]['source'] );
		$this->assertSame( 2, $data['log'][0]['count'] );
		$this->assertSame( 'manual', $this->store['lw_seo_indexnow_log'][0]['source'] );
	}

	public function test_regenerate_key(): void {
		$this->stub_site( true );

		$response = ( new IndexNowController() )->regenerate_key();

		$new = $this->store['lw_seo_indexnow_key'];
		$this->assertNotSame( str_repeat( 'a', 32 ), $new );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $new );
		$this->assertSame( [ 'key_url' => 'https://Example.com/' . $new . '.txt' ], $response->get_data() );
	}

	public function test_log_is_newest_first(): void {
		$this->stub_site( true );
		$this->store['lw_seo_indexnow_log'] = [ [ 'time' => 1 ], [ 'time' => 2 ] ];

		$this->assertSame( [ [ 'time' => 2 ], [ 'time' => 1 ] ], IndexNowController::log() );
	}
}
