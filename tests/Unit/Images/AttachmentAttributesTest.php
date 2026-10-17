<?php
/**
 * AttachmentAttributes unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Images;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Images\AttachmentAttributes;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * @covers \LightweightPlugins\SEO\Images\AttachmentAttributes
 */
final class AttachmentAttributesTest extends MonkeyTestCase {

	use OptionsStubTrait;

	public function test_disabled_noop(): void {
		$this->stub_options();
		$att = new \WP_Post( [ 'ID' => 5, 'post_type' => 'attachment', 'post_title' => 'Red apple' ] );

		$this->assertSame( [ 'alt' => '' ], ( new AttachmentAttributes() )->filter( [ 'alt' => '' ], $att, 'full' ) );
	}

	#[RunInSeparateProcess]
	public function test_conflict_noop(): void {
		define( 'WPSEO_VERSION', '1' );
		$this->stub_options( [ 'image_alt_enabled' => true ] );
		Functions\when( 'did_action' )->justReturn( 1 );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'is_feed' )->justReturn( false );
		$att = new \WP_Post( [ 'ID' => 5, 'post_type' => 'attachment', 'post_title' => 'Red apple' ] );

		$this->assertSame( [ 'alt' => '' ], ( new AttachmentAttributes() )->filter( [ 'alt' => '' ], $att, 'full' ) );
	}

	public function test_fills_alt_on_front_end(): void {
		$this->stub_options( [ 'image_alt_enabled' => true, 'image_alt_template' => '%%image_title%%' ] );
		Functions\when( 'did_action' )->justReturn( 1 );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'is_feed' )->justReturn( false );
		Functions\when( 'get_post' )->justReturn( null );
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		Functions\when( 'get_attached_file' )->justReturn( '/u/a.jpg' );
		$att = new \WP_Post( [ 'ID' => 5, 'post_type' => 'attachment', 'post_title' => 'Red apple', 'post_excerpt' => '' ] );

		$this->assertSame( [ 'alt' => 'Red apple' ], ( new AttachmentAttributes() )->filter( [ 'alt' => '' ], $att, 'full' ) );
	}

	public function test_noop_before_the_query_ran(): void {
		$this->stub_options( [ 'image_alt_enabled' => true ] );
		Functions\when( 'did_action' )->justReturn( 0 );
		$att = new \WP_Post( [ 'ID' => 5, 'post_type' => 'attachment', 'post_title' => 'Red apple' ] );

		$this->assertSame( [ 'alt' => '' ], ( new AttachmentAttributes() )->filter( [ 'alt' => '' ], $att, 'full' ) );
	}
}
