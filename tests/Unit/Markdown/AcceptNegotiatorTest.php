<?php
/**
 * AcceptNegotiator unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use LightweightPlugins\SEO\Markdown\AcceptNegotiator;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class AcceptNegotiatorTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function accept_provider(): array {
		return [
			'empty'                  => [ '', false ],
			'browser'                => [ 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', false ],
			'markdown only'          => [ 'text/markdown', true ],
			'markdown preferred'     => [ 'text/markdown, text/html;q=0.9', true ],
			'html preferred'         => [ 'text/html, text/markdown;q=0.5', false ],
			'markdown refused'       => [ 'text/markdown;q=0', false ],
			'case insensitive'       => [ 'TEXT/MARKDOWN', true ],
			'tie goes to markdown'   => [ 'text/markdown;q=0.8, text/html;q=0.8', true ],
			'markdown with wildcard' => [ 'text/markdown, */*', true ],
		];
	}

	/**
	 * @dataProvider accept_provider
	 *
	 * @param string $accept   Accept header.
	 * @param bool   $expected Whether Markdown wins.
	 */
	public function test_prefers_markdown( string $accept, bool $expected ): void {
		$this->assertSame( $expected, AcceptNegotiator::prefers_markdown( $accept ) );
	}
}
