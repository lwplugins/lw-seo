<?php
/**
 * Tests for the RankMath variable converter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\RankMath;

use LightweightPlugins\SEO\Migration\VariableConverter;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\VariableConverter
 */
final class VariableConverterTest extends MonkeyTestCase {

	/**
	 * Issue #22: the source's %page% is empty on the first page, so it maps to
	 * %%pagenumber%%, not to %%page%% (which always prints the number).
	 *
	 * @dataProvider provide_templates
	 *
	 * @param string $source   Source template.
	 * @param string $expected LW SEO template.
	 */
	public function test_converts_variables( string $source, string $expected ): void {
		$this->assertSame( $expected, VariableConverter::convert( $source ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function provide_templates(): array {
		return [
			'page'          => [ '%title% %page% %sep% %sitename%', '%%title%% %%pagenumber%% %%sep%% %%sitename%%' ],
			'term and page' => [ '%term% %page% %sep% %sitename%', '%%term_title%% %%pagenumber%% %%sep%% %%sitename%%' ],
			'unmapped name' => [ '%sitename%', '%%sitename%%' ],
		];
	}
}
