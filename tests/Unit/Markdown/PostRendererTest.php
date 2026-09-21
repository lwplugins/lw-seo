<?php
/**
 * PostRenderer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Markdown\PostRenderer;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class PostRendererTest extends MonkeyTestCase {

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
}
