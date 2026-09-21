<?php
/**
 * PostTypes unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Content;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Content\PostTypes;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class PostTypesTest extends MonkeyTestCase {

	private static function type( string $label ): \WP_Post_Type {
		return new \WP_Post_Type( [ 'labels' => (object) [ 'name' => $label ] ] );
	}

	public function test_post_types_skips_attachment_and_non_viewable_types(): void {
		Functions\when( 'get_post_types' )->justReturn(
			[
				'post'       => self::type( 'Posts' ),
				'attachment' => self::type( 'Media' ),
				'case_study' => self::type( 'Case Studies' ),
				'internal'   => self::type( 'Internal' ),
			]
		);
		Functions\when( 'is_post_type_viewable' )->alias( static fn( $object ): bool => 'Internal' !== $object->labels->name );

		$this->assertSame( [ 'post' => 'Posts', 'case_study' => 'Case Studies' ], PostTypes::post_types() );
	}

	public function test_taxonomies_skips_post_format_and_non_viewable_taxonomies(): void {
		Functions\when( 'get_taxonomies' )->justReturn(
			[
				'category'    => (object) [ 'labels' => (object) [ 'name' => 'Categories' ] ],
				'post_format' => (object) [ 'labels' => (object) [ 'name' => 'Formats' ] ],
				'genre'       => (object) [ 'labels' => (object) [ 'name' => 'Genres' ] ],
				'hidden'      => (object) [ 'labels' => (object) [ 'name' => 'Hidden' ] ],
			]
		);
		Functions\when( 'is_taxonomy_viewable' )->alias( static fn( $object ): bool => 'Hidden' !== $object->labels->name );

		$this->assertSame( [ 'category' => 'Categories', 'genre' => 'Genres' ], PostTypes::taxonomies() );
	}
}
