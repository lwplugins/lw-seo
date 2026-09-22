<?php
/**
 * Canonical unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Meta;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Meta\Canonical;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Meta\Canonical
 */
final class CanonicalTest extends MonkeyTestCase {

	public function test_core_canonical_url_uses_the_custom_canonical(): void {
		Functions\when( 'get_post_meta' )->justReturn( 'https://example.com/original/' );

		$url = ( new Canonical() )->filter_core_url( 'https://example.com/copy/', new \WP_Post( [ 'ID' => 7 ] ) );

		$this->assertSame( 'https://example.com/original/', $url );
	}

	public function test_core_canonical_url_is_kept_without_a_custom_canonical(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$url = ( new Canonical() )->filter_core_url( 'https://example.com/post/', new \WP_Post( [ 'ID' => 7 ] ) );

		$this->assertSame( 'https://example.com/post/', $url );
	}
}
