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
		$att = new \WP_Post( [ 'ID' => 5, 'post_title' => 'Red apple' ] );

		$this->assertSame( [ 'alt' => '' ], ( new AttachmentAttributes() )->filter( [ 'alt' => '' ], $att, 'full' ) );
	}

	#[RunInSeparateProcess]
	public function test_conflict_noop(): void {
		define( 'WPSEO_VERSION', '1' );
		$this->stub_options( [ 'image_alt_enabled' => true ] );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'is_feed' )->justReturn( false );
		$att = new \WP_Post( [ 'ID' => 5, 'post_title' => 'Red apple' ] );

		$this->assertSame( [ 'alt' => '' ], ( new AttachmentAttributes() )->filter( [ 'alt' => '' ], $att, 'full' ) );
	}
}
