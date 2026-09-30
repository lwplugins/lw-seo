<?php
/**
 * FrontPageMeta unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Meta;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Meta\FrontPageMeta;
use LightweightPlugins\SEO\Meta\TagRenderer;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Meta\FrontPageMeta
 */
final class FrontPageMetaTest extends MonkeyTestCase {

	use OptionsStubTrait;

	/**
	 * LW SEO post meta of the front page, without the prefix.
	 *
	 * @var array<string, string>
	 */
	private array $meta = [];

	protected function setUp(): void {
		parent::setUp();

		$this->meta = [];
		$this->stub_options(
			[
				'opengraph_enabled' => true,
				'twitter_enabled'   => false,
			],
			[ 'show_on_front' => 'page' ]
		);
		Functions\when( 'esc_url' )->alias( static fn( $url ) => (string) $url );
		Functions\when( 'esc_attr' )->alias( static fn( $value ) => (string) $value );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( $text ) => strip_tags( (string) $text ) );
		Functions\when( 'get_locale' )->justReturn( 'en_US' );
		Functions\when( 'get_bloginfo' )->justReturn( 'Site' );
		Functions\when( 'get_query_var' )->justReturn( 0 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/en/' );
		Functions\when( 'get_post_meta' )->alias( fn( $id, $key ) => $this->meta[ substr( $key, strlen( Options::META_PREFIX ) ) ] ?? '' );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	private function page(): \WP_Post {
		return new \WP_Post(
			[
				'ID'        => 494,
				'post_type' => 'page',
			]
		);
	}

	private function render(): string {
		Functions\when( 'get_queried_object' )->justReturn( $this->page() );

		ob_start();
		( new FrontPageMeta( new TagRenderer() ) )->output( $this->page(), 'Home title', 'Home description' );

		return (string) ob_get_clean();
	}

	public function test_uses_the_front_page_own_description(): void {
		$this->meta['description'] = 'EN front page description';

		$html = $this->render();

		$this->assertStringContainsString( '<meta name="description" content="EN front page description" />', $html );
		$this->assertStringContainsString( '<meta property="og:description" content="EN front page description" />', $html );
	}

	public function test_falls_back_to_the_home_description(): void {
		$html = $this->render();

		$this->assertStringContainsString( '<meta name="description" content="Home description" />', $html );
	}

	public function test_uses_the_front_page_own_canonical(): void {
		$this->meta['canonical'] = 'https://example.com/en/custom/';

		$this->assertStringContainsString( '<link rel="canonical" href="https://example.com/en/custom/" />', $this->render() );
	}

	public function test_canonical_defaults_to_the_front_page_permalink(): void {
		$this->assertStringContainsString( '<link rel="canonical" href="https://example.com/en/" />', $this->render() );
	}

	public function test_og_title_uses_the_page_title_then_the_home_title(): void {
		$this->assertStringContainsString( '<meta property="og:title" content="Home title" />', $this->render() );

		$this->meta['title'] = 'Own title';
		$this->assertStringContainsString( '<meta property="og:title" content="Own title" />', $this->render() );
	}

	public function test_og_image_is_the_page_own_image_else_the_default(): void {
		$this->stub_options(
			[
				'opengraph_enabled' => true,
				'twitter_enabled'   => false,
				'default_og_image'  => 'https://example.com/default.jpg',
			],
			[ 'show_on_front' => 'page' ]
		);
		$this->assertStringContainsString( '<meta property="og:image" content="https://example.com/default.jpg" />', $this->render() );

		$this->meta['og_image'] = 'https://example.com/own.jpg';
		$this->assertStringContainsString( '<meta property="og:image" content="https://example.com/own.jpg" />', $this->render() );
	}

	public function test_prints_the_front_page_robots_flags(): void {
		$this->meta['noindex'] = '1';

		$this->assertStringContainsString( '<meta name="robots" content="noindex" />', $this->render() );
	}

	public function test_page_is_the_queried_static_front_page(): void {
		Functions\when( 'get_queried_object' )->justReturn( $this->page() );

		$this->assertSame( 494, FrontPageMeta::page()?->ID );
	}

	public function test_page_is_null_when_the_front_page_shows_latest_posts(): void {
		$this->stub_options( [], [ 'show_on_front' => 'posts' ] );
		Functions\when( 'get_queried_object' )->justReturn( null );

		$this->assertNull( FrontPageMeta::page() );
	}
}
