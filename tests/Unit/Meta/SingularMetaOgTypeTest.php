<?php
/**
 * og:type of a singular post.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Meta;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Meta\SingularMeta;
use LightweightPlugins\SEO\Meta\TagRenderer;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Meta\SingularMeta
 */
final class SingularMetaOgTypeTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();

		$this->stub_options( [ 'opengraph_enabled' => true ] );

		$post = new \WP_Post(
			[
				'ID'           => 9,
				'post_type'    => 'product',
				'post_excerpt' => 'Mug',
				'post_content' => '',
			]
		);

		Functions\when( 'get_queried_object' )->justReturn( $post );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_title' )->justReturn( 'Mug' );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );
		Functions\when( 'wp_trim_words' )->returnArg( 1 );
		Functions\when( 'has_post_thumbnail' )->justReturn( false );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/product/mug/' );
		Functions\when( 'wp_get_canonical_url' )->justReturn( 'https://example.com/product/mug/' );
		Functions\when( 'esc_attr' )->alias( static fn( $value ) => (string) $value );
		Functions\when( 'esc_url' )->alias( static fn( $value ) => (string) $value );
		Functions\when( 'get_locale' )->justReturn( 'hu_HU' );
		Functions\when( 'get_bloginfo' )->justReturn( 'Site' );
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'post_password_required' )->justReturn( false );
		Functions\when( 'get_the_excerpt' )->justReturn( 'Mug' );
		Functions\when( 'get_the_date' )->justReturn( '2026-09-30T10:00:00+00:00' );
		Functions\when( 'get_the_modified_date' )->justReturn( '2026-09-30T10:00:00+00:00' );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	private function head(): string {
		ob_start();
		( new SingularMeta( new TagRenderer() ) )->output();

		return (string) ob_get_clean();
	}

	public function test_og_type_is_article_by_default(): void {
		$this->assertStringContainsString( '<meta property="og:type" content="article" />', $this->head() );
	}

	public function test_og_type_comes_from_the_og_type_filter(): void {
		Filters\expectApplied( 'lw_seo_og_type' )->once()->andReturn( 'product' );

		$html = $this->head();

		$this->assertSame( 1, substr_count( $html, 'property="og:type"' ) );
		$this->assertStringContainsString( '<meta property="og:type" content="product" />', $html );
		$this->assertStringNotContainsString( 'article:published_time', $html );
	}

	public function test_og_type_ignores_a_non_string_filter_value(): void {
		Filters\expectApplied( 'lw_seo_og_type' )->andReturn( null );

		$this->assertStringContainsString( '<meta property="og:type" content="article" />', $this->head() );
	}
}
