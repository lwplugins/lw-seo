<?php
/**
 * Tests for Yoast SeparatorMap.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\Yoast;

use LightweightPlugins\SEO\Migration\Yoast\SeparatorMap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\Yoast\SeparatorMap
 */
final class SeparatorMapTest extends TestCase {

	/**
	 * Known Yoast tokens map to their LW-supported character.
	 *
	 * @dataProvider provide_known_tokens
	 *
	 * @param string $token    Yoast separator token.
	 * @param string $expected Expected character.
	 * @return void
	 */
	public function test_maps_known_token_to_character( string $token, string $expected ): void {
		$this->assertSame( $expected, SeparatorMap::to_char( $token ) );
	}

	/**
	 * Data provider for known token → char.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function provide_known_tokens(): array {
		return [
			'em dash'      => [ 'sc-mdash', '—' ],
			'pipe'         => [ 'sc-pipe', '|' ],
			'right angle'  => [ 'sc-raquo', '»' ],
			'greater than' => [ 'sc-lt', '>' ],
			'middot'       => [ 'sc-middot', '·' ],
		];
	}

	/**
	 * Unknown tokens fall back to a dash.
	 *
	 * @dataProvider provide_unknown_tokens
	 *
	 * @param string $token Unknown token.
	 * @return void
	 */
	public function test_falls_back_to_dash_for_unknown_token( string $token ): void {
		$this->assertSame( '-', SeparatorMap::to_char( $token ) );
	}

	/**
	 * Data provider for unknown tokens.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function provide_unknown_tokens(): array {
		return [
			'unmapped yoast token' => [ 'sc-bull' ],
			'empty string'         => [ '' ],
			'garbage'              => [ 'garbage' ],
		];
	}

	/**
	 * A literal supported character passes through unchanged.
	 *
	 * @return void
	 */
	public function test_passes_through_supported_literal_character(): void {
		$this->assertSame( '|', SeparatorMap::to_char( '|' ) );
	}
}
