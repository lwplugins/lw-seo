<?php
/**
 * Redirects REST controller unit tests (duplicate sources).
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Rest\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Redirects\Manager;
use LightweightPlugins\SEO\Rest\Admin\RedirectsController;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Rest\Admin\RedirectsController
 */
final class RedirectsControllerTest extends MonkeyTestCase {

	/**
	 * In-memory wp_options.
	 *
	 * @var array<string, mixed>
	 */
	private array $options = [];

	protected function setUp(): void {
		parent::setUp();
		$this->options = [
			Manager::OPTION_NAME => [
				[ 'id' => 'id-a', 'source' => '/a', 'destination' => '/new-a', 'type' => 301, 'regex' => false, 'hits' => 0, 'last_accessed' => '' ],
				[ 'id' => 'id-b', 'source' => '/b', 'destination' => '/new-b', 'type' => 301, 'regex' => false, 'hits' => 0, 'last_accessed' => '' ],
			],
		];

		Functions\stubTranslationFunctions();
		Functions\when( 'get_option' )->alias( fn( string $name, $fallback = false ) => $this->options[ $name ] ?? $fallback );
		Functions\when( 'update_option' )->alias(
			function ( string $name, $value ): bool {
				$this->options[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'sanitize_text_field' )->alias( static fn( $value ) => trim( (string) $value ) );
		Functions\when( 'rest_sanitize_boolean' )->alias( static fn( $value ) => (bool) $value );
		Functions\when( 'home_url' )->justReturn( 'https://example.test' );
		Functions\when( 'current_time' )->justReturn( '2026-09-27 10:00:00' );
		Functions\when( 'wp_generate_uuid4' )->justReturn( 'new-uuid' );
	}

	public function test_create_rejects_a_source_that_already_has_a_redirect(): void {
		$result = ( new RedirectsController() )->create_item( new \WP_REST_Request( [ 'source' => '/a/', 'destination' => '/x', 'type' => 301 ] ) );

		$this->assert_duplicate_error( $result );
		$this->assertCount( 2, $this->options[ Manager::OPTION_NAME ] );
	}

	public function test_create_adds_a_new_source(): void {
		$result = ( new RedirectsController() )->create_item( new \WP_REST_Request( [ 'source' => '/c', 'destination' => '/x', 'type' => 301 ] ) );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 201, $result->get_status() );
	}

	public function test_update_rejects_the_source_of_another_redirect(): void {
		$result = ( new RedirectsController() )->update_item( new \WP_REST_Request( [ 'id' => 'id-b', 'source' => '/a', 'destination' => '/new-b', 'type' => 301 ] ) );

		$this->assert_duplicate_error( $result );
		$this->assertSame( '/b', $this->options[ Manager::OPTION_NAME ][1]['source'] );
	}

	public function test_update_accepts_its_own_source(): void {
		$result = ( new RedirectsController() )->update_item( new \WP_REST_Request( [ 'id' => 'id-b', 'source' => '/b/', 'destination' => '/changed', 'type' => 301 ] ) );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( '/changed', $result->get_data()['destination'] );
	}

	/**
	 * Assert the 400 duplicate-source error.
	 *
	 * @param mixed $result Controller result.
	 */
	private function assert_duplicate_error( mixed $result ): void {
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame(
			[ 'lw_seo_redirect_invalid', 'A redirect for this source already exists.', [ 'status' => 400 ] ],
			[ $result->get_error_code(), $result->get_error_message(), $result->get_error_data() ]
		);
	}
}
