<?php
/**
 * HTML sitemap block definition tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\HtmlSitemap;

use LightweightPlugins\SEO\Blocks\HtmlSitemap\Block;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\SEO\Blocks\HtmlSitemap\Block
 */
final class BlockTest extends TestCase {

	private const DIR = __DIR__ . '/../../../includes/Blocks/HtmlSitemap/';

	public function test_block_json_matches_the_block_and_its_script(): void {
		$this->assertFileExists( self::DIR . 'block.json' );

		$json = json_decode( (string) file_get_contents( self::DIR . 'block.json' ), true );

		$this->assertIsArray( $json );
		$this->assertSame( Block::NAME, $json['name'] );
		$this->assertSame( 3, $json['apiVersion'] );
		$this->assertSame( [ 'max', 'order', 'post_types', 'taxonomies' ], $this->sorted_keys( $json['attributes'] ) );

		$script = (string) file_get_contents( __DIR__ . '/../../../assets/js/blocks/html-sitemap.js' );
		$this->assertStringContainsString( "'" . $json['name'] . "'", $script );
		foreach ( array_keys( $json['attributes'] ) as $attribute ) {
			$this->assertStringContainsString( $attribute, $script );
		}
	}

	/**
	 * @param array<string, mixed> $array Array.
	 * @return array<int, string>
	 */
	private function sorted_keys( array $array ): array {
		$keys = array_keys( $array );
		sort( $keys );

		return $keys;
	}
}
