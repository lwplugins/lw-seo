<?php
/**
 * ImageVars unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Images;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Images\ImageVars;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Images\ImageVars
 */
final class ImageVarsTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		Functions\when( 'get_option' )->alias( static fn( $k, $d = false ) => $d );
		Functions\when( 'get_attached_file' )->justReturn( '/u/2026/10/red-apple_photo-1024x768-scaled.jpg' );
		Functions\when( 'get_bloginfo' )->justReturn( 'Shop' );
	}

	private function attachment( string $title = 'Red apple', string $caption = '' ): \WP_Post {
		return new \WP_Post( [ 'ID' => 5, 'post_title' => $title, 'post_excerpt' => $caption ] );
	}

	public function test_image_title(): void {
		$this->assertSame( 'Red apple | Shop', ImageVars::replace( '%%image_title%% | %%sitename%%', $this->attachment(), null ) );
	}

	public function test_humanised_filename(): void {
		$this->assertSame( 'Red apple photo', ImageVars::replace( '%%image_filename%%', $this->attachment(), null ) );
	}

	public function test_strips_tags_and_entities(): void {
		$this->assertSame(
			'Tom & "Jerry"',
			ImageVars::replace( '%%image_title%%', $this->attachment( '<b>Tom</b> &amp; &quot;Jerry&quot;' ), null )
		);
	}

	public function test_unknown_attachment_empty_image_vars(): void {
		$this->assertSame( '', ImageVars::replace( '%%image_title%% %%image_caption%% %%image_filename%%', null, null ) );
	}
}
