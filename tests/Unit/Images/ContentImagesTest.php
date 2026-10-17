<?php
/**
 * ContentImages unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Images;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Images\ContentImages;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * @covers \LightweightPlugins\SEO\Images\ContentImages
 */
final class ContentImagesTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();
		require_once dirname( __DIR__, 2 ) . '/Stubs/html-tag-processor.php';
		$this->stub_options( [ 'image_alt_enabled' => true, 'image_alt_template' => '%%image_title%%' ] );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'is_feed' )->justReturn( false );
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		Functions\when( 'get_attached_file' )->justReturn( '/u/a.jpg' );
		Functions\when( 'get_post' )->alias(
			static fn( $id = null ) => is_int( $id ) ? new \WP_Post( [ 'ID' => $id, 'post_title' => 'Red apple', 'post_excerpt' => '' ] ) : null
		);
	}

	#[RunInSeparateProcess]
	public function test_fills_alt_in_tag(): void {
		$out = ( new ContentImages() )->filter( '<img src="a.jpg" alt="" />', 'the_content', 5 );

		$this->assertStringContainsString( 'alt="Red apple"', $out );
	}

	#[RunInSeparateProcess]
	public function test_keeps_existing_alt_in_tag(): void {
		$html = '<img src="a.jpg" alt="Mine" />';

		$this->assertSame( $html, ( new ContentImages() )->filter( $html, 'the_content', 5 ) );
	}
}
