<?php
/**
 * PublicObjectAccess unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Rest;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Rest\PublicObjectAccess;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class PublicObjectAccessTest extends MonkeyTestCase {

	/**
	 * Post cases: [status, password, viewable type, granted caps, readable].
	 *
	 * @return array<string, array{0: string, 1: string, 2: bool, 3: array<int, string>, 4: bool}>
	 */
	public static function post_provider(): array {
		return [
			'published public post, anonymous'         => [ 'publish', '', true, [], true ],
			'published course (not viewable), anon'    => [ 'publish', '', false, [], false ],
			'published lesson, subscriber (read only)' => [ 'publish', '', false, [ 'read_post' ], false ],
			'published course, admin'                  => [ 'publish', '', false, [ 'edit_post', 'read_post' ], true ],
			'password-protected post, anonymous'       => [ 'publish', 'secret', true, [], false ],
			'password-protected post, subscriber'      => [ 'publish', 'secret', true, [ 'read_post' ], false ],
			'password-protected post, editor'          => [ 'publish', 'secret', true, [ 'edit_post' ], true ],
			'private post, anonymous'                  => [ 'private', '', true, [], false ],
			'private post, private reader'             => [ 'private', '', true, [ 'read_post' ], true ],
			'private course, private reader'           => [ 'private', '', false, [ 'read_post' ], false ],
			'draft, anonymous'                         => [ 'draft', '', true, [], false ],
		];
	}

	/**
	 * @dataProvider post_provider
	 *
	 * @param string             $status   Post status.
	 * @param string             $password Post password.
	 * @param bool               $viewable Whether the post type is viewable.
	 * @param array<int, string> $caps     Capabilities the user has.
	 * @param bool               $expected Expected readability.
	 */
	public function test_can_read_post( string $status, string $password, bool $viewable, array $caps, bool $expected ): void {
		Functions\when( 'post_password_required' )->alias( static fn( \WP_Post $post ): bool => '' !== $post->post_password );
		Functions\when( 'is_post_type_viewable' )->justReturn( $viewable );
		Functions\when( 'current_user_can' )->alias( static fn( string $cap ): bool => in_array( $cap, $caps, true ) );

		$post = new \WP_Post(
			[
				'ID'            => 422,
				'post_status'   => $status,
				'post_password' => $password,
				'post_type'     => 'course',
			]
		);

		$this->assertSame( $expected, PublicObjectAccess::can_read_post( $post ) );
	}

	/**
	 * Term cases: [taxonomy exists, viewable, can edit term, readable].
	 *
	 * @return array<string, array{0: bool, 1: bool, 2: bool, 3: bool}>
	 */
	public static function term_provider(): array {
		return [
			'public category, anonymous'        => [ true, true, false, true ],
			'private course_category, anon'     => [ true, false, false, false ],
			'private course_category, admin'    => [ true, false, true, true ],
			'unregistered taxonomy, even admin' => [ false, false, true, false ],
		];
	}

	/**
	 * @dataProvider term_provider
	 *
	 * @param bool $exists   Whether the taxonomy is registered.
	 * @param bool $viewable Whether the taxonomy is viewable.
	 * @param bool $can_edit Whether the user can edit the term.
	 * @param bool $expected Expected readability.
	 */
	public function test_can_read_term( bool $exists, bool $viewable, bool $can_edit, bool $expected ): void {
		Functions\when( 'taxonomy_exists' )->justReturn( $exists );
		Functions\when( 'is_taxonomy_viewable' )->justReturn( $viewable );
		Functions\when( 'current_user_can' )->alias( static fn( string $cap ): bool => 'edit_term' === $cap && $can_edit );

		$term = new \WP_Term(
			[
				'term_id'  => 9,
				'taxonomy' => 'course_category',
			]
		);

		$this->assertSame( $expected, PublicObjectAccess::can_read_term( $term ) );
	}

	/**
	 * Author cases: [public post count, can list users, readable].
	 *
	 * @return array<string, array{0: int, 1: bool, 2: bool}>
	 */
	public static function author_provider(): array {
		return [
			'author with public posts, anonymous' => [ 3, false, true ],
			'user without posts, anonymous'       => [ 0, false, false ],
			'user without posts, admin'           => [ 0, true, true ],
		];
	}

	/**
	 * @dataProvider author_provider
	 *
	 * @param int  $count      Published posts in viewable post types.
	 * @param bool $list_users Whether the user can list users.
	 * @param bool $expected   Expected readability.
	 */
	public function test_can_read_author( int $count, bool $list_users, bool $expected ): void {
		Functions\when( 'get_post_types' )->justReturn( [ 'post' => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Posts' ] ] ) ] );
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\when( 'count_user_posts' )->justReturn( (string) $count );
		Functions\when( 'current_user_can' )->alias( static fn( string $cap ): bool => 'list_users' === $cap && $list_users );

		$this->assertSame( $expected, PublicObjectAccess::can_read_author( new \WP_User( [ 'ID' => 5 ] ) ) );
	}

	/**
	 * An unreadable object answers like a missing one: same code, 404.
	 */
	public function test_post_error_is_indistinguishable_from_a_missing_post(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'post_password_required' )->justReturn( false );
		Functions\when( 'is_post_type_viewable' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );

		$error = PublicObjectAccess::post_error( new \WP_Post( [ 'ID' => 424, 'post_status' => 'publish', 'post_password' => '', 'post_type' => 'lesson' ] ) );

		$missing = PublicObjectAccess::post_not_found();
		$this->assertSame(
			[ $missing->get_error_code(), $missing->get_error_data() ],
			[ $error?->get_error_code(), $error?->get_error_data() ]
		);
	}
}
