<?php
/**
 * ContentSource unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use Brain\Monkey\Filters;
use Bricks\Database;
use Bricks\Helpers;
use LightweightPlugins\SEO\Markdown\ContentSource;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class ContentSourceTest extends MonkeyTestCase {

	protected function tearDown(): void {
		Database::test_reset();
		parent::tearDown();
	}

	public function test_uses_the_bricks_content_of_a_bricks_page(): void {
		Helpers::$test_bricks_posts = [ 7 ];
		Database::$test_content[7]  = [ [ 'id' => 'abc123', 'settings' => [ 'text' => 'Built with Bricks' ] ] ];

		$this->assertSame( '<p>Built with Bricks</p>', ContentSource::html( $this->post() ) );
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
