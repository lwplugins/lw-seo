<?php
/**
 * Watcher unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\IndexNow;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\IndexNow\Watcher;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class WatcherTest extends MonkeyTestCase {

	use OptionsStubTrait;

	/** @var array<int, string> */
	private array $queued = [];

	protected function setUp(): void {
		parent::setUp();
		$this->queued = [];
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\when( 'is_taxonomy_viewable' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_term_meta' )->justReturn( '' );
		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_is_post_autosave' )->justReturn( false );
		Functions\when( 'get_permalink' )->alias( static fn( $post ): string => 'https://e.com/p' . $post->ID . '/' );
		Functions\when( 'wp_next_scheduled' )->justReturn( 1 );
		Functions\when( 'update_option' )->alias(
			function ( string $name, $value ): bool {
				$this->queued = $value;
				return true;
			}
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	private function enabled( array $extra = [], array $other = [] ): Watcher {
		$this->stub_options( array_merge( [ 'indexnow_enabled' => true ], $extra ), $other );
		Functions\when( 'add_action' )->justReturn( true );
		return new Watcher();
	}

	private function post( array $props = [] ): \WP_Post {
		return new \WP_Post( array_merge( [ 'ID' => 5, 'post_status' => 'publish', 'post_password' => '', 'post_type' => 'post', 'post_name' => 'slug' ], $props ) );
	}

	public function test_publish_queues_url(): void {
		$this->enabled()->on_transition( 'publish', 'draft', $this->post() );

		$this->assertSame( [ 'https://e.com/p5/' ], $this->queued );
	}

	public function test_trash_queues_url(): void {
		$this->enabled()->on_transition( 'trash', 'publish', $this->post( [ 'post_status' => 'trash' ] ) );

		$this->assertSame( [ 'https://e.com/p5/' ], $this->queued );
	}

	public function test_draft_to_draft_ignored(): void {
		$this->enabled()->on_transition( 'draft', 'pending', $this->post( [ 'post_status' => 'draft' ] ) );

		$this->assertSame( [], $this->queued );
	}

	public function test_skips_noindex_excluded_password(): void {
		$this->enabled( [ 'noindex_post' => true ] )->on_transition( 'publish', 'draft', $this->post() );
		$this->assertSame( [], $this->queued, 'type noindex' );

		$this->enabled()->on_transition( 'publish', 'draft', $this->post( [ 'post_password' => 'x' ] ) );
		$this->assertSame( [], $this->queued, 'password' );

		Functions\when( 'apply_filters' )->alias( static fn( string $tag, $value ) => 'lw_seo_sitemap_excluded_ids' === $tag ? [ 5 ] : $value );
		$this->enabled()->on_transition( 'publish', 'draft', $this->post() );
		$this->assertSame( [], $this->queued, 'excluded id' );
	}

	public function test_removal_queues_url_captured_before_update(): void {
		$watcher = $this->enabled();
		Functions\when( 'get_post' )->justReturn( $this->post() );
		Functions\when( 'get_permalink' )->justReturn( 'https://e.com/pretty/' );
		$watcher->before_post_update( 5 );
		Functions\when( 'get_permalink' )->justReturn( 'https://e.com/?p=5' );

		$watcher->on_transition( 'trash', 'publish', $this->post( [ 'post_status' => 'trash' ] ) );

		$this->assertSame( [ 'https://e.com/pretty/' ], $this->queued );
	}

	public function test_capture_restores_slug_from_trash_suffix(): void {
		$watcher = $this->enabled();
		Functions\when( 'get_post' )->justReturn( $this->post( [ 'post_name' => 'slug__trashed' ] ) );
		Functions\when( 'get_post_meta' )->alias( static fn( $id, $key ) => '_wp_desired_post_slug' === $key ? 'slug' : '' );
		Functions\when( 'get_permalink' )->alias( static fn( $post ): string => 'https://e.com/' . $post->post_name . '/' );
		$watcher->before_post_update( 5 );
		Functions\when( 'get_permalink' )->justReturn( 'https://e.com/?p=5' );

		$watcher->on_transition( 'trash', 'publish', $this->post( [ 'post_status' => 'trash', 'post_name' => 'slug__trashed' ] ) );

		$this->assertSame( [ 'https://e.com/slug/' ], $this->queued );
	}

	public function test_removal_fallback_uses_published_copy_without_trash_suffix(): void {
		Functions\when( 'get_permalink' )->alias(
			static fn( $post ): string => 'https://e.com/' . $post->post_name . '/' . $post->post_status
		);

		$this->enabled()->on_transition( 'trash', 'publish', $this->post( [ 'post_status' => 'trash', 'post_name' => 'hello__trashed' ] ) );

		$this->assertSame( [ 'https://e.com/hello/publish' ], $this->queued );
	}

	public function test_skips_removal_of_password_or_excluded_post(): void {
		$this->enabled()->on_transition( 'trash', 'publish', $this->post( [ 'post_status' => 'trash', 'post_password' => 'x' ] ) );
		$this->assertSame( [], $this->queued, 'password' );

		Functions\when( 'apply_filters' )->alias( static fn( string $tag, $value ) => 'lw_seo_sitemap_excluded_ids' === $tag ? [ 5 ] : $value );
		$this->enabled()->on_transition( 'trash', 'publish', $this->post( [ 'post_status' => 'trash' ] ) );
		$this->assertSame( [], $this->queued, 'excluded' );
	}

	public function test_skips_removal_of_noindex_type(): void {
		$this->enabled( [ 'noindex_post' => true ] )->on_transition( 'trash', 'publish', $this->post( [ 'post_status' => 'trash' ] ) );

		$this->assertSame( [], $this->queued );
	}

	public function test_skips_revision(): void {
		Functions\when( 'wp_is_post_revision' )->justReturn( 9 );

		$this->enabled()->on_transition( 'publish', 'draft', $this->post() );

		$this->assertSame( [], $this->queued );
	}

	public function test_skips_autosave(): void {
		Functions\when( 'wp_is_post_autosave' )->justReturn( 9 );

		$this->enabled()->on_transition( 'publish', 'draft', $this->post() );

		$this->assertSame( [], $this->queued );
	}

	public function test_skips_non_viewable_type(): void {
		Functions\when( 'is_post_type_viewable' )->justReturn( false );

		$this->enabled()->on_transition( 'publish', 'draft', $this->post() );

		$this->assertSame( [], $this->queued );
	}

	public function test_nothing_hooked_when_disabled(): void {
		$this->stub_options( [ 'indexnow_enabled' => false ] );
		Functions\expect( 'add_action' )->never();

		new Watcher();
		$this->addToAssertionCount( 1 );
	}

	public function test_term_edited_queues_link(): void {
		Functions\when( 'get_term' )->justReturn( new \WP_Term( [ 'term_id' => 3, 'taxonomy' => 'category' ] ) );
		Functions\when( 'get_term_link' )->justReturn( 'https://e.com/category/x/' );
		Functions\when( 'is_wp_error' )->justReturn( false );

		$this->enabled()->on_term_edited( 3, 3, 'category' );

		$this->assertSame( [ 'https://e.com/category/x/' ], $this->queued );
	}

	public function test_term_delete_uses_link_captured_before_deletion(): void {
		Functions\when( 'get_term' )->justReturn( new \WP_Term( [ 'term_id' => 3, 'taxonomy' => 'category' ] ) );
		Functions\when( 'get_term_link' )->justReturn( 'https://e.com/category/x/' );

		$watcher = $this->enabled();
		$watcher->before_term_delete( 3, 'category' );
		Functions\when( 'get_term' )->justReturn( false );
		$watcher->on_term_deleted( 3 );

		$this->assertSame( [ 'https://e.com/category/x/' ], $this->queued );
	}
}
