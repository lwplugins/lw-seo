<?php
/**
 * Head descriptions of a singular post versus content restriction.
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
 * @covers \LightweightPlugins\SEO\Content\PostDescription
 */
final class SingularMetaDescriptionTest extends MonkeyTestCase {

	use OptionsStubTrait;

	/**
	 * The restricted post under test.
	 *
	 * @var \WP_Post
	 */
	private \WP_Post $post;

	protected function setUp(): void {
		parent::setUp();

		$this->stub_options(
			[
				'opengraph_enabled' => true,
				'twitter_enabled'   => true,
				'twitter_card_type' => 'summary',
			]
		);

		$this->post = new \WP_Post(
			[
				'ID'           => 7,
				'post_type'    => 'post',
				'post_excerpt' => '',
				'post_content' => '<p>Members only secret text</p>',
			]
		);

		Functions\when( 'get_queried_object' )->justReturn( $this->post );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_title' )->justReturn( 'Post' );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );
		Functions\when( 'wp_trim_words' )->returnArg( 1 );
		Functions\when( 'has_post_thumbnail' )->justReturn( false );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/post/' );
		Functions\when( 'wp_get_canonical_url' )->justReturn( 'https://example.com/post/' );
		Functions\when( 'esc_attr' )->alias( static fn( $value ) => htmlspecialchars( (string) $value, ENT_QUOTES ) );
		Functions\when( 'esc_url' )->alias( static fn( $value ) => (string) $value );
		Functions\when( 'get_locale' )->justReturn( 'hu_HU' );
		Functions\when( 'get_bloginfo' )->justReturn( 'Site' );
		Functions\when( 'is_singular' )->justReturn( false );
		Functions\when( 'post_password_required' )->justReturn( false );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Print the head meta of the queried post.
	 *
	 * @return string
	 */
	private function render_head(): string {
		ob_start();
		( new SingularMeta( new TagRenderer() ) )->output();

		return (string) ob_get_clean();
	}

	public function test_description_uses_the_masked_excerpt_instead_of_the_content(): void {
		Functions\when( 'get_the_excerpt' )->justReturn( 'Join to read this post.' );

		$html = $this->render_head();

		$this->assertStringNotContainsString( 'secret', $html );
		$this->assertStringContainsString( '<meta name="description" content="Join to read this post." />', $html );
		$this->assertStringContainsString( '<meta property="og:description" content="Join to read this post." />', $html );
		$this->assertStringContainsString( '<meta name="twitter:description" content="Join to read this post." />', $html );
	}

	public function test_description_filter_masks_meta_og_and_twitter(): void {
		Functions\when( 'get_the_excerpt' )->justReturn( 'Members only secret text' );
		Filters\expectApplied( 'lw_seo_meta_description' )->once()->with( 'Members only secret text', $this->post, 'meta' )->andReturn( 'Meta teaser' );
		Filters\expectApplied( 'lw_seo_meta_description' )->once()->with( 'Members only secret text', $this->post, 'og' )->andReturn( 'OG teaser' );
		Filters\expectApplied( 'lw_seo_meta_description' )->once()->with( 'Members only secret text', $this->post, 'twitter' )->andReturn( 'Twitter teaser' );

		$html = $this->render_head();

		$this->assertStringNotContainsString( 'secret', $html );
		$this->assertStringContainsString( '<meta name="description" content="Meta teaser" />', $html );
		$this->assertStringContainsString( '<meta property="og:description" content="OG teaser" />', $html );
		$this->assertStringContainsString( '<meta name="twitter:description" content="Twitter teaser" />', $html );
	}

	public function test_password_protected_post_gets_no_generated_description(): void {
		Functions\when( 'post_password_required' )->justReturn( true );
		Functions\expect( 'get_the_excerpt' )->never();

		$html = $this->render_head();

		$this->assertStringNotContainsString( 'secret', $html );
		$this->assertStringNotContainsString( 'description', $html );
	}
}
