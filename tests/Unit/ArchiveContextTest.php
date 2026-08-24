<?php
/**
 * ArchiveContext unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Meta\ArchiveContext;

final class ArchiveContextTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: string, 1: string, 2: int, 3: string}>
	 */
	public static function pagination_provider(): array {
		return [
			'plain archive'      => [ 'https://example.com/shop/', 'page', 2, 'https://example.com/shop/page/2/' ],
			'missing slash'      => [ 'https://example.com/shop', 'page', 3, 'https://example.com/shop/page/3/' ],
			'custom base'        => [ 'https://example.com/shop/', 'oldal', 4, 'https://example.com/shop/oldal/4/' ],
			'query string kept'  => [ 'https://example.com/shop/?orderby=price', 'page', 2, 'https://example.com/shop/page/2/?orderby=price' ],
		];
	}

	/**
	 * @dataProvider pagination_provider
	 */
	public function test_append_pagination( string $base, string $pagination_base, int $paged, string $expected ): void {
		$this->assertSame( $expected, ArchiveContext::append_pagination( $base, $pagination_base, $paged ) );
	}

	public function test_paged_url_returns_base_on_first_page(): void {
		Functions\when( 'get_query_var' )->justReturn( 1 );

		$this->assertSame( 'https://example.com/shop/', ArchiveContext::paged_url( 'https://example.com/shop/' ) );
	}

	public function test_paged_url_returns_base_when_empty(): void {
		Functions\when( 'get_query_var' )->justReturn( 5 );

		$this->assertSame( '', ArchiveContext::paged_url( '' ) );
	}

	public function test_paged_url_uses_pretty_permalinks(): void {
		Functions\when( 'get_query_var' )->justReturn( 2 );
		Functions\when( 'trailingslashit' )->alias( static fn( string $url ): string => rtrim( $url, '/' ) . '/' );

		$GLOBALS['wp_rewrite'] = new \WP_Rewrite( true );

		$this->assertSame(
			'https://example.com/shop/page/2/',
			ArchiveContext::paged_url( 'https://example.com/shop/' )
		);
	}

	public function test_paged_url_falls_back_to_query_arg_without_permalinks(): void {
		Functions\when( 'get_query_var' )->justReturn( 3 );
		Functions\when( 'add_query_arg' )->alias(
			static fn( string $key, int $value, string $url ): string => $url . '&' . $key . '=' . $value
		);

		$GLOBALS['wp_rewrite'] = new \WP_Rewrite( false );

		$this->assertSame(
			'https://example.com/?post_type=product&paged=3',
			ArchiveContext::paged_url( 'https://example.com/?post_type=product' )
		);
	}

	public function test_post_type_archive_link_uses_query_var(): void {
		Functions\when( 'get_query_var' )->justReturn( 'product' );
		Functions\when( 'get_post_type_archive_link' )->justReturn( 'https://example.com/shop/' );

		$this->assertSame( 'https://example.com/shop/', ArchiveContext::post_type_archive_link() );
	}

	public function test_post_type_archive_link_falls_back_to_queried_object(): void {
		Functions\when( 'get_query_var' )->justReturn( '' );
		Functions\when( 'get_queried_object' )->justReturn( new \WP_Post_Type( [ 'name' => 'product' ] ) );
		Functions\when( 'get_post_type_archive_link' )->alias(
			static fn( string $type ): string => 'https://example.com/' . $type . '-archive/'
		);

		$this->assertSame( 'https://example.com/product-archive/', ArchiveContext::post_type_archive_link() );
	}

	public function test_post_type_archive_link_handles_array_query_var(): void {
		Functions\when( 'get_query_var' )->justReturn( [ 'product', 'post' ] );
		Functions\when( 'get_post_type_archive_link' )->alias(
			static fn( string $type ): string => 'https://example.com/' . $type . '/'
		);

		$this->assertSame( 'https://example.com/product/', ArchiveContext::post_type_archive_link() );
	}

	public function test_post_type_archive_link_empty_without_archive(): void {
		Functions\when( 'get_query_var' )->justReturn( 'product' );
		Functions\when( 'get_post_type_archive_link' )->justReturn( false );

		$this->assertSame( '', ArchiveContext::post_type_archive_link() );
	}

	public function test_is_posts_page_true_for_separate_blog_page(): void {
		Functions\when( 'is_home' )->justReturn( true );
		Functions\when( 'is_front_page' )->justReturn( false );
		Functions\when( 'get_option' )->justReturn( 12 );

		$this->assertTrue( ArchiveContext::is_posts_page() );
	}

	public function test_is_posts_page_false_on_front_page(): void {
		Functions\when( 'is_home' )->justReturn( true );
		Functions\when( 'is_front_page' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( 12 );

		$this->assertFalse( ArchiveContext::is_posts_page() );
	}

	public function test_is_posts_page_false_without_posts_page(): void {
		Functions\when( 'is_home' )->justReturn( true );
		Functions\when( 'is_front_page' )->justReturn( false );
		Functions\when( 'get_option' )->justReturn( 0 );

		$this->assertFalse( ArchiveContext::is_posts_page() );
	}
}
