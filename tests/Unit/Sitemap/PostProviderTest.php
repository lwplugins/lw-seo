<?php
/**
 * PostProvider unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Sitemap;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Sitemap\PostProvider;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Sitemap\PostProvider
 * @covers \LightweightPlugins\SEO\Sitemap\ExcludedPosts
 */
final class PostProviderTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();

		$this->stub_options();
		$pages = [];
		foreach ( [ 10, 20, 21, 22 ] as $id ) {
			$pages[] = new \WP_Post(
				[
					'ID'            => $id,
					'post_type'     => 'page',
					'post_status'   => 'publish',
					'post_password' => '',
				]
			);
		}

		Functions\when( 'get_posts' )->justReturn( $pages );
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_permalink' )->alias( static fn( \WP_Post $post ): string => 'https://example.com/?page_id=' . $post->ID );
		Functions\when( 'get_the_modified_date' )->justReturn( '' );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Listed URLs of the page sitemap.
	 *
	 * @return array<int, string>
	 */
	private function listed(): array {
		return array_column( ( new PostProvider( 'page' ) )->get_items( 1 ), 'loc' );
	}

	public function test_lists_every_eligible_page_when_nothing_is_excluded(): void {
		$this->assertCount( 4, $this->listed() );
	}

	public function test_query_has_no_language_argument_of_its_own(): void {
		$args = [];
		Functions\when( 'get_posts' )->alias(
			static function ( array $query ) use ( &$args ): array {
				$args = $query;
				return [];
			}
		);

		$this->listed();

		$this->assertArrayNotHasKey( 'lang', $args );
	}

	public function test_query_runs_through_the_query_args_filter(): void {
		$context = '';
		Filters\expectApplied( 'lw_seo_query_args' )->once()->andReturnUsing(
			static function ( array $args, string $query_context ) use ( &$context ): array {
				$context = $query_context;
				return $args;
			}
		);

		$this->listed();

		$this->assertSame( 'sitemap_posts', $context );
	}

	public function test_excluded_ids_filter_receives_the_integrations_default_exclusions(): void {
		Filters\expectApplied( 'lw_seo_sitemap_default_excluded_ids' )->once()->with( [], 'page' )->andReturn( [ 20, 21, 22 ] );
		Filters\expectApplied( 'lw_seo_sitemap_excluded_ids' )->once()->with( [ 20, 21, 22 ], 'page' )->andReturnFirstArg();

		$this->assertSame( [ 'https://example.com/?page_id=10' ], $this->listed() );
	}

	public function test_excluded_ids_filter_leaves_out_extra_posts(): void {
		Filters\expectApplied( 'lw_seo_sitemap_excluded_ids' )
			->once()
			->with( [], 'page' )
			->andReturn( [ 20, 21, 22, 10 ] );

		$this->assertSame( [], $this->listed() );
	}
}
