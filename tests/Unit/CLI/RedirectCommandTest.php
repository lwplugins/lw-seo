<?php
/**
 * RedirectCommand unit tests (the `test` rows).
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\CLI;

use LightweightPlugins\SEO\CLI\RedirectCommand;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\CLI\RedirectCommand
 */
final class RedirectCommandTest extends MonkeyTestCase {

	public function test_test_rows_list_every_rule_with_its_verdict(): void {
		$rows = RedirectCommand::test_rows(
			[
				'path'  => '/b',
				'match' => null,
				'rules' => [
					[ 'id' => 'x', 'source' => '/a', 'regex' => false, 'result' => 'no' ],
					[ 'id' => 'y', 'source' => '(oops', 'regex' => true, 'result' => 'invalid_regex' ],
				],
			]
		);

		$this->assertSame(
			[
				[ 'id' => 'x', 'source' => '/a', 'regex' => 'no', 'result' => 'no' ],
				[ 'id' => 'y', 'source' => '(oops', 'regex' => 'yes', 'result' => 'invalid_regex' ],
			],
			$rows
		);
	}
}
