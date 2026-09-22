<?php
/**
 * PostRenderer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use Brain\Monkey\Functions;
use Bricks\Database;
use Bricks\Helpers;
use LightweightPlugins\SEO\Markdown\PostRenderer;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class PostRendererTest extends MonkeyTestCase {

	protected function tearDown(): void {
		Database::test_reset();
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

	public function test_body_renders_the_bricks_content_of_a_bricks_page(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_title' )->justReturn( 'About' );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );
		Helpers::$test_bricks_posts = [ 7 ];
		Database::$test_content[7]  = [ [ 'id' => 'abc123', 'settings' => [ 'text' => 'Built with Bricks' ] ] ];

		$post = new \WP_Post(
			[
				'ID'           => 7,
				'post_content' => '',
			]
		);

		$this->assertSame( "# About\n\nBuilt with Bricks\n", ( new PostRenderer( $post ) )->body() );
	}
}
