<?php
/**
 * RestApi access unit tests (issue #18).
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\RestApi;

final class RestApiTest extends MonkeyTestCase {

	use OptionsStubTrait;

	/**
	 * The issue's post probes: [callback, post type].
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function private_post_provider(): array {
		return [
			'meta of a course'        => [ 'get_post_meta', 'course' ],
			'schema of a course'      => [ 'get_post_schema', 'course' ],
			'breadcrumbs of a course' => [ 'get_post_breadcrumbs', 'course' ],
			'meta of a lesson'        => [ 'get_post_meta', 'lesson' ],
		];
	}

	/**
	 * A published post of a non-viewable post type answers 404 to an
	 * anonymous caller, exactly like a missing post.
	 *
	 * @dataProvider private_post_provider
	 *
	 * @param string $callback  RestApi callback.
	 * @param string $post_type Post type.
	 */
	public function test_post_routes_hide_non_viewable_post_types_from_anonymous( string $callback, string $post_type ): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_post' )->justReturn( self::post( $post_type ) );
		Functions\when( 'post_password_required' )->justReturn( false );
		Functions\when( 'is_post_type_viewable' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );

		$result = ( new RestApi() )->$callback( new \WP_REST_Request( [ 'id' => 422 ] ) );

		$this->assert_not_found( 'post_not_found', $result );
	}

	/**
	 * A term of a private taxonomy answers 404 to an anonymous caller.
	 */
	public function test_term_route_hides_private_taxonomy_terms_from_anonymous(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'get_term' )->justReturn( self::term() );
		Functions\when( 'taxonomy_exists' )->justReturn( true );
		Functions\when( 'is_taxonomy_viewable' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );

		$result = ( new RestApi() )->get_term_meta( new \WP_REST_Request( [ 'id' => 9, 'taxonomy' => 'course_category' ] ) );

		$this->assert_not_found( 'term_not_found', $result );
	}

	/**
	 * Term route answers 200: [taxonomy viewable, can edit term].
	 *
	 * @return array<string, array{0: bool, 1: bool}>
	 */
	public static function readable_term_provider(): array {
		return [
			'public category, anonymous'     => [ true, false ],
			'private course_category, admin' => [ false, true ],
		];
	}

	/**
	 * @dataProvider readable_term_provider
	 *
	 * @param bool $viewable Whether the taxonomy is viewable.
	 * @param bool $can_edit Whether the user can edit the term.
	 */
	public function test_term_route_serves_readable_terms( bool $viewable, bool $can_edit ): void {
		$this->stub_options();
		Functions\when( 'get_term' )->justReturn( self::term() );
		Functions\when( 'taxonomy_exists' )->justReturn( true );
		Functions\when( 'is_taxonomy_viewable' )->justReturn( $viewable );
		Functions\when( 'current_user_can' )->justReturn( $can_edit );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/t/' );
		Functions\when( 'get_bloginfo' )->justReturn( 'Site' );
		Functions\when( 'get_locale' )->justReturn( 'en_US' );
		Functions\when( 'wp_strip_all_tags' )->returnArg();

		$result = ( new RestApi() )->get_term_meta( new \WP_REST_Request( [ 'id' => 9, 'taxonomy' => 'course_category' ] ) );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( [ 200, 'Probe description' ], [ $result->get_status(), $result->get_data()['description'] ] );
	}

	/**
	 * Breadcrumbs route answers 200: [post type, viewable, caps].
	 *
	 * @return array<string, array{0: string, 1: bool, 2: bool}>
	 */
	public static function readable_post_provider(): array {
		return [
			'public post, anonymous' => [ 'post', true, false ],
			'public page, anonymous' => [ 'page', true, false ],
			'course, admin'          => [ 'course', false, true ],
			'lesson, admin'          => [ 'lesson', false, true ],
		];
	}

	/**
	 * @dataProvider readable_post_provider
	 *
	 * @param string $post_type Post type.
	 * @param bool   $viewable  Whether the post type is viewable.
	 * @param bool   $is_admin  Whether the user can edit the post.
	 */
	public function test_post_route_serves_readable_posts( string $post_type, bool $viewable, bool $is_admin ): void {
		Functions\stubTranslationFunctions();
		$this->stub_options();
		Functions\when( 'get_post' )->justReturn( self::post( $post_type ) );
		Functions\when( 'post_password_required' )->justReturn( false );
		Functions\when( 'is_post_type_viewable' )->justReturn( $viewable );
		Functions\when( 'current_user_can' )->justReturn( $is_admin );
		Functions\when( 'add_shortcode' )->justReturn( true );
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );
		Functions\when( 'get_post_type_object' )->justReturn( null );
		Functions\when( 'get_the_category' )->justReturn( [] );
		Functions\when( 'get_the_title' )->justReturn( 'Private course REST probe' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/probe/' );

		$result = ( new RestApi() )->get_post_breadcrumbs( new \WP_REST_Request( [ 'id' => 422 ] ) );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( [ 200, 'Private course REST probe' ], [ $result->get_status(), $result->get_data()[1]['title'] ?? null ] );
	}

	/**
	 * Get the REST SEO data of a public post with the given LW SEO meta.
	 *
	 * @param array<string, string> $meta Field => value.
	 * @return array<string, mixed>
	 */
	private function post_seo_data( array $meta ): array {
		$this->stub_options( [ 'separator' => '|' ] );
		Functions\when( 'get_post' )->justReturn( self::post( 'page' ) );
		Functions\when( 'post_password_required' )->justReturn( false );
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\when( 'get_post_meta' )->alias( static fn( int $id, string $key ): string => $meta[ substr( $key, strlen( '_lw_seo_' ) ) ] ?? '' );
		Functions\when( 'get_the_title' )->justReturn( 'About' );
		Functions\when( 'get_bloginfo' )->justReturn( 'Site' );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/about/' );
		Functions\when( 'get_locale' )->justReturn( 'hu_HU' );
		Functions\when( 'has_post_thumbnail' )->justReturn( false );

		$result = ( new RestApi() )->get_post_meta( new \WP_REST_Request( [ 'id' => 422 ] ) );
		$this->assertInstanceOf( \WP_REST_Response::class, $result );

		return $result->get_data();
	}

	public function test_post_route_fills_in_variables_of_the_post_seo_texts(): void {
		$data = $this->post_seo_data(
			[
				'title'          => 'Custom %%title%% %%sep%% %%sitename%%',
				'description'    => '%%title%% on %%sitename%%',
				'og_title'       => '%%title%% shared',
				'og_description' => '%%sitename%% social',
			]
		);

		$this->assertSame(
			[ 'Custom About | Site', 'About on Site', 'About shared', 'Site social', 'About shared', 'Site social' ],
			[ $data['title'], $data['description'], $data['og']['title'], $data['og']['description'], $data['twitter']['title'], $data['twitter']['description'] ]
		);
	}

	public function test_post_route_returns_post_seo_texts_without_variables_as_saved(): void {
		$data = $this->post_seo_data(
			[
				'title'       => 'Plain  title',
				'description' => 'Plain description',
			]
		);

		$this->assertSame( [ 'Plain  title', 'Plain description', 'Plain  title' ], [ $data['title'], $data['description'], $data['og']['title'] ] );
	}

	/**
	 * A published post of the given type.
	 *
	 * @param string $post_type Post type.
	 * @return \WP_Post
	 */
	private static function post( string $post_type ): \WP_Post {
		return new \WP_Post(
			[
				'ID'            => 422,
				'post_status'   => 'publish',
				'post_password' => '',
				'post_type'     => $post_type,
				'post_parent'   => 0,
				'post_title'    => 'Private course REST probe',
			]
		);
	}

	/**
	 * A course_category term.
	 *
	 * @return \WP_Term
	 */
	private static function term(): \WP_Term {
		return new \WP_Term(
			[
				'term_id'     => 9,
				'taxonomy'    => 'course_category',
				'name'        => 'Probe',
				'description' => 'Probe description',
			]
		);
	}

	/**
	 * Assert a 404 WP_Error with the given code.
	 *
	 * @param string $code   Expected error code.
	 * @param mixed  $result Callback result.
	 */
	private function assert_not_found( string $code, mixed $result ): void {
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( [ $code, [ 'status' => 404 ] ], [ $result->get_error_code(), $result->get_error_data() ] );
	}
}
