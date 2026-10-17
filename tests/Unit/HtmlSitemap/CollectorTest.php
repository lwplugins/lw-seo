<?php
/**
 * HTML sitemap Collector unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\HtmlSitemap;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Content\Eligibility;
use LightweightPlugins\SEO\HtmlSitemap\Collector;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\HtmlSitemap\Collector
 */
final class CollectorTest extends MonkeyTestCase {

	use OptionsStubTrait;

	/**
	 * Posts returned by get_posts(), by post type.
	 *
	 * @var array<string, array<int, \WP_Post>>
	 */
	private array $posts = [];

	/**
	 * Arguments of the last get_posts() call per post type.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $queries = [];

	protected function setUp(): void {
		parent::setUp();

		$this->stub_options();
		$this->posts   = [
			'post' => [ $this->post( 1, 'post', 'Hello' ), $this->post( 2, 'post', 'World' ) ],
			'page' => [ $this->post( 10, 'page', 'About' ), $this->post( 11, 'page', 'Team', 10 ), $this->post( 12, 'page', 'Bios', 11 ) ],
		];
		$this->queries = [];

		Functions\when( 'get_post_types' )->justReturn(
			[
				'post' => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Posts' ] ] ),
				'page' => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Pages' ] ] ),
			]
		);
		Functions\when( 'get_taxonomies' )->justReturn(
			[ 'category' => new \WP_Taxonomy( [ 'labels' => (object) [ 'name' => 'Categories' ] ] ) ]
		);
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\when( 'is_taxonomy_viewable' )->justReturn( true );
		Functions\when( 'is_post_type_hierarchical' )->alias( static fn( string $type ): bool => 'page' === $type );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_title' )->alias( static fn( \WP_Post $post ): string => (string) $post->post_title );
		Functions\when( 'get_permalink' )->alias( static fn( \WP_Post $post ): string => 'https://example.com/?p=' . $post->ID );
		Functions\when( 'get_post_type_archive_link' )->justReturn( false );
		Functions\when( 'get_posts' )->alias(
			function ( array $args ): array {
				$this->queries[ $args['post_type'] ] = $args;
				return $this->posts[ $args['post_type'] ] ?? [];
			}
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	private function post( int $id, string $type, string $title, int $parent = 0 ): \WP_Post {
		return new \WP_Post(
			[
				'ID'            => $id,
				'post_type'     => $type,
				'post_status'   => 'publish',
				'post_password' => '',
				'post_title'    => $title,
				'post_parent'   => $parent,
			]
		);
	}

	public function test_defaults_to_posts_and_pages(): void {
		$sections = Collector::sections( [], [], 500, 'title' );

		$this->assertSame( [ 'Posts', 'Pages' ], array_column( $sections, 'label' ) );
		$this->assertSame( 'post_type', $sections[0]['type'] );
		$this->assertSame( 'https://example.com/?p=1', $sections[0]['items'][0]['url'] );
		$this->assertSame( 'Hello', $sections[0]['items'][0]['title'] );
	}

	public function test_skips_noindex_type(): void {
		$this->stub_options( [ 'noindex_page' => true ] );

		$sections = Collector::sections( [ 'post', 'page' ], [], 500, 'title' );

		$this->assertSame( [ 'Posts' ], array_column( $sections, 'label' ) );
	}

	public function test_skips_excluded_ids(): void {
		Filters\expectApplied( 'lw_seo_sitemap_excluded_ids' )->andReturn( [ 2 ] );

		$sections = Collector::sections( [ 'post' ], [], 500, 'title' );

		$this->assertSame( [ 'Hello' ], array_column( $sections[0]['items'], 'title' ) );
	}

	public function test_caps_items_and_adds_archive_link(): void {
		Functions\when( 'get_post_type_archive_link' )->justReturn( 'https://example.com/blog/' );

		$sections = Collector::sections( [ 'post' ], [], 1, 'title' );

		$this->assertCount( 1, $sections[0]['items'] );
		$this->assertSame( 'https://example.com/blog/', $sections[0]['archive_url'] );
		$this->assertGreaterThan( 1, $this->queries['post']['posts_per_page'] );
	}

	public function test_no_archive_link_when_within_cap(): void {
		Functions\when( 'get_post_type_archive_link' )->justReturn( 'https://example.com/blog/' );

		$sections = Collector::sections( [ 'post' ], [], 500, 'title' );

		$this->assertNull( $sections[0]['archive_url'] );
	}

	public function test_pages_get_depth(): void {
		$sections = Collector::sections( [ 'page' ], [], 500, 'title' );

		$this->assertSame( [ 0, 1, 2 ], array_column( $sections[0]['items'], 'depth' ) );
	}

	public function test_order_by_date_is_passed_to_the_query(): void {
		Collector::sections( [ 'post' ], [], 500, 'date' );

		$this->assertSame( 'date', $this->queries['post']['orderby'] );
		$this->assertSame( 'DESC', $this->queries['post']['order'] );
	}

	public function test_unknown_type_is_ignored(): void {
		$sections = Collector::sections( [ 'nope', 'post' ], [], 500, 'title' );

		$this->assertSame( [ 'Posts' ], array_column( $sections, 'label' ) );
	}

	public function test_lists_taxonomy_terms(): void {
		Functions\when( 'get_terms' )->justReturn(
			[
				new \WP_Term( [ 'term_id' => 5, 'name' => 'News', 'parent' => 0 ] ),
				new \WP_Term( [ 'term_id' => 6, 'name' => 'Local', 'parent' => 5 ] ),
			]
		);
		Functions\when( 'get_term_link' )->alias( static fn( \WP_Term $term ): string => 'https://example.com/c/' . $term->term_id );
		Functions\when( 'is_wp_error' )->justReturn( false );

		$sections = Collector::sections( [ 'post' ], [ 'category' ], 500, 'title' );

		$this->assertSame( 'taxonomy', $sections[1]['type'] );
		$this->assertSame( [ 0, 1 ], array_column( $sections[1]['items'], 'depth' ) );
		$this->assertSame( 'https://example.com/c/6', $sections[1]['items'][1]['url'] );
	}

	public function test_term_query_excludes_noindex_terms(): void {
		$captured = null;
		Functions\when( 'get_terms' )->alias(
			static function ( array $args ) use ( &$captured ): array {
				$captured = $args;
				return [];
			}
		);
		Functions\when( 'is_wp_error' )->justReturn( false );

		Collector::sections( [ 'post' ], [ 'category' ], 500, 'title' );

		$this->assertSame( Eligibility::noindex_meta_query(), $captured['meta_query'] );
		$this->assertSame( 'term_id', $captured['orderby'] );
	}
}
