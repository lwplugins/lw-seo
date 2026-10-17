<?php
/**
 * HTML sitemap Shortcode unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\HtmlSitemap;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\HtmlSitemap\Shortcode;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\HtmlSitemap\Shortcode
 */
final class ShortcodeTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'get_post_types' )->justReturn(
			[
				'post'  => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Posts' ] ] ),
				'page'  => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Pages' ] ] ),
				'event' => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Events' ] ] ),
			]
		);
		Functions\when( 'get_taxonomies' )->justReturn( [ 'category' => new \WP_Taxonomy( [ 'labels' => (object) [ 'name' => 'Categories' ] ] ) ] );
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\when( 'is_taxonomy_viewable' )->justReturn( true );
		Functions\when( 'sanitize_key' )->alias( static fn( $key ) => strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $key ) ) );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_locale' )->justReturn( 'en_US' );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_attributes_override_options(): void {
		$this->stub_options(
			[
				'html_sitemap_post_types'  => [ 'post' => true ],
				'html_sitemap_taxonomies'  => [ 'category' => true ],
				'html_sitemap_max_items'   => 100,
				'html_sitemap_order'       => 'date',
			]
		);

		$this->assertSame(
			[
				'post_types' => [ 'post' ],
				'taxonomies' => [ 'category' ],
				'max'        => 100,
				'order'      => 'date',
			],
			Shortcode::resolve( [] )
		);

		$this->assertSame(
			[
				'post_types' => [ 'page', 'event' ],
				'taxonomies' => [],
				'max'        => 20,
				'order'      => 'title',
			],
			Shortcode::resolve(
				[
					'post_types' => 'page, event',
					'taxonomies' => 'none',
					'max'        => '20',
					'order'      => 'title',
				]
			)
		);
	}

	public function test_unknown_type_ignored(): void {
		$this->stub_options();

		$resolved = Shortcode::resolve( [ 'post_types' => 'page,ghost', 'order' => 'bogus', 'max' => '-5' ] );

		$this->assertSame( [ 'page' ], $resolved['post_types'] );
		$this->assertSame( 'title', $resolved['order'] );
		$this->assertSame( 500, $resolved['max'] );
	}

	public function test_max_attribute_can_only_lower_the_setting(): void {
		$this->stub_options( [ 'html_sitemap_max_items' => 100 ] );

		$this->assertSame( 100, Shortcode::resolve( [ 'max' => '4000' ] )['max'] );
		$this->assertSame( 40, Shortcode::resolve( [ 'max' => '40' ] )['max'] );
		$this->assertSame( 100, Shortcode::resolve( [] )['max'] );
	}
}
