<?php
/**
 * QueueResolver unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\IndexNow;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\WooCommerce\CatalogVisibility;
use LightweightPlugins\SEO\IndexNow\QueueResolver;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class QueueResolverTest extends MonkeyTestCase {

	use OptionsStubTrait;

	/** @var array<int, \WP_Post> */
	private array $posts = [];

	protected function setUp(): void {
		parent::setUp();
		$this->stub_options();
		$this->posts = [
			5 => $this->post( 5 ),
			6 => $this->post( 6, [ 'post_type' => 'product' ] ),
		];
		Functions\when( '_prime_post_caches' )->justReturn( null );
		Functions\when( 'get_post' )->alias( fn( int $id ) => $this->posts[ $id ] ?? null );
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\when( 'is_taxonomy_viewable' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_term_meta' )->justReturn( '' );
		Functions\when( 'has_term' )->justReturn( false );
		Functions\when( 'get_permalink' )->alias( static fn( \WP_Post $post ): string => 'https://e.com/p' . $post->ID . '/' );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	private function post( int $id, array $props = [] ): \WP_Post {
		return new \WP_Post( array_merge( [ 'ID' => $id, 'post_status' => 'publish', 'post_password' => '', 'post_type' => 'post' ], $props ) );
	}

	/**
	 * @param array<int, int>    $posts Post IDs.
	 * @param array<int, int>    $terms Term IDs.
	 * @param array<int, string> $urls  URLs.
	 * @return array<int, string>
	 */
	private function resolve( array $posts, array $terms = [], array $urls = [] ): array {
		return QueueResolver::urls(
			[
				'posts' => $posts,
				'terms' => $terms,
				'urls'  => $urls,
			]
		);
	}

	public function test_eligible_post_resolves_to_its_permalink(): void {
		$this->assertSame( [ 'https://e.com/p5/' ], $this->resolve( [ 5 ] ) );
	}

	public function test_post_noindexed_after_queueing_is_not_sent(): void {
		Functions\when( 'get_post_meta' )->alias( static fn( int $id, string $key ) => '_lw_seo_noindex' === $key ? '1' : '' );

		$this->assertSame( [], $this->resolve( [ 5 ] ) );
	}

	public function test_hidden_product_is_not_sent(): void {
		$visibility = new CatalogVisibility();
		Functions\when( 'apply_filters' )->alias(
			static fn( string $tag, $value, ...$args ) => 'lw_seo_post_is_eligible' === $tag ? $visibility->filter_eligible( $value, $args[0] ) : $value
		);
		Functions\when( 'has_term' )->justReturn( true );

		$this->assertSame( [ 'https://e.com/p5/' ], $this->resolve( [ 5, 6 ] ) );
	}

	public function test_excluded_or_missing_post_is_not_sent(): void {
		Functions\when( 'apply_filters' )->alias( static fn( string $tag, $value ) => 'lw_seo_sitemap_excluded_ids' === $tag ? [ 5 ] : $value );

		$this->assertSame( [], $this->resolve( [ 5, 99 ] ) );
	}

	public function test_post_unpublished_before_flush_is_not_sent(): void {
		$this->posts[5] = $this->post( 5, [ 'post_status' => 'draft' ] );

		$this->assertSame( [], $this->resolve( [ 5 ] ) );
	}

	public function test_removal_urls_are_sent_as_captured(): void {
		$this->assertSame( [ 'https://e.com/gone/' ], $this->resolve( [], [], [ 'https://e.com/gone/' ] ) );
	}

	public function test_terms_resolve_only_when_eligible(): void {
		Functions\when( 'get_term' )->alias( static fn( int $id ) => new \WP_Term( [ 'term_id' => $id, 'taxonomy' => 'category' ] ) );
		Functions\when( 'get_term_link' )->alias( static fn( \WP_Term $term ): string => 'https://e.com/c' . $term->term_id . '/' );
		Functions\when( 'get_term_meta' )->alias( static fn( int $id ) => 4 === $id ? '1' : '' );

		$this->assertSame( [ 'https://e.com/c3/' ], $this->resolve( [], [ 3, 4 ] ) );
	}
}
