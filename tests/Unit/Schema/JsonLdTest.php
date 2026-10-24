<?php
/**
 * Tests for the shared JSON-LD encoder.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Schema;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Schema\JsonLd;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Schema\JsonLd
 */
final class JsonLdTest extends MonkeyTestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_json_encode' )->alias( static fn( $data, $flags = 0 ) => json_encode( $data, $flags ) );
	}

	public function test_markup_characters_are_encoded(): void {
		$json = JsonLd::encode( [ 'name' => 'A <b> & C' ] );

		$this->assertStringNotContainsString( '<', $json );
		$this->assertStringNotContainsString( '>', $json );
		$this->assertStringNotContainsString( '&', $json );
		$this->assertSame( [ 'name' => 'A <b> & C' ], json_decode( $json, true ) );
	}

	public function test_urls_and_unicode_stay_readable(): void {
		$json = JsonLd::encode( [ 'url' => 'https://example.test/árvíztűrő/' ] );

		$this->assertStringContainsString( '"url": "https://example.test/árvíztűrő/"', $json );
	}
}
