<?php
/**
 * AltResolver unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Images;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Images\AltResolver;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Images\AltResolver
 */
final class AltResolverTest extends MonkeyTestCase {

	use OptionsStubTrait;

	private \WP_Post $att;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		Functions\when( 'get_attached_file' )->justReturn( '/u/a.jpg' );
		$this->att = new \WP_Post( [ 'ID' => 5, 'post_title' => 'Red apple', 'post_excerpt' => '' ] );
		$this->stub_options( [ 'image_alt_enabled' => true, 'image_alt_template' => '%%image_title%%' ] );
	}

	public function test_fills_empty_alt(): void {
		$this->assertSame( [ 'alt' => 'Red apple' ], AltResolver::fill( [ 'alt' => '' ], $this->att, null ) );
	}

	public function test_fills_missing_alt(): void {
		$this->assertSame( [ 'alt' => 'Red apple' ], AltResolver::fill( [ 'alt' => "  \n" ], $this->att, null ) );
		$this->assertSame( [ 'src' => 'x', 'alt' => 'Red apple' ], AltResolver::fill( [ 'src' => 'x' ], $this->att, null ) );
	}

	public function test_keeps_existing_alt(): void {
		$attrs = [ 'alt' => 'Mine' ];
		$this->assertSame( $attrs, AltResolver::fill( $attrs, $this->att, null ) );
	}

	public function test_skips_decorative(): void {
		foreach ( [ [ 'role' => 'presentation' ], [ 'role' => 'none' ], [ 'aria-hidden' => 'true' ] ] as $extra ) {
			$attrs = [ 'alt' => '' ] + $extra;
			$this->assertSame( $attrs, AltResolver::fill( $attrs, $this->att, null ) );
		}
	}

	public function test_empty_result_leaves_attrs(): void {
		$attrs = [ 'alt' => '' ];
		$this->assertSame( $attrs, AltResolver::fill( $attrs, null, null ) );
	}

	public function test_title_independent(): void {
		$this->stub_options( [ 'image_title_enabled' => true, 'image_title_template' => 'T %%image_title%%' ] );
		$this->assertSame( [ 'alt' => '', 'title' => 'T Red apple' ], AltResolver::fill( [ 'alt' => '' ], $this->att, null ) );
		$this->assertSame( [ 'title' => 'Own' ], AltResolver::fill( [ 'title' => 'Own' ], $this->att, null ) );
	}
}
