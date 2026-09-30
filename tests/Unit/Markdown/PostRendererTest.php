<?php
/**
 * PostRenderer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Markdown\PostRenderer;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class PostRendererTest extends MonkeyTestCase {

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * The title is plain text: link syntax and (decoded) tags in it must not
	 * become live Markdown in the heading.
	 */
	public function test_body_heading_escapes_title(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_title' )->justReturn( 'Hi [x](javascript:alert(1)) &lt;img src=x onerror=alert(1)&gt;' );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );

		$post = new \WP_Post(
			[
				'ID'           => 7,
				'post_content' => '<p>Body</p>',
			]
		);

		$this->assertSame(
			"# Hi \\[x\\](javascript:alert(1)) \\<img src=x onerror=alert(1)\\>\n\nBody\n",
			( new PostRenderer( $post ) )->body()
		);
	}

	/**
	 * Stub everything frontmatter() reads besides the excerpt.
	 *
	 * @return void
	 */
	private function stub_frontmatter_basics(): void {
		Functions\when( 'get_the_title' )->justReturn( 'Post' );
		Functions\when( 'get_permalink' )->justReturn( 'https://x.test/post/' );
		Functions\when( 'get_the_date' )->justReturn( '2026-09-26' );
		Functions\when( 'get_the_modified_date' )->justReturn( '2026-09-26' );
		Functions\when( 'get_the_author_meta' )->justReturn( 'Anna' );
		Functions\when( 'get_locale' )->justReturn( 'hu_HU' );
		Functions\when( 'get_the_category' )->justReturn( [] );
		Functions\when( 'get_the_tags' )->justReturn( false );
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );
		Functions\when( 'post_password_required' )->justReturn( false );
	}

	public function test_frontmatter_excerpt_uses_the_masked_excerpt(): void {
		$this->stub_frontmatter_basics();
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_excerpt' )->justReturn( 'Members teaser' );

		$post = new \WP_Post(
			[
				'ID'           => 7,
				'post_author'  => 1,
				'post_excerpt' => 'Secret summary',
			]
		);

		$this->assertSame( 'Members teaser', ( new PostRenderer( $post ) )->frontmatter()['excerpt'] );
	}

	public function test_frontmatter_excerpt_runs_through_the_description_filter(): void {
		$this->stub_frontmatter_basics();
		Functions\when( 'get_post_meta' )->alias(
			static fn( int $id, string $key ): string => '_lw_seo_description' === $key ? 'SEO summary' : ''
		);

		$post = new \WP_Post(
			[
				'ID'           => 7,
				'post_author'  => 1,
				'post_excerpt' => '',
			]
		);
		Filters\expectApplied( 'lw_seo_meta_description' )->once()->with( 'SEO summary', $post, 'markdown' )->andReturn( '' );

		$this->assertArrayNotHasKey( 'excerpt', ( new PostRenderer( $post ) )->frontmatter() );
	}

	public function test_body_renders_html_a_source_filter_supplies(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_title' )->justReturn( 'About' );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );
		Filters\expectApplied( 'lw_seo_markdown_source_html' )->andReturn( '<p>Built with Bricks</p>' );

		$post = new \WP_Post(
			[
				'ID'           => 7,
				'post_content' => '',
			]
		);

		$this->assertSame( "# About\n\nBuilt with Bricks\n", ( new PostRenderer( $post ) )->body() );
	}

	public function test_frontmatter_excerpt_fills_in_variables_of_the_seo_description(): void {
		Options::clear_cache();
		$this->stub_frontmatter_basics();
		Functions\when( 'get_bloginfo' )->justReturn( 'Site' );
		Functions\when( 'get_option' )->justReturn( [] );
		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ) => array_merge( $defaults, (array) $args ) );
		Functions\when( 'get_post_meta' )->alias(
			static fn( int $id, string $key ): string => '_lw_seo_description' === $key ? 'Custom %%title%% %%sep%% %%sitename%%' : ''
		);

		$post = new \WP_Post(
			[
				'ID'           => 7,
				'post_author'  => 1,
				'post_excerpt' => '',
			]
		);

		$this->assertSame( 'Custom Post - Site', ( new PostRenderer( $post ) )->frontmatter()['excerpt'] );
	}
}
