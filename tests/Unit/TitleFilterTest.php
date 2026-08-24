<?php
/**
 * TitleFilter unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Meta\TitleFilter;
use LightweightPlugins\SEO\Options;

/**
 * @covers \LightweightPlugins\SEO\Meta\TitleFilter
 */
final class TitleFilterTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();

		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'get_option' )->alias(
			static fn( string $key, $default_value = false ) => match ( $key ) {
				'lw_seo_options' => [],
				'page_for_posts' => 12,
				default          => $default_value,
			}
		);
		Functions\when( 'get_bloginfo' )->alias(
			static fn( string $show ) => 'Site' === $show ? 'Site' : ( 'name' === $show ? 'Site' : 'Tagline' )
		);
		Functions\when( 'is_front_page' )->justReturn( false );
		Functions\when( 'is_home' )->justReturn( false );
		Functions\when( 'is_singular' )->justReturn( false );
		Functions\when( 'is_category' )->justReturn( false );
		Functions\when( 'is_tag' )->justReturn( false );
		Functions\when( 'is_tax' )->justReturn( false );
		Functions\when( 'is_author' )->justReturn( false );
		Functions\when( 'is_search' )->justReturn( false );
		Functions\when( 'is_404' )->justReturn( false );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_front_page_uses_home_template(): void {
		Functions\when( 'is_front_page' )->justReturn( true );
		Functions\when( 'is_home' )->justReturn( true );

		$parts = ( new TitleFilter() )->filter_title(
			[
				'title'   => 'Home',
				'site'    => 'Site',
				'tagline' => 'Tagline',
			]
		);

		$this->assertSame( [ 'title' => 'Site - Tagline' ], $parts );
	}

	public function test_posts_page_uses_its_own_seo_title(): void {
		Functions\when( 'is_home' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( 'Blog SEO title' );

		$parts = ( new TitleFilter() )->filter_title(
			[
				'title' => 'Blog',
				'site'  => 'Site',
			]
		);

		$this->assertSame(
			[
				'title' => 'Blog SEO title',
				'site'  => 'Site',
			],
			$parts
		);
	}

	public function test_posts_page_falls_back_to_page_template(): void {
		Functions\when( 'is_home' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_post' )->justReturn( new \WP_Post( [ 'ID' => 12 ] ) );
		Functions\when( 'get_the_title' )->justReturn( 'Blog' );

		$parts = ( new TitleFilter() )->filter_title(
			[
				'title' => 'Blog',
				'site'  => 'Site',
			]
		);

		$this->assertSame( [ 'title' => 'Blog - Site' ], $parts );
	}

	public function test_singular_custom_title_keeps_site_part(): void {
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_queried_object' )->justReturn( new \WP_Post( [ 'ID' => 7, 'post_type' => 'post' ] ) );
		Functions\when( 'get_post_meta' )->justReturn( 'Custom' );

		$parts = ( new TitleFilter() )->filter_title(
			[
				'title' => 'Hello',
				'site'  => 'Site',
			]
		);

		$this->assertSame(
			[
				'title' => 'Custom',
				'site'  => 'Site',
			],
			$parts
		);
	}

	public function test_separator_falls_back_to_wordpress_default(): void {
		$this->assertSame( '-', ( new TitleFilter() )->filter_separator( '-' ) );
	}
}
