<?php
/**
 * Client unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\IndexNow;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\IndexNow\Client;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class ClientTest extends MonkeyTestCase {

	/** @var array<int, array{0: string, 1: array<string, mixed>}> */
	private array $requests = [];

	/** @var array<int, array<string, mixed>> */
	private array $logged = [];

	private function stub_http( int $code, ?\WP_Error $error = null ): void {
		$this->requests = [];
		$this->logged   = [];
		$key            = str_repeat( 'c', 32 );

		Functions\when( 'get_option' )->alias(
			static fn( string $name, $fallback = false ) => 'lw_seo_indexnow_key' === $name ? $key : $fallback
		);
		Functions\when( 'update_option' )->alias(
			function ( string $name, $value ): bool {
				$this->logged = $value;
				return true;
			}
		);
		Functions\when( 'home_url' )->alias( static fn( string $path = '' ): string => 'https://example.com' . $path );
		Functions\when( 'wp_parse_url' )->alias( static fn( string $url, int $component = -1 ) => parse_url( $url, $component ) );
		Functions\when( 'wp_json_encode' )->alias( static fn( $data ) => json_encode( $data ) );
		Functions\when( 'wp_remote_post' )->alias(
			function ( string $url, array $args ) use ( $error ) {
				$this->requests[] = [ $url, $args ];
				return $error ?? [ 'response' => [ 'code' => 0 ] ];
			}
		);
		Functions\when( 'is_wp_error' )->alias( static fn( $thing ): bool => $thing instanceof \WP_Error );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( $code );
	}

	public function test_payload_shape(): void {
		$this->stub_http( 200 );

		$result = Client::submit( [ 'https://example.com/a/', 'https://example.com/a/', 'https://example.com/b/' ] );

		$this->assertTrue( $result['ok'] );
		$this->assertCount( 1, $this->requests );
		[ $url, $args ] = $this->requests[0];
		$this->assertSame( 'https://api.indexnow.org/indexnow', $url );
		$this->assertSame( 10, $args['timeout'] );
		$this->assertSame( 'application/json; charset=utf-8', $args['headers']['Content-Type'] );
		$body = json_decode( $args['body'], true );
		$this->assertSame( 'example.com', $body['host'] );
		$this->assertSame( str_repeat( 'c', 32 ), $body['key'] );
		$this->assertSame( 'https://example.com/' . str_repeat( 'c', 32 ) . '.txt', $body['keyLocation'] );
		$this->assertSame( [ 'https://example.com/a/', 'https://example.com/b/' ], $body['urlList'] );
	}

	public function test_202_is_ok(): void {
		$this->stub_http( 202 );

		$this->assertSame( true, Client::submit( [ 'https://example.com/a/' ] )['ok'] );
	}

	public function test_429_message(): void {
		$this->stub_http( 429 );

		$result = Client::submit( [ 'https://example.com/a/' ], 'manual' );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 429, $result['code'] );
		$this->assertSame( 'Too many requests', $result['message'] );
		$this->assertSame( 'manual', $this->logged[0]['source'] );
		$this->assertFalse( $this->logged[0]['ok'] );
	}

	public function test_wp_error(): void {
		$error = new \WP_Error( 'http_request_failed', 'cURL error 28' );
		$this->stub_http( 0, $error );

		$result = Client::submit( [ 'https://example.com/a/' ] );

		$this->assertSame( [ 'ok' => false, 'code' => 0, 'message' => 'cURL error 28' ], $result );
	}

	public function test_chunks_over_10000(): void {
		$this->stub_http( 200 );
		$urls = [];
		for ( $i = 0; $i < 10001; $i++ ) {
			$urls[] = 'https://example.com/p' . $i . '/';
		}

		Client::submit( $urls );

		$this->assertCount( 2, $this->requests );
		$this->assertCount( 10000, json_decode( $this->requests[0][1]['body'], true )['urlList'] );
		$this->assertCount( 1, json_decode( $this->requests[1][1]['body'], true )['urlList'] );
		$this->assertSame( 10001, $this->logged[0]['count'] );
	}

	public function test_endpoint_filter(): void {
		$this->stub_http( 200 );
		Filters\expectApplied( 'lw_seo_indexnow_endpoint' )->once()->andReturn( 'https://example.org/in' );

		Client::submit( [ 'https://example.com/a/' ] );

		$this->assertSame( 'https://example.org/in', $this->requests[0][0] );
	}
}
