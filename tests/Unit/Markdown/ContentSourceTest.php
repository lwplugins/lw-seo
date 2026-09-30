<?php
/**
 * ContentSource unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use Brain\Monkey\Filters;
use LightweightPlugins\SEO\Markdown\ContentSource;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class ContentSourceTest extends MonkeyTestCase {

	public function test_uses_the_html_a_source_filter_supplies(): void {
		Filters\expectApplied( 'lw_seo_markdown_source_html' )->once()->andReturn( '<p>Built with a builder</p>' );

		$this->assertSame( '<p>Built with a builder</p>', ContentSource::html( $this->post() ) );
	}

	public function test_ignores_a_non_string_source_value(): void {
		Filters\expectApplied( 'lw_seo_markdown_source_html' )->andReturn( [ 'bad' ] );
		Filters\expectApplied( 'the_content' )->andReturn( '<p>Hi</p>' );

		$this->assertSame( '<p>Hi</p>', ContentSource::html( $this->post() ) );
	}

	public function test_runs_post_content_through_the_content_otherwise(): void {
		Filters\expectApplied( 'the_content' )->with( '<!-- wp:paragraph -->Hi' )->andReturn( '<p>Hi</p>' );

		$this->assertSame( '<p>Hi</p>', ContentSource::html( $this->post() ) );
	}

	private function post(): \WP_Post {
		return new \WP_Post(
			[
				'ID'           => 7,
				'post_type'    => 'page',
				'post_content' => '<!-- wp:paragraph -->Hi',
			]
		);
	}
}
