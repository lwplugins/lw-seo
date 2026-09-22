<?php
/**
 * ArchiveMeta unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Meta;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Meta\ArchiveMeta;
use LightweightPlugins\SEO\Meta\TagRenderer;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Meta\ArchiveMeta
 */
final class ArchiveMetaTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();

		$this->stub_options(
			[
				'opengraph_enabled' => false,
				'twitter_enabled'   => false,
			]
		);
		Functions\when( 'esc_url' )->alias( static fn( $url ) => (string) $url );
		Functions\when( 'esc_attr' )->alias( static fn( $value ) => (string) $value );
		Functions\when( 'trailingslashit' )->alias( static fn( string $url ): string => rtrim( $url, '/' ) . '/' );
		Functions\when( 'get_query_var' )->justReturn( 2 );

		$GLOBALS['wp_rewrite'] = new \WP_Rewrite( true );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_rewrite'] );
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Run a callable and return what it printed.
	 *
	 * @param callable $callback Callback to run.
	 * @return string
	 */
	private function capture( callable $callback ): string {
		ob_start();
		$callback();

		return (string) ob_get_clean();
	}

	public function test_paged_term_archive_canonical_points_at_its_own_page(): void {
		Functions\when( 'get_queried_object' )->justReturn( new \WP_Term( [ 'term_id' => 5, 'taxonomy' => 'product_cat', 'name' => 'Coffee' ] ) );
		Functions\when( 'get_term_meta' )->justReturn( '' );
		Functions\when( 'single_term_title' )->justReturn( 'Coffee' );
		Functions\when( 'term_description' )->justReturn( '' );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/product-category/coffee/' );

		$html = $this->capture( static fn() => ( new ArchiveMeta( new TagRenderer() ) )->output_taxonomy() );

		$this->assertStringContainsString( '<link rel="canonical" href="https://example.com/product-category/coffee/page/2/" />', $html );
	}

	public function test_paged_author_archive_canonical_points_at_its_own_page(): void {
		Functions\when( 'get_queried_object' )->justReturn( new \WP_User( [ 'ID' => 3, 'display_name' => 'Anna' ] ) );
		Functions\when( 'get_the_author_meta' )->justReturn( '' );
		Functions\when( 'get_author_posts_url' )->justReturn( 'https://example.com/author/anna/' );

		$html = $this->capture( static fn() => ( new ArchiveMeta( new TagRenderer() ) )->output_author() );

		$this->assertStringContainsString( '<link rel="canonical" href="https://example.com/author/anna/page/2/" />', $html );
	}

	public function test_paged_front_page_canonical_points_at_its_own_page(): void {
		Functions\when( 'get_queried_object' )->justReturn( null );
		Functions\when( 'get_bloginfo' )->justReturn( 'Site' );
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );

		$html = $this->capture( static fn() => ( new ArchiveMeta( new TagRenderer() ) )->output_home() );

		$this->assertStringContainsString( '<link rel="canonical" href="https://example.com/page/2/" />', $html );
	}

	public function test_first_term_page_canonical_is_the_term_link(): void {
		Functions\when( 'get_query_var' )->justReturn( 0 );
		Functions\when( 'get_queried_object' )->justReturn( new \WP_Term( [ 'term_id' => 5, 'taxonomy' => 'category', 'name' => 'News' ] ) );
		Functions\when( 'get_term_meta' )->justReturn( '' );
		Functions\when( 'single_term_title' )->justReturn( 'News' );
		Functions\when( 'term_description' )->justReturn( '' );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/news/' );

		$html = $this->capture( static fn() => ( new ArchiveMeta( new TagRenderer() ) )->output_taxonomy() );

		$this->assertStringContainsString( '<link rel="canonical" href="https://example.com/category/news/" />', $html );
	}
}
