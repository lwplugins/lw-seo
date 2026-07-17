<?php
/**
 * Tests for Yoast VariableConverter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Migration\Yoast;

use LightweightPlugins\SEO\Migration\Yoast\VariableConverter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\Yoast\VariableConverter
 */
final class VariableConverterTest extends TestCase {

	/**
	 * Matching variable names pass through unchanged.
	 *
	 * @return void
	 */
	public function test_passthrough_names_unchanged(): void {
		$this->assertSame(
			'%%title%% %%sep%% %%sitename%%',
			VariableConverter::convert( '%%title%% %%sep%% %%sitename%%' )
		);
	}

	/**
	 * Diverging variable names are renamed.
	 *
	 * @return void
	 */
	public function test_renamed_names(): void {
		$this->assertSame( '%%author%%', VariableConverter::convert( '%%name%%' ) );
		$this->assertSame( '%%category%%', VariableConverter::convert( '%%primary_category%%' ) );
	}

	/**
	 * Unsupported variable names are stripped.
	 *
	 * @return void
	 */
	public function test_unsupported_names_stripped(): void {
		$this->assertSame( 'Buy %%title%%', VariableConverter::convert( 'Buy %%pt_single%%%%title%%' ) );
	}

	/**
	 * Non-string and empty values pass through unchanged.
	 *
	 * @return void
	 */
	public function test_non_string_passthrough(): void {
		$this->assertSame( 42, VariableConverter::convert( 42 ) );
		$this->assertSame( '', VariableConverter::convert( '' ) );
	}
}
