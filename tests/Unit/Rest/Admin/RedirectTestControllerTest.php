<?php
/**
 * Redirect URL tester REST controller unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Rest\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Redirects\Manager;
use LightweightPlugins\SEO\Rest\Admin\RedirectTestController;
use LightweightPlugins\SEO\Rest\Admin\Routes;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Rest\Admin\RedirectTestController
 */
final class RedirectTestControllerTest extends MonkeyTestCase {

	public function test_requires_manage_options(): void {
		Functions\expect( 'current_user_can' )->once()->with( 'manage_options' )->andReturn( false );
		Functions\expect( 'register_rest_route' )->once()->andReturnUsing(
			static function ( string $ns, string $route, array $args ): bool {
				\PHPUnit\Framework\Assert::assertSame( 'lw-seo/v1', $ns );
				\PHPUnit\Framework\Assert::assertSame( '/admin/redirects-test', $route );
				\PHPUnit\Framework\Assert::assertSame( [ Routes::class, 'can_manage' ], $args['permission_callback'] );
				return true;
			}
		);

		( new RedirectTestController() )->register_routes();

		$this->assertFalse( Routes::can_manage() );
	}

	public function test_returns_explain_payload(): void {
		$options = [
			Manager::OPTION_NAME => [
				[ 'id' => 'id-a', 'source' => '/a', 'destination' => '/new-a', 'type' => 301, 'regex' => false, 'hits' => 0, 'last_accessed' => '' ],
			],
		];
		Functions\when( 'get_option' )->alias( static fn( string $name, $fallback = false ) => $options[ $name ] ?? $fallback );
		Functions\when( 'home_url' )->alias( static fn( string $path = '' ) => 'https://example.test' . $path );
		Functions\when( 'sanitize_text_field' )->alias( static fn( $value ) => trim( (string) $value ) );

		$response = ( new RedirectTestController() )->test_url( new \WP_REST_Request( [ 'url' => '/a/' ] ) );

		$data = $response->get_data();
		$this->assertSame( '/a', $data['path'] );
		$this->assertSame( 'https://example.test/new-a', $data['match']['destination'] );
		$this->assertSame( 'matched', $data['rules'][0]['result'] );
	}
}
