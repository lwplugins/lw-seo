<?php
/**
 * llms-full.txt assembly unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\LlmsTxt;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\LlmsTxt\FullText;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class FullTextTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $s ): string => trim( strip_tags( $s ) ) );
	}

	public function test_concatenates_chunks_under_the_limit(): void {
		$this->assertSame( "# Site\n\naaa\nbbb", FullText::assemble( 'Site', [ 'aaa', 'bbb' ] ) );
	}

	public function test_stops_at_the_size_limit_and_never_renders_further_chunks(): void {
		$rendered = [];
		$chunks   = ( static function () use ( &$rendered ): \Generator {
			foreach ( [ 'aaa', 'bbb', 'ccc' ] as $chunk ) {
				$rendered[] = $chunk;
				yield $chunk;
			}
		} )();

		$result = FullText::assemble( 'Site', $chunks, 12 );

		$this->assertSame( "# Site\n\naaa" . FullText::TRUNCATED, $result );
		$this->assertSame( [ 'aaa', 'bbb' ], $rendered );
	}
}
