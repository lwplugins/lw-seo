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

		$this->assertSame( [ [ 'title' ], [ 'markdown_content' ] ], [ $result['updated'], $result['skipped'] ] );
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

		$this->assertSame( [ [ 'markdown_content' ], [] ], [ $result['updated'], $result['skipped'] ] );
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

		$this->assertSame( [ [], [ 'markdown_content' ] ], [ $result['updated'], $result['skipped'] ] );
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

		$this->assertSame( [ [ 'markdown_content' ], [] ], [ $result['updated'], $result['skipped'] ] );
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

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function read_ability_provider(): array {
		return [
			'get_meta'            => [ 'get_meta' ],
			'get_content_signals' => [ 'get_content_signals' ],
			'get_markdown'        => [ 'get_markdown' ],
		];
	}

	/**
	 * A draft the user can't edit is not readable through any read ability,
	 * and nothing about it is read or rendered.
	 *
	 * @dataProvider read_ability_provider
	 *
	 * @param string $method SeoService read method.
	 */
	public function test_read_rejects_draft_the_user_cannot_edit( string $method ): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_post' )->justReturn( self::post( 'draft' ) );
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\expect( 'current_user_can' )->once()->with( 'edit_post', 7 )->andReturn( false );
		Functions\expect( 'get_post_meta' )->never();

		$this->assert_forbidden( SeoService::$method( [ 'post_id' => 7 ] ) );
	}

	/**
	 * A term in a non-public taxonomy the user can't edit is not readable
	 * through any read ability.
	 *
	 * @dataProvider read_ability_provider
	 *
	 * @param string $method SeoService read method.
	 */
	public function test_read_rejects_private_taxonomy_term_the_user_cannot_edit( string $method ): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_term' )->justReturn( self::term() );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_taxonomy' )->justReturn( new \WP_Taxonomy( [ 'public' => false ] ) );
		Functions\expect( 'current_user_can' )->once()->with( 'edit_term', 3 )->andReturn( false );
		Functions\expect( 'get_term_meta' )->never();

		$this->assert_forbidden( SeoService::$method( [ 'term_id' => 3 ] ) );
	}

	/**
	 * A password-protected post is not public, so it needs edit_post.
	 */
	public function test_read_rejects_password_protected_post_the_user_cannot_edit(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_post' )->justReturn( self::post( 'publish', 'secret' ) );
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'get_post_meta' )->never();

		$this->assert_forbidden( SeoService::get_markdown( [ 'post_id' => 7 ] ) );
	}

	/**
	 * A published post of a non-viewable post type (e.g. a WooCommerce
	 * coupon, whose title is the code) is not public either.
	 */
	public function test_read_rejects_published_post_of_non_viewable_type_the_user_cannot_edit(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_post' )->justReturn( self::post( 'publish', '', 'shop_coupon' ) );
		Functions\when( 'is_post_type_viewable' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'get_post_meta' )->never();

		$this->assert_forbidden( SeoService::get_markdown( [ 'post_id' => 7 ] ) );
	}

	/**
	 * A published, public post is readable without edit_post: it is
	 * publicly readable anyway.
	 */
	public function test_read_allows_published_public_post_without_edit_post(): void {
		Functions\when( 'get_post' )->justReturn( self::post( 'publish' ) );
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\expect( 'current_user_can' )->never();
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$result = SeoService::get_meta( [ 'post_id' => 7 ] );

		$this->assertSame( [ true, 'post', 7 ], [ $result['success'], $result['type'], $result['id'] ] );
	}

	/**
	 * A draft is readable by a user who can edit it.
	 */
	public function test_read_allows_draft_with_edit_post(): void {
		Functions\when( 'get_post' )->justReturn( self::post( 'draft' ) );
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\expect( 'current_user_can' )->once()->with( 'edit_post', 7 )->andReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$result = SeoService::get_meta( [ 'post_id' => 7 ] );

		$this->assertSame( [ true, 'post', 7 ], [ $result['success'], $result['type'], $result['id'] ] );
	}

	/**
	 * A term in a non-public taxonomy is readable with edit_term.
	 */
	public function test_read_allows_private_taxonomy_term_with_edit_term(): void {
		Functions\when( 'get_term' )->justReturn( self::term() );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_taxonomy' )->justReturn( new \WP_Taxonomy( [ 'public' => false ] ) );
		Functions\expect( 'current_user_can' )->once()->with( 'edit_term', 3 )->andReturn( true );
		Functions\when( 'get_term_meta' )->justReturn( '' );

		$result = SeoService::get_meta( [ 'term_id' => 3 ] );

		$this->assertSame( [ true, 'term', 3 ], [ $result['success'], $result['type'], $result['id'] ] );
	}

	/**
	 * A term in a public taxonomy is readable without edit_term.
	 */
	public function test_read_allows_public_taxonomy_term_without_edit_term(): void {
		Functions\when( 'get_term' )->justReturn( self::term() );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_taxonomy' )->justReturn( new \WP_Taxonomy( [ 'public' => true ] ) );
		Functions\expect( 'current_user_can' )->never();
		Functions\when( 'get_term_meta' )->justReturn( '' );

		$result = SeoService::get_meta( [ 'term_id' => 3 ] );

		$this->assertSame( [ true, 'term', 3 ], [ $result['success'], $result['type'], $result['id'] ] );
	}

	/**
	 * Signal values are whitelisted to yes/no/empty; invalid values are
	 * sanitized to empty string (which deletes the meta row).
	 */
	public function test_set_meta_sanitizes_signal_values_to_whitelist(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_post' )->justReturn( new \WP_Post( [ 'ID' => 7 ] ) );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\expect( 'update_post_meta' )->once()->with( 7, '_lw_seo_ai_train', 'yes' )->andReturn( true );
		Functions\expect( 'delete_post_meta' )->once()->with( 7, '_lw_seo_ai_input' )->andReturn( true );
		Functions\expect( 'delete_post_meta' )->once()->with( 7, '_lw_seo_search' )->andReturn( true );

		$result = SeoService::set_meta(
			[
				'post_id' => 7,
				'meta'    => [
					'ai_train' => 'yes',
					'ai_input' => 'invalid',
					'search'   => 'default',
				],
			]
		);

		$this->assertSame( [ 'ai_train', 'ai_input', 'search' ], $result['updated'] );
	}

	/**
	 * Post fixture.
	 *
	 * @param string $status    Post status.
	 * @param string $password  Post password.
	 * @param string $post_type Post type.
	 */
	private static function post( string $status, string $password = '', string $post_type = 'post' ): \WP_Post {
		return new \WP_Post(
			[
				'ID'            => 7,
				'post_status'   => $status,
				'post_password' => $password,
				'post_type'     => $post_type,
			]
		);
	}

	/**
	 * Term fixture.
	 */
	private static function term(): \WP_Term {
		return new \WP_Term(
			[
				'term_id'  => 3,
				'taxonomy' => 'secret_tax',
			]
		);
	}

	/**
	 * @param mixed $result Read ability result.
	 */
	private function assert_forbidden( mixed $result ): void {
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( [ 'forbidden', [ 'status' => 403 ] ], [ $result->get_error_code(), $result->get_error_data() ] );
	}
}
