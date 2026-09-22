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

	/**
	 * WooCommerce page IDs returned by wc_get_page_id().
	 *
	 * @var array<string, int>
	 */
	private array $wc_pages = [];

	protected function setUp(): void {
		parent::setUp();

		$this->stub_options();
		$this->wc_pages = [
			'cart'      => 20,
			'checkout'  => 21,
			'myaccount' => 22,
		];

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
		Functions\when( 'wc_get_page_id' )->alias( fn( string $page ): int => $this->wc_pages[ $page ] ?? -1 );
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

	public function test_woocommerce_cart_checkout_and_account_pages_are_left_out(): void {
		$this->assertSame( [ 'https://example.com/?page_id=10' ], $this->listed() );
	}

	public function test_unassigned_woocommerce_pages_leave_every_page_listed(): void {
		$this->wc_pages = [];

		$this->assertCount( 4, $this->listed() );
	}

	public function test_excluded_ids_filter_leaves_out_extra_posts(): void {
		Filters\expectApplied( 'lw_seo_sitemap_excluded_ids' )
			->once()
			->with( [ 20, 21, 22 ], 'page' )
			->andReturn( [ 20, 21, 22, 10 ] );

		$this->assertSame( [], $this->listed() );
	}
}
