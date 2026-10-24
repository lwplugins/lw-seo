<?php
/**
 * Tests for the term screen bootstrap data.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Editor;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Editor\TermScreen;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Editor\TermScreen
 */
final class TermScreenTest extends MonkeyTestCase {

	public function test_inline_data_is_markup_safe(): void {
		$inline = '';

		Functions\when( 'get_term_meta' )->justReturn( '' );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'wp_json_encode' )->alias( static fn( $data, $flags = 0 ) => json_encode( $data, $flags ) );
		Functions\when( 'wp_add_inline_script' )->alias(
			static function ( string $handle, string $data ) use ( &$inline ): bool {
				$inline = $data;
				return true;
			}
		);

		$term          = new \WP_Term();
		$term->term_id = 3;
		$term->name    = 'A <b> & C';

		ob_start();
		TermScreen::render( $term );
		ob_end_clean();

		$this->assertStringStartsWith( 'window.lwSeoTerm = ', $inline );
		$this->assertStringNotContainsString( '<', $inline );
		$this->assertStringNotContainsString( '&', $inline );

		$json = json_decode( substr( $inline, strlen( 'window.lwSeoTerm = ' ), -1 ), true );
		$this->assertSame( 'A <b> & C', $json['termName'] );
	}
}
