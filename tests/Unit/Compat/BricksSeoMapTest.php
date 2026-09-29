<?php
/**
 * Bricks page settings <-> LW SEO field mapping unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Compat;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Compat\BricksSeoMap;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class BricksSeoMapTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
	}

	public function test_reads_every_mapped_bricks_setting(): void {
		$lw = BricksSeoMap::to_lw(
			[
				'documentTitle'      => 'Doc title',
				'metaDescription'    => 'Meta desc',
				'metaKeywords'       => 'ignored',
				'metaRobots'         => [ 'noindex' ],
				'sharingTitle'       => '{post_title}',
				'sharingDescription' => 'Share desc',
				'sharingImage'       => [
					'id'  => 12,
					'url' => 'https://example.com/a.jpg',
				],
			]
		);

		$this->assertSame(
			[
				'title'          => 'Doc title',
				'description'    => 'Meta desc',
				'og_title'       => '%%title%%',
				'og_description' => 'Share desc',
				'og_image'       => 'https://example.com/a.jpg',
				'noindex'        => '1',
				'nofollow'       => '',
			],
			$lw
		);
	}

	public function test_robots_none_means_noindex_and_nofollow(): void {
		$lw = BricksSeoMap::to_lw( [ 'metaRobots' => [ 'none' ] ] );

		$this->assertSame( '1', $lw['noindex'] );
		$this->assertSame( '1', $lw['nofollow'] );
	}

	public function test_skips_empty_unconvertible_and_dynamic_values(): void {
		$lw = BricksSeoMap::to_lw(
			[
				'documentTitle'   => '',
				'metaDescription' => '{acf_intro}',
				'sharingImage'    => [
					'useDynamicData' => '{featured_image}',
					'url'            => 'https://example.com/b.jpg',
				],
			]
		);

		$this->assertSame(
			[
				'noindex'  => '',
				'nofollow' => '',
			],
			$lw
		);
	}

	public function test_changes_are_only_the_edited_non_empty_texts(): void {
		$old = [
			'documentTitle'   => 'Old title',
			'metaDescription' => 'Kept desc',
			'sharingTitle'    => 'Removed in Bricks',
		];
		$new = [
			'documentTitle'   => 'New title',
			'metaDescription' => 'Kept desc',
		];

		$this->assertSame( [ 'title' => 'New title' ], BricksSeoMap::changes( $old, $new ) );
	}

	public function test_robots_changes_go_both_ways(): void {
		$this->assertSame(
			[ 'noindex' => '' ],
			BricksSeoMap::changes( [ 'metaRobots' => [ 'noindex' ] ], [] )
		);
		$this->assertSame(
			[ 'nofollow' => '1' ],
			BricksSeoMap::changes( [ 'metaRobots' => [ 'noindex' ] ], [ 'metaRobots' => [ 'noindex', 'nofollow' ] ] )
		);
	}

	public function test_apply_text_keeps_other_bricks_settings(): void {
		$settings = BricksSeoMap::apply(
			[
				'scrollSnap'    => true,
				'documentTitle' => 'Old',
			],
			'title',
			'%%title%% | Brand'
		);

		$this->assertSame(
			[
				'scrollSnap'    => true,
				'documentTitle' => '{post_title} | Brand',
			],
			$settings
		);
	}

	public function test_apply_never_writes_empty_or_unconvertible_text(): void {
		$this->assertNull( BricksSeoMap::apply( [ 'documentTitle' => 'Keep' ], 'title', '' ) );
		$this->assertNull( BricksSeoMap::apply( [ 'documentTitle' => 'Keep' ], 'title', '%%title%% %%sep%% %%sitename%%' ) );
		$this->assertNull( BricksSeoMap::apply( [], 'canonical', 'https://example.com/' ) );
	}

	public function test_apply_robots_flags(): void {
		$this->assertSame(
			[ 'metaRobots' => [ 'noarchive', 'noindex' ] ],
			BricksSeoMap::apply( [ 'metaRobots' => [ 'noarchive' ] ], 'noindex', '1' )
		);
		$this->assertSame(
			[ 'metaRobots' => [ 'nofollow' ] ],
			BricksSeoMap::apply( [ 'metaRobots' => [ 'none' ] ], 'noindex', '' )
		);
		$this->assertSame(
			[ 'other' => 1 ],
			BricksSeoMap::apply(
				[
					'other'      => 1,
					'metaRobots' => [ 'noindex' ],
				],
				'noindex',
				''
			)
		);
	}

	public function test_apply_image(): void {
		$this->assertSame(
			[
				'sharingImage' => [
					'id'       => 7,
					'filename' => 'c.jpg',
					'size'     => 'full',
					'full'     => 'https://example.com/c.jpg',
					'url'      => 'https://example.com/c.jpg',
				],
			],
			BricksSeoMap::apply( [], 'og_image', 'https://example.com/c.jpg', 7 )
		);
	}
}
