<?php
/**
 * 404 log REST controller unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Rest\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\NotFound\LogRepository;
use LightweightPlugins\SEO\Rest\Admin\NotFoundLogController;
use LightweightPlugins\SEO\Rest\Admin\Routes;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use Mockery;

/**
 * @covers \LightweightPlugins\SEO\Rest\Admin\NotFoundLogController
 */
final class NotFoundLogControllerTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( static fn( $value ) => trim( (string) $value ) );
		Functions\when( 'sanitize_key' )->alias( static fn( $value ) => strtolower( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ) ) );
		Functions\when( 'absint' )->alias( static fn( $value ) => abs( (int) $value ) );
	}

	public function test_routes_require_manage_options(): void {
		$routes = [];
		Functions\when( 'register_rest_route' )->alias(
			static function ( string $ns, string $route, array $args ) use ( &$routes ): bool {
				$routes[ $route ] = [ $ns, $args ];
				return true;
			}
		);

		( new NotFoundLogController( Mockery::mock( LogRepository::class ) ) )->register_routes();

		$this->assertSame( [ '/admin/404-log', '/admin/404-log/(?P<id>\d+)' ], array_keys( $routes ) );
		foreach ( $routes as [ $ns, $endpoints ] ) {
			$this->assertSame( 'lw-seo/v1', $ns );
			foreach ( $endpoints as $endpoint ) {
				$this->assertSame( [ Routes::class, 'can_manage' ], $endpoint['permission_callback'] );
			}
		}

		Functions\expect( 'current_user_can' )->once()->with( 'manage_options' )->andReturn( false );
		$this->assertFalse( Routes::can_manage() );
	}

	public function test_list_passes_sanitized_args(): void {
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'paginate' )->once()->with( 'caf%C3%A9', 'hits', 'asc', 1, 100 )->andReturn( [ 'items' => [], 'total' => 0 ] );

		$response = ( new NotFoundLogController( $repository ) )->list_items(
			new \WP_REST_Request( [ 'search' => ' caf%C3%A9 ', 'orderby' => 'Hits', 'order' => 'ASC', 'page' => '0', 'per_page' => '9999' ] )
		);

		$this->assertSame( [ 'items' => [], 'total' => 0 ], $response->get_data() );
	}

	public function test_list_defaults_and_minimum_per_page(): void {
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'paginate' )->once()->with( '', '', '', 1, 20 )->andReturn( [ 'items' => [], 'total' => 0 ] );
		$repository->shouldReceive( 'paginate' )->once()->with( '', '', '', 2, 1 )->andReturn( [ 'items' => [], 'total' => 0 ] );

		$controller = new NotFoundLogController( $repository );
		$controller->list_items( new \WP_REST_Request( [] ) );
		$controller->list_items( new \WP_REST_Request( [ 'page' => 2, 'per_page' => 0 ] ) );

		$this->addToAssertionCount( 1 );
	}

	public function test_delete_single(): void {
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'delete' )->once()->with( [ 7 ] )->andReturn( 1 );

		$response = ( new NotFoundLogController( $repository ) )->delete_item( new \WP_REST_Request( [ 'id' => '7' ] ) );

		$this->assertSame( [ 'deleted' => 1 ], $response->get_data() );
	}

	public function test_clear(): void {
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'clear' )->once();

		$response = ( new NotFoundLogController( $repository ) )->clear();

		$this->assertSame( [ 'cleared' => true ], $response->get_data() );
	}
}
