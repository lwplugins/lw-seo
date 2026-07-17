<?php
/**
 * Tests for Yoast SeparatorMap.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Migration\Yoast;

use LightweightPlugins\SEO\Migration\Yoast\SeparatorMap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\Yoast\SeparatorMap
 */
final class SeparatorMapTest extends TestCase {

	/**
	 * Known Yoast tokens map to LW-supported characters.
	 *
	 * @return void
	 */
	public function test_known_tokens_map_to_chars(): void {
		$this->assertSame( '—', SeparatorMap::to_char( 'sc-mdash' ) );
		$this->assertSame( '|', SeparatorMap::to_char( 'sc-pipe' ) );
		$this->assertSame( '»', SeparatorMap::to_char( 'sc-raquo' ) );
		$this->assertSame( '>', SeparatorMap::to_char( 'sc-lt' ) );
	}

	/**
	 * Unknown tokens fall back to a dash.
	 *
	 * @return void
	 */
	public function test_unknown_token_falls_back_to_dash(): void {
		$this->assertSame( '-', SeparatorMap::to_char( 'sc-bull' ) );
		$this->assertSame( '-', SeparatorMap::to_char( '' ) );
		$this->assertSame( '-', SeparatorMap::to_char( 'garbage' ) );
	}

	/**
	 * A literal supported char passes through unchanged.
	 *
	 * @return void
	 */
	public function test_literal_char_passes_through_if_supported(): void {
		$this->assertSame( '|', SeparatorMap::to_char( '|' ) );
	}
}
