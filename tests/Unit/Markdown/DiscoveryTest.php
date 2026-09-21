<?php
/**
 * Discovery unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use LightweightPlugins\SEO\Markdown\Discovery;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class DiscoveryTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public static function header_provider(): array {
		return [
			'both'          => [ 'https://x.test/a/md/', 'https://x.test/llms.txt', '<https://x.test/a/md/>; rel="alternate"; type="text/markdown", <https://x.test/llms.txt>; rel="describedby"' ],
			'markdown only' => [ 'https://x.test/a/md/', '', '<https://x.test/a/md/>; rel="alternate"; type="text/markdown"' ],
			'llms only'     => [ '', 'https://x.test/llms.txt', '<https://x.test/llms.txt>; rel="describedby"' ],
			'none'          => [ '', '', '' ],
		];
	}

	/**
	 * @dataProvider header_provider
	 *
	 * @param string $markdown Markdown URL.
	 * @param string $llms     llms.txt URL.
	 * @param string $expected Header value.
	 */
	public function test_link_header( string $markdown, string $llms, string $expected ): void {
		$this->assertSame( $expected, Discovery::link_header( $markdown, $llms ) );
	}
}
