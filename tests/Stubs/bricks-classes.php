<?php
/**
 * Bricks theme API doubles.
 *
 * Only the members LW SEO calls, with the signatures of Bricks 2.4
 * (includes/database.php, helpers.php, frontend.php). Loaded by the unit
 * test bootstrap, and by PHPStan as the stub for the optional integration.
 * By default no post renders with Bricks; the `test_*` properties let a
 * test say otherwise.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace Bricks;

/**
 * Bricks\Database double.
 */
class Database {

	/**
	 * Data of the page being rendered.
	 *
	 * @var array<string, mixed>
	 */
	public static $page_data = [
		'preview_or_post_id' => 0,
		'language'           => '',
	];

	/**
	 * Test: Bricks content elements by post ID.
	 *
	 * @var array<int, array<int, array<string, mixed>>>
	 */
	public static array $test_content = [];

	/**
	 * Bricks data of a post and content area.
	 *
	 * @param int    $post_id      Post ID.
	 * @param string $content_area header|content|footer.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_data( $post_id = 0, $content_area = '' ) {
		return self::$test_content[ $post_id ] ?? [];
	}

	/**
	 * Test: reset every double to its default state.
	 *
	 * @return void
	 */
	public static function test_reset(): void {
		self::$page_data                 = [
			'preview_or_post_id' => 0,
			'language'           => '',
		];
		self::$test_content              = [];
		Helpers::$test_bricks_posts      = [];
		Frontend::$test_seen_preview_ids = [];
	}
}

/**
 * Bricks\Helpers double.
 */
class Helpers {

	/**
	 * Test: IDs of the posts that render with Bricks.
	 *
	 * @var array<int, int>
	 */
	public static array $test_bricks_posts = [];

	/**
	 * Whether a post renders with Bricks.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function render_with_bricks( $post_id = 0 ) {
		return in_array( $post_id, self::$test_bricks_posts, true );
	}
}

/**
 * Bricks\Frontend double.
 */
class Frontend {

	/**
	 * Test: Database::$page_data['preview_or_post_id'] at each render.
	 *
	 * @var array<int, mixed>
	 */
	public static array $test_seen_preview_ids = [];

	/**
	 * Rendered HTML of elements: each element's `settings.text` as a paragraph.
	 *
	 * @param array<int, array<string, mixed>> $elements       Elements.
	 * @param string                           $area           header|content|footer.
	 * @param int                              $source_post_id Source post ID.
	 * @return string|null Null for no elements, as Bricks returns.
	 */
	public static function render_data( $elements = [], $area = 'content', $source_post_id = 0 ) {
		if ( ! is_array( $elements ) || ! count( $elements ) ) {
			return null;
		}

		self::$test_seen_preview_ids[] = Database::$page_data['preview_or_post_id'];

		return implode( '', array_map( static fn( array $element ): string => '<p>' . $element['settings']['text'] . '</p>', $elements ) );
	}
}
