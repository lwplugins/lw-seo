<?php
/**
 * MarkdownContent unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\Bricks;

use Bricks\Database;
use Bricks\Frontend;
use Bricks\Helpers;
use LightweightPlugins\SEO\Integrations\Bricks\MarkdownContent;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class MarkdownContentTest extends MonkeyTestCase {

	protected function tearDown(): void {
		Database::test_reset();
		parent::tearDown();
	}

	public function test_filter_keeps_html_another_source_supplied(): void {
		$this->assertSame( '<p>Other</p>', MarkdownContent::filter( '<p>Other</p>', $this->post( 7 ) ) );
	}

	public function test_filter_supplies_the_bricks_content(): void {
		$this->bricks_post( 7, 'Hello' );

		$this->assertSame( '<p>Hello</p>', MarkdownContent::filter( '', $this->post( 7 ) ) );
	}

	public function test_filter_ignores_a_non_string_value(): void {
		$this->bricks_post( 7, 'Hello' );

		$this->assertSame( '<p>Hello</p>', MarkdownContent::filter( null, $this->post( 7 ) ) );
	}

	public function test_renders_the_posts_own_bricks_content(): void {
		$this->bricks_post( 7, 'Hello' );

		$this->assertSame( '<p>Hello</p>', MarkdownContent::html( $this->post( 7 ) ) );
	}

	/**
	 * Editor mode "WordPress": the page shows post_content, not the stored
	 * Bricks data.
	 */
	public function test_returns_empty_when_the_post_does_not_render_with_bricks(): void {
		Database::$test_content[7] = [ $this->element( 'Stale Bricks data' ) ];

		$this->assertSame( '', MarkdownContent::html( $this->post( 7 ) ) );
	}

	/**
	 * A post shown through a Bricks template has no Bricks data of its own.
	 */
	public function test_returns_empty_when_the_post_has_no_bricks_content(): void {
		Helpers::$test_bricks_posts = [ 7 ];

		$this->assertSame( '', MarkdownContent::html( $this->post( 7 ) ) );
	}

	/**
	 * Dynamic data in the elements resolves against the rendered post, and
	 * the page Bricks is serving gets its own post back afterwards.
	 */
	public function test_points_bricks_at_the_post_while_rendering_and_restores_it(): void {
		$this->bricks_post( 7, 'Hello' );
		Database::$page_data['preview_or_post_id'] = 99;

		MarkdownContent::html( $this->post( 7 ) );

		$this->assertSame( [ [ 7 ], 99 ], [ Frontend::$test_seen_preview_ids, Database::$page_data['preview_or_post_id'] ] );
	}

	private function bricks_post( int $id, string $text ): void {
		Helpers::$test_bricks_posts  = [ $id ];
		Database::$test_content[ $id ] = [ $this->element( $text ) ];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function element( string $text ): array {
		return [
			'id'       => 'abc123',
			'name'     => 'text',
			'settings' => [ 'text' => $text ],
		];
	}

	private function post( int $id ): \WP_Post {
		return new \WP_Post( [ 'ID' => $id, 'post_type' => 'page' ] );
	}
}
