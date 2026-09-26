<?php
/**
 * SectionCollector unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\LlmsTxt;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\LlmsTxt\SectionCollector;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class SectionCollectorTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'post_password_required' )->justReturn( false );
		Functions\when( 'get_the_excerpt' )->alias( static fn( \WP_Post $post ): string => (string) $post->post_excerpt );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * @return array<string, array{0: mixed, 1: int}>
	 */
	public static function limit_provider(): array {
		return [
			'missing'  => [ null, 100 ],
			'zero'     => [ 0, 100 ],
			'in range' => [ '50', 50 ],
			'too high' => [ 9999, 500 ],
		];
	}

	/**
	 * @dataProvider limit_provider
	 *
	 * @param mixed $value    Stored value.
	 * @param int   $expected Clamped limit.
	 */
	public function test_limit_is_clamped( $value, int $expected ): void {
		$this->assertSame( $expected, SectionCollector::limit( $value ) );
	}

	public function test_parse_links_accepts_title_url_and_optional_description(): void {
		$text = "Blog | https://x.test/blog/\n\nbad line\nFTP | ftp://x.test/\nShop | https://x.test/shop/ | Online bolt";

		$this->assertSame(
			[
				[ 'title' => 'Blog', 'url' => 'https://x.test/blog/', 'description' => '' ],
				[ 'title' => 'Shop', 'url' => 'https://x.test/shop/', 'description' => 'Online bolt' ],
			],
			SectionCollector::parse_links( $text )
		);
	}

	public function test_post_types_honours_llms_toggles(): void {
		$this->stub_options( [ 'llms_txt_post_types' => [ 'post' => false ] ] );
		Functions\when( 'get_post_types' )->justReturn(
			[
				'post'       => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Posts' ] ] ),
				'page'       => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Pages' ] ] ),
				'case_study' => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Case Studies' ] ] ),
			]
		);
		Functions\when( 'is_post_type_viewable' )->justReturn( true );

		$this->assertSame( [ 'page' => 'Pages', 'case_study' => 'Case Studies' ], SectionCollector::post_types() );
	}

	/**
	 * Stub the post types, the per-type query and the eligibility checks
	 * posts() runs through. Every type in $with_posts gets one eligible
	 * post; the others get none.
	 *
	 * @param array<string, string> $labels     Post type name => plural label.
	 * @param array<int, string>    $with_posts Types that have a listable post.
	 */
	private function stub_listed_types( array $labels, array $with_posts ): void {
		$this->stub_options();
		Functions\when( 'get_post_types' )->justReturn(
			array_map( static fn( string $label ): \WP_Post_Type => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => $label ] ] ), $labels )
		);
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\when( 'is_post_type_hierarchical' )->justReturn( false );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'get_posts' )->alias(
			fn( array $args ): array => in_array( $args['post_type'], $with_posts, true )
				? [ $this->post( [ 'post_type' => $args['post_type'], 'post_status' => 'publish', 'post_password' => '' ] ) ]
				: []
		);
	}

	/**
	 * Headings and post types of each collected section.
	 *
	 * @param array<string, array{heading: string, posts: array<int, \WP_Post>}> $sections Sections.
	 * @return array<string, array{0: string, 1: array<int, string>}>
	 */
	private static function outline( array $sections ): array {
		return array_map( static fn( array $section ): array => [ $section['heading'], array_column( $section['posts'], 'post_type' ) ], $sections );
	}

	public function test_posts_keys_sections_by_post_type_and_keeps_same_label_types_apart(): void {
		$this->stub_listed_types(
			[
				'post'         => 'Posts',
				'event'        => 'Events',
				'tribe_events' => 'Events',
				'extras'       => 'Optional',
			],
			[ 'post', 'event', 'tribe_events', 'extras' ]
		);

		$this->assertSame(
			[
				'post'         => [ 'Posts', [ 'post' ] ],
				'event'        => [ 'Events (event)', [ 'event' ] ],
				'tribe_events' => [ 'Events (tribe_events)', [ 'tribe_events' ] ],
				'extras'       => [ 'Optional (extras)', [ 'extras' ] ],
			],
			self::outline( ( new SectionCollector() )->posts() )
		);
	}

	public function test_posts_leaves_a_heading_alone_when_its_twin_type_has_nothing_listed(): void {
		$this->stub_listed_types(
			[
				'event'        => 'Events',
				'tribe_events' => 'Events',
			],
			[ 'event' ]
		);

		$this->assertSame( [ 'event' => [ 'Events', [ 'event' ] ] ], self::outline( ( new SectionCollector() )->posts() ) );
	}

	/**
	 * @param array<string, mixed> $props Overrides.
	 */
	private function post( array $props = [] ): \WP_Post {
		return new \WP_Post(
			array_merge(
				[
					'ID'           => 42,
					'post_title'   => 'A post',
					'post_excerpt' => 'Fallback excerpt',
				],
				$props
			)
		);
	}

	public function test_link_uses_description_meta_when_set(): void {
		$this->stub_options();
		Functions\when( 'get_the_title' )->justReturn( 'A post' );
		Functions\when( 'get_permalink' )->justReturn( 'https://x.test/a/' );
		Functions\when( 'wp_trim_words' )->returnArg( 1 );
		Functions\when( 'get_post_meta' )->alias(
			static fn( int $id, string $key ): string => '_lw_seo_description' === $key ? 'Meta description' : ''
		);

		$link = SectionCollector::link( $this->post(), false );

		$this->assertSame( 'Meta description', $link['description'] );
	}

	public function test_link_falls_back_to_post_excerpt_when_description_meta_is_empty(): void {
		$this->stub_options();
		Functions\when( 'get_the_title' )->justReturn( 'A post' );
		Functions\when( 'get_permalink' )->justReturn( 'https://x.test/a/' );
		Functions\when( 'wp_trim_words' )->returnArg( 1 );
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$link = SectionCollector::link( $this->post(), false );

		$this->assertSame( 'Fallback excerpt', $link['description'] );
	}

	public function test_description_prefers_the_description_meta_over_the_excerpt(): void {
		Functions\when( 'get_post_meta' )->alias(
			static fn( int $id, string $key ): string => '_lw_seo_description' === $key ? 'Meta description' : ''
		);

		$this->assertSame( 'Meta description', SectionCollector::description( $this->post() ) );
	}

	public function test_description_falls_back_to_the_excerpt(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$this->assertSame( 'Fallback excerpt', SectionCollector::description( $this->post() ) );
	}

	public function test_description_is_not_trimmed(): void {
		$long = implode( ' ', array_fill( 0, 40, 'word' ) );
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$this->assertSame( $long, SectionCollector::description( $this->post( [ 'post_excerpt' => $long ] ) ) );
	}

	public function test_description_runs_through_the_description_filter_with_llms_context(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );
		$post = $this->post();
		Filters\expectApplied( 'lw_seo_meta_description' )->once()->with( 'Fallback excerpt', $post, 'llms' )->andReturn( '' );

		$this->assertSame( '', SectionCollector::description( $post ) );
	}

	public function test_description_uses_the_masked_excerpt(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_excerpt' )->justReturn( 'Members teaser' );

		$this->assertSame( 'Members teaser', SectionCollector::description( $this->post() ) );
	}

	public function test_description_skips_the_excerpt_of_a_password_protected_post(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'post_password_required' )->justReturn( true );

		$this->assertSame( '', SectionCollector::description( $this->post() ) );
	}

	public function test_link_uses_markdown_url_when_requested(): void {
		$this->stub_options( [], [ 'permalink_structure' => '/%postname%/' ] );
		Functions\when( 'get_the_title' )->justReturn( 'A post' );
		Functions\when( 'get_permalink' )->justReturn( 'https://x.test/a/' );
		Functions\when( 'wp_trim_words' )->returnArg( 1 );
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$link = SectionCollector::link( $this->post(), true );

		$this->assertSame( 'https://x.test/a/md/', $link['url'] );
	}

	public function test_link_uses_plain_permalink_when_markdown_not_requested(): void {
		$this->stub_options();
		Functions\when( 'get_the_title' )->justReturn( 'A post' );
		Functions\when( 'get_permalink' )->justReturn( 'https://x.test/a/' );
		Functions\when( 'wp_trim_words' )->returnArg( 1 );
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$link = SectionCollector::link( $this->post(), false );

		$this->assertSame( 'https://x.test/a/', $link['url'] );
	}
}
