<?php
/**
 * Tests for Yoast VariableConverter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\Yoast;

use LightweightPlugins\SEO\Migration\Yoast\VariableConverter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\Yoast\VariableConverter
 */
final class VariableConverterTest extends TestCase {

	/**
	 * Variables shared by both plugins pass through unchanged.
	 *
	 * @return void
	 */
	public function test_keeps_shared_variables_unchanged(): void {
		$this->assertSame(
			'%%title%% %%sep%% %%sitename%%',
			VariableConverter::convert( '%%title%% %%sep%% %%sitename%%' )
		);
	}

	/**
	 * Yoast-specific names are renamed to LW SEO equivalents.
	 *
	 * @dataProvider provide_renamed
	 *
	 * @param string $input    Yoast template.
	 * @param string $expected LW SEO template.
	 * @return void
	 */
	public function test_renames_diverging_variables( string $input, string $expected ): void {
		$this->assertSame( $expected, VariableConverter::convert( $input ) );
	}

	/**
	 * Data provider for renamed variables.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function provide_renamed(): array {
		return [
			'author name'      => [ '%%name%%', '%%author%%' ],
			'primary category' => [ '%%primary_category%%', '%%category%%' ],
		];
	}

	/**
	 * Variables with no LW SEO equivalent are stripped.
	 *
	 * @return void
	 */
	public function test_strips_unsupported_variables(): void {
		$this->assertSame( 'Buy %%title%%', VariableConverter::convert( 'Buy %%pt_single%%%%title%%' ) );
	}

	/**
	 * Non-string and empty values pass through unchanged.
	 *
	 * @return void
	 */
	public function test_returns_non_string_and_empty_unchanged(): void {
		$this->assertSame( 42, VariableConverter::convert( 42 ) );
		$this->assertSame( '', VariableConverter::convert( '' ) );
	}
}
