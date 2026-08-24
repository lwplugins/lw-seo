<?php
/**
 * Characterization tests for the head meta rendering moved out of Plugin.
 *
 * These pin down the CURRENT output byte-for-byte (see .claude/rules/tests.md),
 * so the extraction into Meta\TagRenderer / Meta\SingularMeta stays behaviour
 * preserving.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Meta\SingularMeta;
use LightweightPlugins\SEO\Meta\TagRenderer;
use LightweightPlugins\SEO\Options;

/**
 * @covers \LightweightPlugins\SEO\Meta\TagRenderer
 * @covers \LightweightPlugins\SEO\Meta\SingularMeta
 */
final class TagRendererTest extends MonkeyTestCase {

	/**
	 * Plugin options seen by Options::get() in a given test.
	 *
	 * @var array<string, mixed>
	 */
	private array $options = [];

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();

		$this->options = [];

		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'get_option' )->alias(
			fn( string $key, $default_value = false ) => 'lw_seo_options' === $key ? $this->options : $default_value
		);
		Functions\when( 'esc_attr' )->alias( static fn( $value ) => htmlspecialchars( (string) $value, ENT_QUOTES ) );
		Functions\when( 'esc_url' )->alias( static fn( $value ) => (string) $value );
		Functions\when( 'get_locale' )->justReturn( 'hu_HU' );
		Functions\when( 'get_bloginfo' )->justReturn( 'Site' );
		Functions\when( 'is_singular' )->justReturn( false );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Capture the output of a callable.
	 *
	 * @param callable $callback Callback to run.
	 * @return string
	 */
	private function capture( callable $callback ): string {
		ob_start();
		$callback();

		return (string) ob_get_clean();
	}

	public function test_renders_only_description_and_canonical_when_social_is_off(): void {
		$this->options = [
			'opengraph_enabled' => false,
			'twitter_enabled'   => false,
		];

		$html = $this->capture(
			static function (): void {
				( new TagRenderer() )->render( 'T', 'Desc', 'https://example.com/shop/', 'OGT', 'OGD', 'https://example.com/i.jpg', 'website' );
			}
		);

		$this->assertSame(
			"\n<!-- LW SEO -->\n"
			. '<meta name="description" content="Desc" />' . "\n"
			. '<link rel="canonical" href="https://example.com/shop/" />' . "\n"
			. "<!-- /LW SEO -->\n\n",
			$html
		);
	}

	public function test_renders_open_graph_and_twitter_blocks(): void {
		$this->options = [
			'opengraph_enabled' => true,
			'twitter_enabled'   => true,
			'twitter_card_type' => 'summary_large_image',
		];

		$html = $this->capture(
			static function (): void {
				( new TagRenderer() )->render( 'T', 'Desc', 'https://example.com/shop/', 'OGT', 'OGD', 'https://example.com/i.jpg', 'website' );
			}
		);

		$this->assertStringContainsString( '<meta property="og:locale" content="hu_HU" />', $html );
		$this->assertStringContainsString( '<meta property="og:type" content="website" />', $html );
		$this->assertStringContainsString( '<meta property="og:title" content="OGT" />', $html );
		$this->assertStringContainsString( '<meta property="og:url" content="https://example.com/shop/" />', $html );
		$this->assertStringContainsString( '<meta property="og:description" content="OGD" />', $html );
		$this->assertStringContainsString( '<meta property="og:image" content="https://example.com/i.jpg" />', $html );
		$this->assertStringContainsString( '<meta name="twitter:card" content="summary_large_image" />', $html );
		$this->assertStringContainsString( '<meta name="twitter:image" content="https://example.com/i.jpg" />', $html );
		$this->assertStringNotContainsString( 'article:published_time', $html );
	}

	public function test_omits_empty_description_and_image(): void {
		$this->options = [
			'opengraph_enabled' => true,
			'twitter_enabled'   => true,
			'twitter_card_type' => 'summary',
		];

		$html = $this->capture(
			static function (): void {
				( new TagRenderer() )->render( 'T', '', 'https://example.com/', 'OGT', '', '', 'website' );
			}
		);

		$this->assertStringNotContainsString( 'name="description"', $html );
		$this->assertStringNotContainsString( 'og:description', $html );
		$this->assertStringNotContainsString( 'og:image', $html );
		$this->assertStringNotContainsString( 'twitter:image', $html );
	}

	public function test_robots_tag_is_skipped_without_directives(): void {
		$html = $this->capture(
			static function (): void {
				( new TagRenderer() )->render_robots( [] );
			}
		);

		$this->assertSame( '', $html );
	}

	public function test_robots_tag_joins_directives(): void {
		$html = $this->capture(
			static function (): void {
				( new TagRenderer() )->render_robots( [ 'noindex', 'nofollow' ] );
			}
		);

		$this->assertSame( '<meta name="robots" content="noindex, nofollow" />' . "\n", $html );
	}

	public function test_singular_output_uses_post_meta_and_prints_robots(): void {
		$this->options = [
			'opengraph_enabled' => false,
			'twitter_enabled'   => false,
			'noindex_post'      => false,
		];

		$post = new \WP_Post(
			[
				'ID'           => 7,
				'post_type'    => 'post',
				'post_excerpt' => 'Excerpt',
				'post_content' => 'Content',
			]
		);

		Functions\when( 'get_queried_object' )->justReturn( $post );
		Functions\when( 'get_post_meta' )->alias(
			static fn( int $id, string $key ) => match ( $key ) {
				'_lw_seo_noindex'   => '1',
				'_lw_seo_title'     => 'Meta title',
				'_lw_seo_canonical' => 'https://example.com/custom/',
				default             => '',
			}
		);
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( $value ) => (string) $value );
		Functions\when( 'has_post_thumbnail' )->justReturn( false );

		$html = $this->capture(
			static function () use ( $post ): void {
				unset( $post );
				( new SingularMeta( new TagRenderer() ) )->output();
			}
		);

		$this->assertStringContainsString( '<meta name="robots" content="noindex" />', $html );
		$this->assertStringContainsString( '<meta name="description" content="Excerpt" />', $html );
		$this->assertStringContainsString( '<link rel="canonical" href="https://example.com/custom/" />', $html );
	}

	public function test_singular_output_returns_early_without_a_post(): void {
		Functions\when( 'get_queried_object' )->justReturn( null );

		$html = $this->capture(
			static function (): void {
				( new SingularMeta( new TagRenderer() ) )->output();
			}
		);

		$this->assertSame( '', $html );
	}
}
