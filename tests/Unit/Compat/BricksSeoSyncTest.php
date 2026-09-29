<?php
/**
 * Bricks <-> LW SEO sync unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Compat;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Compat\BricksSeoSync;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class BricksSeoSyncTest extends MonkeyTestCase {

	private const POST = 5;

	private const KEY = BricksSeoSync::BRICKS_KEY;

	/**
	 * Post meta by post ID and key.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $meta = [];

	private BricksSeoSync $sync;

	protected function setUp(): void {
		parent::setUp();
		$this->sync = new BricksSeoSync();
		$this->meta = [];

		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'attachment_url_to_postid' )->justReturn( 0 );
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'get_post_meta' )->alias( fn( $id, $key ) => $this->meta[ $id ][ $key ] ?? '' );
		Functions\when( 'update_post_meta' )->alias(
			function ( $id, $key, $value ) {
				$this->meta[ $id ][ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_post_meta' )->alias(
			function ( $id, $key ) {
				unset( $this->meta[ $id ][ $key ] );
				return true;
			}
		);
	}

	/**
	 * Simulate a Bricks builder save, as WordPress runs the meta hooks.
	 *
	 * @param array<string, mixed> $settings New settings.
	 */
	private function bricks_save( array $settings ): void {
		$this->sync->remember( null, self::POST, self::KEY );
		$this->meta[ self::POST ][ self::KEY ] = $settings;
		$this->sync->written( 1, self::POST, self::KEY, $settings );
	}

	/**
	 * Simulate an LW SEO field save ('' deletes it, as Options::set_post_meta does).
	 */
	private function lw_save( string $field, string $value ): void {
		$key = '_lw_seo_' . $field;

		if ( '' === $value ) {
			unset( $this->meta[ self::POST ][ $key ] );
			$this->sync->deleted( [ 1 ], self::POST, $key );
			return;
		}

		$this->meta[ self::POST ][ $key ] = $value;
		$this->sync->written( 1, self::POST, $key, $value );
	}

	public function test_bricks_save_copies_seo_values_into_lw_seo(): void {
		$this->bricks_save(
			[
				'documentTitle'   => 'Öko reklámajándék | Brand',
				'metaDescription' => 'Fenntartható ajándék',
				'metaRobots'      => [ 'noindex' ],
			]
		);

		$this->assertSame( 'Öko reklámajándék | Brand', $this->meta[ self::POST ]['_lw_seo_title'] );
		$this->assertSame( 'Fenntartható ajándék', $this->meta[ self::POST ]['_lw_seo_description'] );
		$this->assertSame( '1', $this->meta[ self::POST ]['_lw_seo_noindex'] );
	}

	public function test_clearing_a_bricks_field_keeps_the_lw_seo_value(): void {
		$this->meta[ self::POST ] = [
			self::KEY       => [ 'documentTitle' => 'Title' ],
			'_lw_seo_title' => 'Title',
		];

		$this->bricks_save( [ 'scrollSnap' => true ] );

		$this->assertSame( 'Title', $this->meta[ self::POST ]['_lw_seo_title'] );
	}

	public function test_unchanged_bricks_value_does_not_overwrite_lw_seo(): void {
		$this->meta[ self::POST ] = [
			self::KEY       => [ 'documentTitle' => 'Old Bricks title' ],
			'_lw_seo_title' => 'Newer LW title',
		];

		$this->bricks_save(
			[
				'documentTitle' => 'Old Bricks title',
				'scrollSnap'    => true,
			]
		);

		$this->assertSame( 'Newer LW title', $this->meta[ self::POST ]['_lw_seo_title'] );
	}

	public function test_lw_save_copies_into_bricks_settings_of_a_bricks_post(): void {
		$this->meta[ self::POST ] = [ '_bricks_editor_mode' => 'bricks' ];

		$this->lw_save( 'description', 'LW description' );

		$this->assertSame( [ 'metaDescription' => 'LW description' ], $this->meta[ self::POST ][ self::KEY ] );
	}

	public function test_lw_save_leaves_posts_not_edited_with_bricks_alone(): void {
		$this->lw_save( 'title', 'LW title' );

		$this->assertArrayNotHasKey( self::KEY, $this->meta[ self::POST ] );
	}

	public function test_clearing_an_lw_seo_text_keeps_the_bricks_value(): void {
		$this->meta[ self::POST ] = [
			self::KEY       => [ 'documentTitle' => 'Bricks title' ],
			'_lw_seo_title' => 'Bricks title',
		];

		$this->lw_save( 'title', '' );

		$this->assertSame( [ 'documentTitle' => 'Bricks title' ], $this->meta[ self::POST ][ self::KEY ] );
	}

	public function test_switching_off_lw_noindex_removes_it_from_bricks(): void {
		$this->meta[ self::POST ] = [
			self::KEY         => [
				'documentTitle' => 'T',
				'metaRobots'    => [ 'noindex' ],
			],
			'_lw_seo_noindex' => '1',
		];

		$this->lw_save( 'noindex', '' );

		$this->assertSame( [ 'documentTitle' => 'T' ], $this->meta[ self::POST ][ self::KEY ] );
	}

	public function test_paused_writes_are_not_synced(): void {
		$this->meta[ self::POST ] = [ '_bricks_editor_mode' => 'bricks' ];

		BricksSeoSync::paused( fn() => $this->lw_save( 'title', 'Imported' ) );

		$this->assertArrayNotHasKey( self::KEY, $this->meta[ self::POST ] );
	}
}
