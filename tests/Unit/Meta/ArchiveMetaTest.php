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

	/**
	 * Enable Open Graph and stub the site name / tagline.
	 *
	 * @param array<string, mixed> $saved Extra saved options.
	 */
	private function stub_social_site( array $saved = [] ): void {
		$this->stub_options( array_merge( [ 'opengraph_enabled' => true, 'twitter_enabled' => false ], $saved ) );
		Functions\when( 'get_bloginfo' )->alias( static fn( string $show ): string => 'description' === $show ? 'Tagline' : 'Site' );
		Functions\when( 'get_locale' )->justReturn( 'hu_HU' );
		Functions\when( 'get_query_var' )->justReturn( 0 );
	}

	public function test_variables_in_the_term_seo_texts_are_filled_in(): void {
		$this->stub_social_site();
		$meta = [
			'_lw_seo_title'          => '%%term_title%% %%sep%% %%sitename%%',
			'_lw_seo_description'    => 'All about %%term_title%%',
			'_lw_seo_og_description' => '%%term_title%% on %%sitename%%',
		];
		Functions\when( 'get_queried_object' )->justReturn( new \WP_Term( [ 'term_id' => 5, 'taxonomy' => 'category', 'name' => 'News' ] ) );
		Functions\when( 'get_term_meta' )->alias( static fn( int $id, string $key ): string => $meta[ $key ] ?? '' );
		Functions\when( 'single_term_title' )->justReturn( 'News' );
		Functions\when( 'term_description' )->justReturn( '' );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/news/' );

		$html = $this->capture( static fn() => ( new ArchiveMeta( new TagRenderer() ) )->output_taxonomy() );

		$this->assertStringNotContainsString( '%%', $html );
		$this->assertStringContainsString( '<meta property="og:title" content="News - Site" />', $html );
		$this->assertStringContainsString( '<meta name="description" content="All about News" />', $html );
		$this->assertStringContainsString( '<meta property="og:description" content="News on Site" />', $html );
	}

	public function test_variables_in_the_term_social_title_are_filled_in(): void {
		$this->stub_social_site();
		Functions\when( 'get_queried_object' )->justReturn( new \WP_Term( [ 'term_id' => 5, 'taxonomy' => 'category', 'name' => 'News' ] ) );
		Functions\when( 'get_term_meta' )->alias( static fn( int $id, string $key ): string => '_lw_seo_og_title' === $key ? '%%term_title%% shared' : '' );
		Functions\when( 'single_term_title' )->justReturn( 'News' );
		Functions\when( 'term_description' )->justReturn( '' );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/news/' );

		$html = $this->capture( static fn() => ( new ArchiveMeta( new TagRenderer() ) )->output_taxonomy() );

		$this->assertStringContainsString( '<meta property="og:title" content="News shared" />', $html );
	}

	public function test_variables_in_the_home_description_are_filled_in(): void {
		$this->stub_social_site( [ 'desc_home' => '%%sitename%% %%sep%% %%sitedesc%%' ] );
		Functions\when( 'get_queried_object' )->justReturn( null );
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );

		$html = $this->capture( static fn() => ( new ArchiveMeta( new TagRenderer() ) )->output_home() );

		$this->assertStringContainsString( '<meta name="description" content="Site - Tagline" />', $html );
		$this->assertStringContainsString( '<meta property="og:description" content="Site - Tagline" />', $html );
	}

	public function test_home_description_without_variables_is_printed_as_saved(): void {
		$this->stub_social_site( [ 'desc_home' => 'Handmade  coffee, 100% organic' ] );
		Functions\when( 'get_queried_object' )->justReturn( null );
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );

		$html = $this->capture( static fn() => ( new ArchiveMeta( new TagRenderer() ) )->output_home() );

		$this->assertStringContainsString( '<meta name="description" content="Handmade  coffee, 100% organic" />', $html );
	}

	public function test_variables_in_the_posts_page_titles_are_filled_in(): void {
		$this->stub_social_site();
		$this->stub_options( [ 'opengraph_enabled' => true ], [ 'page_for_posts' => 12 ] );
		$meta = [
			'_lw_seo_title'       => '%%title%% %%sep%% %%sitename%%',
			'_lw_seo_description' => 'Latest from %%sitename%%',
		];
		Functions\when( 'get_queried_object' )->justReturn( null );
		Functions\when( 'get_post' )->justReturn( new \WP_Post( [ 'ID' => 12, 'post_type' => 'page' ] ) );
		Functions\when( 'get_post_meta' )->alias( static fn( int $id, string $key ): string => $meta[ $key ] ?? '' );
		Functions\when( 'get_the_title' )->justReturn( 'Blog' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/blog/' );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );
		Functions\when( 'has_post_thumbnail' )->justReturn( false );

		$html = $this->capture( static fn() => ( new ArchiveMeta( new TagRenderer() ) )->output_posts_page() );

		$this->assertStringContainsString( '<meta property="og:title" content="Blog - Site" />', $html );
		$this->assertStringContainsString( '<meta name="description" content="Latest from Site" />', $html );
	}
}
