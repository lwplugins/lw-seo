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
		Functions\when( 'current_user_can' )->alias( static fn( string $cap ): bool => 'unfiltered_html' !== $cap );
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
		Functions\when( 'current_user_can' )->alias( static fn( string $cap ): bool => 'unfiltered_html' !== $cap );
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

	/**
	 * With unfiltered_html the term override is written as before.
	 */
	public function test_set_term_meta_writes_markdown_override_with_unfiltered_html(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_term' )->justReturn( new \WP_Term( [ 'term_id' => 3 ] ) );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\expect( 'update_term_meta' )->once()->with( 3, '_lw_seo_markdown_content', '# Custom' )->andReturn( true );

		$result = SeoService::set_meta(
			[
				'term_id' => 3,
				'meta'    => [ 'markdown_content' => '# Custom' ],
			]
		);

		$this->assertSame( [ 'markdown_content' ], $result['updated'] );
	}

	/**
	 * The generic edit_posts gate isn't enough: the user must be able to
	 * edit the target post.
	 */
	public function test_set_meta_checks_edit_post_for_the_target_post(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_post' )->justReturn( new \WP_Post( [ 'ID' => 7 ] ) );
		Functions\expect( 'current_user_can' )->once()->with( 'edit_post', 7 )->andReturn( true );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\expect( 'update_post_meta' )->once()->with( 7, '_lw_seo_title', 'T' )->andReturn( true );

		$result = SeoService::set_meta(
			[
				'post_id' => 7,
				'meta'    => [ 'title' => 'T' ],
			]
		);

		$this->assertSame( [ 'title' ], $result['updated'] );
	}

	/**
	 * A post the user can't edit is rejected before anything is written.
	 */
	public function test_set_meta_rejects_post_the_user_cannot_edit(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_post' )->justReturn( new \WP_Post( [ 'ID' => 7 ] ) );
		Functions\when( 'current_user_can' )->alias( static fn( string $cap ): bool => 'edit_post' !== $cap );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\expect( 'update_post_meta' )->never();
		Functions\expect( 'delete_post_meta' )->never();

		$result = SeoService::set_meta(
			[
				'post_id' => 7,
				'meta'    => [ 'noindex' => '1' ],
			]
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( [ 'forbidden', [ 'status' => 403 ] ], [ $result->get_error_code(), $result->get_error_data() ] );
	}

	/**
	 * A term the user can't edit is rejected before anything is written.
	 */
	public function test_set_meta_rejects_term_the_user_cannot_edit(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_term' )->justReturn( new \WP_Term( [ 'term_id' => 3 ] ) );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'current_user_can' )->alias( static fn( string $cap ): bool => 'edit_term' !== $cap );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\expect( 'update_term_meta' )->never();
		Functions\expect( 'delete_term_meta' )->never();

		$result = SeoService::set_meta(
			[
				'term_id' => 3,
				'meta'    => [ 'title' => 'T' ],
			]
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( [ 'forbidden', [ 'status' => 403 ] ], [ $result->get_error_code(), $result->get_error_data() ] );
	}
}
