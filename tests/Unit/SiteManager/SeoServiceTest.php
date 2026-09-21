<?php
/**
 * SeoService unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\SiteManager;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\SiteManager\SeoService;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class SeoServiceTest extends MonkeyTestCase {

	/**
	 * Without unfiltered_html the Markdown override is skipped (the stored
	 * value is left untouched) while the other fields are still written.
	 */
	public function test_set_meta_skips_markdown_override_without_unfiltered_html(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_post' )->justReturn( new \WP_Post( [ 'ID' => 7 ] ) );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\expect( 'update_post_meta' )->once()->with( 7, '_lw_seo_title', 'T' )->andReturn( true );
		Functions\expect( 'delete_post_meta' )->never();

		$result = SeoService::set_meta(
			[
				'post_id' => 7,
				'meta'    => [
					'title'            => 'T',
					'markdown_content' => '[x](javascript:alert(1))',
				],
			]
		);

		$this->assertSame( [ 'title' ], $result['updated'] );
	}

	/**
	 * With unfiltered_html the override is written as before.
	 */
	public function test_set_meta_writes_markdown_override_with_unfiltered_html(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_post' )->justReturn( new \WP_Post( [ 'ID' => 7 ] ) );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\expect( 'update_post_meta' )->once()->with( 7, '_lw_seo_markdown_content', '# Custom' )->andReturn( true );

		$result = SeoService::set_meta(
			[
				'post_id' => 7,
				'meta'    => [ 'markdown_content' => '# Custom' ],
			]
		);

		$this->assertSame( [ 'markdown_content' ], $result['updated'] );
	}

	/**
	 * The same rule applies to terms.
	 */
	public function test_set_term_meta_skips_markdown_override_without_unfiltered_html(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_term' )->justReturn( new \WP_Term( [ 'term_id' => 3 ] ) );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'update_term_meta' )->never();
		Functions\expect( 'delete_term_meta' )->never();

		$result = SeoService::set_meta(
			[
				'term_id' => 3,
				'meta'    => [ 'markdown_content' => '[x](javascript:alert(1))' ],
			]
		);

		$this->assertSame( [], $result['updated'] );
	}
}
