<?php
/**
 * llms.txt Cache unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\LlmsTxt;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\LlmsTxt\Cache;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class CacheTest extends MonkeyTestCase {

	/**
	 * Simulated current user ID (the requester is a logged-in admin).
	 */
	private int $current_user = 7;

	protected function setUp(): void {
		parent::setUp();
		$this->current_user = 7;
		Functions\when( 'get_current_user_id' )->alias( fn(): int => $this->current_user );
		Functions\when( 'wp_set_current_user' )->alias(
			function ( int $id ): \WP_User {
				$this->current_user = $id;
				return new \WP_User( [ 'ID' => $id ] );
			}
		);
	}

	public function test_returns_cached_content_without_building(): void {
		Functions\when( 'get_transient' )->justReturn( 'cached' );
		$built = false;

		$result = Cache::remember(
			'index',
			static function () use ( &$built ): string {
				$built = true;
				return 'fresh';
			}
		);

		$this->assertSame( 'cached', $result );
		$this->assertFalse( $built );
	}

	public function test_builds_and_stores_on_miss(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\expect( 'set_transient' )->once()->with( 'lw_seo_llms_index', 'fresh', \Mockery::type( 'int' ) );

		$this->assertSame( 'fresh', Cache::remember( 'index', static fn(): string => 'fresh' ) );
	}

	public function test_builds_as_the_anonymous_visitor(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );
		$builder_user = null;

		Cache::remember(
			'full',
			function () use ( &$builder_user ): string {
				$builder_user = $this->current_user;
				return 'doc';
			}
		);

		$this->assertSame( 0, $builder_user );
	}

	public function test_restores_the_requesting_user_after_building(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );

		Cache::remember( 'index', static fn(): string => 'doc' );

		$this->assertSame( 7, $this->current_user );
	}

	public function test_restores_the_requesting_user_when_the_builder_throws(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\expect( 'set_transient' )->never();

		try {
			Cache::remember(
				'full',
				static function (): string {
					throw new \RuntimeException( 'render failed' );
				}
			);
			$this->fail( 'The builder exception should propagate.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'render failed', $e->getMessage() );
		}

		$this->assertSame( 7, $this->current_user );
	}
}
