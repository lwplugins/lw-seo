<?php
/**
 * Markdown Dispatcher unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Markdown\Dispatcher;
use LightweightPlugins\SEO\Markdown\RendererInterface;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class DispatcherTest extends MonkeyTestCase {

	public function test_uses_the_renderer_the_filter_supplies(): void {
		$renderer = new class() implements RendererInterface {

			public function frontmatter(): array {
				return [];
			}

			public function body(): string {
				return 'custom body';
			}
		};
		Filters\expectApplied( 'lw_seo_markdown_renderer' )->once()->andReturn( $renderer );

		$post = new \WP_Post(
			[
				'ID'        => 9,
				'post_type' => 'product',
			]
		);

		$this->assertSame( 'custom body', Dispatcher::body( $post ) );
	}

	public function test_falls_back_to_the_post_renderer_for_a_non_renderer_value(): void {
		Filters\expectApplied( 'lw_seo_markdown_renderer' )->once()->andReturn( 'not a renderer' );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_title' )->justReturn( 'About' );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );

		$post = new \WP_Post(
			[
				'ID'           => 7,
				'post_type'    => 'page',
				'post_content' => '',
			]
		);

		$this->assertStringStartsWith( '# About', Dispatcher::body( $post ) );
	}
}
