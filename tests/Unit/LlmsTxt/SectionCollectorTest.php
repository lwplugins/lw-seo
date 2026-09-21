<?php
/**
 * SectionCollector unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\LlmsTxt;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\LlmsTxt\SectionCollector;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class SectionCollectorTest extends MonkeyTestCase {

	use OptionsStubTrait;

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
