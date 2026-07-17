<?php
/**
 * Characterization tests for Breadcrumbs.
 *
 * These tests pin down the CURRENT behaviour (see .claude/rules/tests.md).
 * Odd behaviour is asserted as-is and flagged with a TODO comment — it is
 * never "fixed" in a characterization phase.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Breadcrumbs;
use LightweightPlugins\SEO\Options;

/**
 * @covers \LightweightPlugins\SEO\Breadcrumbs
 */
final class BreadcrumbsTest extends MonkeyTestCase {

	private const CONDITIONALS = [
		'is_front_page',
		'is_home',
		'is_singular',
		'is_category',
		'is_tag',
		'is_tax',
		'is_author',
		'is_date',
		'is_search',
		'is_404',
		'is_archive',
	];

	/**
	 * Stub the WordPress environment: options, translations, escaping, and
	 * every conditional tag defaulting to false.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();

		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();
		Functions\when( 'add_shortcode' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( [] );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.test' . $path );

		foreach ( self::CONDITIONALS as $conditional ) {
			Functions\when( $conditional )->justReturn( false );
		}
	}

	/**
	 * Keep the static Options cache from leaking into other test classes.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Front page: only the Home item, rendered without a link.
	 */
	public function test_front_page_renders_home_only(): void {
		Functions\when( 'is_front_page' )->justReturn( true );

		$this->assertSame(
			'<nav class="lw-breadcrumbs" aria-label="Breadcrumb">'
			. '<ol itemscope itemtype="https://schema.org/BreadcrumbList">'
			. '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">'
			. '<span itemprop="name">Home</span>'
			. '<meta itemprop="position" content="1" />'
			. '</li>'
			. '</ol>'
			. '</nav>',
			( new Breadcrumbs() )->render()
		);
	}

	/**
	 * Search result page: Home link, separator, unlinked current item.
	 */
	public function test_search_renders_home_link_separator_and_current(): void {
		Functions\when( 'is_search' )->justReturn( true );
		Functions\when( 'get_search_query' )->justReturn( 'piros alma' );

		$this->assertSame(
			'<nav class="lw-breadcrumbs" aria-label="Breadcrumb">'
			. '<ol itemscope itemtype="https://schema.org/BreadcrumbList">'
			. '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">'
			. '<a itemprop="item" href="https://example.test/">'
			. '<span itemprop="name">Home</span>'
			. '</a>'
			. '<meta itemprop="position" content="1" />'
			. '</li>'
			. '<li class="separator" aria-hidden="true">»</li>'
			. '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">'
			. '<span itemprop="name">Search: piros alma</span>'
			. '<meta itemprop="position" content="2" />'
			. '</li>'
			. '</ol>'
			. '</nav>',
			( new Breadcrumbs() )->render()
		);
	}

	/**
	 * Leaf scenarios produce the expected item lists.
	 *
	 * @dataProvider provide_leaf_scenarios
	 *
	 * @param callable                                  $arrange        Scenario-specific stubs.
	 * @param array<array{title: string, url: string}>  $expected_items Expected breadcrumb items.
	 */
	public function test_leaf_scenarios_build_expected_items( callable $arrange, array $expected_items ): void {
		$arrange();

		$this->assertSame( $expected_items, ( new Breadcrumbs() )->get_items() );
	}

	/**
	 * @return array<string, array{callable, array<array{title: string, url: string}>}>
	 */
	public static function provide_leaf_scenarios(): array {
		return [
			'front page' => [
				static function (): void {
					Functions\when( 'is_front_page' )->justReturn( true );
				},
				[
					[
						'title' => 'Home',
						'url'   => 'https://example.test/',
					],
				],
			],
			'404'        => [
				static function (): void {
					Functions\when( 'is_404' )->justReturn( true );
				},
				[
					[
						'title' => 'Home',
						'url'   => 'https://example.test/',
					],
					[
						'title' => 'Page not found',
						'url'   => '',
					],
				],
			],
			'kereső'     => [
				static function (): void {
					Functions\when( 'is_search' )->justReturn( true );
					Functions\when( 'get_search_query' )->justReturn( 'piros alma' );
				},
				[
					[
						'title' => 'Home',
						'url'   => 'https://example.test/',
					],
					[
						'title' => 'Search: piros alma',
						'url'   => '',
					],
				],
			],
		];
	}

	/**
	 * Taxonomy archive: parent terms are linked, the current term is not.
	 */
	public function test_taxonomy_archive_builds_parent_and_current_terms(): void {
		Functions\when( 'is_category' )->justReturn( true );

		$term   = new \WP_Term(
			[
				'term_id'  => 8,
				'taxonomy' => 'category',
				'name'     => 'Hírek',
			]
		);
		$parent = new \WP_Term(
			[
				'term_id'  => 3,
				'taxonomy' => 'category',
				'name'     => 'Szülő kategória',
			]
		);
		Functions\when( 'get_queried_object' )->justReturn( $term );
		Functions\when( 'get_ancestors' )->justReturn( [ 3 ] );
		Functions\when( 'get_term' )->justReturn( $parent );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.test/kategoria/szulo/' );

		$this->assertSame(
			[
				[
					'title' => 'Home',
					'url'   => 'https://example.test/',
				],
				[
					'title' => 'Szülő kategória',
					'url'   => 'https://example.test/kategoria/szulo/',
				],
				[
					'title' => 'Hírek',
					'url'   => '',
				],
			],
			( new Breadcrumbs() )->get_items()
		);
	}

	/**
	 * Hierarchical page: ancestors listed top-down, current page unlinked.
	 */
	public function test_hierarchical_page_lists_ancestors_top_down(): void {
		Functions\when( 'is_singular' )->justReturn( true );

		$post = new \WP_Post(
			[
				'ID'          => 10,
				'post_type'   => 'page',
				'post_parent' => 5,
				'post_title'  => 'Aloldal',
			]
		);
		Functions\when( 'get_queried_object' )->justReturn( $post );
		// WordPress returns ancestors with the immediate parent first.
		Functions\when( 'get_ancestors' )->justReturn( [ 5, 2 ] );
		Functions\when( 'get_the_title' )->alias(
			static fn( $p ) => match ( true ) {
				$p instanceof \WP_Post => $p->post_title,
				2 === $p               => 'Nagyszülő oldal',
				5 === $p               => 'Szülő oldal',
				default                => '',
			}
		);
		Functions\when( 'get_permalink' )->alias(
			static fn( $p ) => 'https://example.test/oldal/' . ( $p instanceof \WP_Post ? $p->ID : $p ) . '/'
		);

		$this->assertSame(
			[
				[
					'title' => 'Home',
					'url'   => 'https://example.test/',
				],
				[
					'title' => 'Nagyszülő oldal',
					'url'   => 'https://example.test/oldal/2/',
				],
				[
					'title' => 'Szülő oldal',
					'url'   => 'https://example.test/oldal/5/',
				],
				[
					'title' => 'Aloldal',
					'url'   => '',
				],
			],
			( new Breadcrumbs() )->get_items()
		);
	}

	/**
	 * Post with a category: the category trail sits between Home and the post.
	 */
	public function test_post_with_category_includes_category_trail(): void {
		Functions\when( 'is_singular' )->justReturn( true );

		$post     = new \WP_Post(
			[
				'ID'          => 7,
				'post_type'   => 'post',
				'post_parent' => 0,
				'post_title'  => 'Cikk címe',
			]
		);
		$category = new \WP_Term(
			[
				'term_id'  => 4,
				'taxonomy' => 'category',
				'name'     => 'Hírek',
			]
		);
		Functions\when( 'get_queried_object' )->justReturn( $post );
		Functions\when( 'get_the_category' )->justReturn( [ $category ] );
		Functions\when( 'get_ancestors' )->justReturn( [] );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.test/kategoria/hirek/' );
		Functions\when( 'get_the_title' )->alias(
			static fn( $p ) => $p instanceof \WP_Post ? $p->post_title : ''
		);

		$this->assertSame(
			[
				[
					'title' => 'Home',
					'url'   => 'https://example.test/',
				],
				[
					'title' => 'Hírek',
					'url'   => 'https://example.test/kategoria/hirek/',
				],
				[
					'title' => 'Cikk címe',
					'url'   => '',
				],
			],
			( new Breadcrumbs() )->get_items()
		);
	}

	/**
	 * render() honours a boolean show_current=false.
	 */
	public function test_render_with_show_current_false_omits_current_item(): void {
		Functions\when( 'is_404' )->justReturn( true );

		$html = ( new Breadcrumbs() )->render( [ 'show_current' => false ] );

		$this->assertStringNotContainsString( 'Page not found', $html );
		$this->assertStringContainsString( '<span itemprop="name">Home</span>', $html );
	}

	/**
	 * TODO: gyanús viselkedés — a shortcode show_current="false" attribútuma
	 * STRING 'false', ami truthy, így az aktuális elem mégis megjelenik.
	 * Szándékos?
	 */
	public function test_shortcode_show_current_false_string_still_shows_current(): void {
		Functions\when( 'is_404' )->justReturn( true );
		Functions\when( 'shortcode_atts' )->alias(
			static fn( array $defaults, $atts ) => array_merge( $defaults, (array) $atts )
		);

		$html = ( new Breadcrumbs() )->shortcode( [ 'show_current' => 'false' ] );

		$this->assertStringContainsString( 'Page not found', $html );
	}

	/**
	 * build_for_post() (REST path): sequential positions, and — unlike
	 * render() — the current item carries its permalink as URL.
	 */
	public function test_build_for_post_assigns_sequential_positions(): void {
		$post     = new \WP_Post(
			[
				'ID'          => 7,
				'post_type'   => 'post',
				'post_parent' => 0,
				'post_title'  => 'Cikk címe',
			]
		);
		$category = new \WP_Term(
			[
				'term_id'  => 4,
				'taxonomy' => 'category',
				'name'     => 'Hírek',
			]
		);
		Functions\when( 'get_the_category' )->justReturn( [ $category ] );
		Functions\when( 'get_ancestors' )->justReturn( [] );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.test/kategoria/hirek/' );
		Functions\when( 'get_the_title' )->alias(
			static fn( $p ) => $p instanceof \WP_Post ? $p->post_title : ''
		);
		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/cikk/' );

		$this->assertSame(
			[
				[
					'title'    => 'Home',
					'url'      => 'https://example.test/',
					'position' => 1,
				],
				[
					'title'    => 'Hírek',
					'url'      => 'https://example.test/kategoria/hirek/',
					'position' => 2,
				],
				[
					'title'    => 'Cikk címe',
					'url'      => 'https://example.test/cikk/',
					'position' => 3,
				],
			],
			( new Breadcrumbs() )->build_for_post( $post )
		);
	}
}
