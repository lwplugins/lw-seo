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

	protected function tearDown(): void {
		unset( $GLOBALS['post'] );
		parent::tearDown();
	}

	public function test_concatenates_chunks_under_the_limit(): void {
		$this->assertSame( "# Site\n\naaa\nbbb", FullText::assemble( 'Site', [ 'aaa', 'bbb' ] ) );
	}

	public function test_title_line_leaves_no_live_markup(): void {
		$result = FullText::assemble( '&lt;img src=x onerror=alert(1)&gt; [x](javascript:alert(1)) `y`', [] );

		$this->assertSame( "# \\<img src=x onerror=alert(1)\\> \\[x\\](javascript:alert(1)) \\`y\\`\n", $result );
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

	public function test_chunk_restores_the_previous_global_post(): void {
		$this->stub_cheap_dispatcher_body();
		$previous = new \WP_Post( [ 'ID' => 1, 'post_type' => 'post', 'post_excerpt' => '' ] );
		$GLOBALS['post'] = $previous;

		FullText::chunk( new \WP_Post( [ 'ID' => 2, 'post_type' => 'post', 'post_excerpt' => '' ] ) );

		$this->assertSame( $previous, $GLOBALS['post'] );
	}

	public function test_chunk_leaves_no_global_post_when_there_was_none(): void {
		$this->stub_cheap_dispatcher_body();
		unset( $GLOBALS['post'] );

		FullText::chunk( new \WP_Post( [ 'ID' => 2, 'post_type' => 'post', 'post_excerpt' => '' ] ) );

		$this->assertArrayNotHasKey( 'post', $GLOBALS );
	}

	/**
	 * A page whose content the renderer can't reach (a page builder, a theme
	 * template) must still carry the summary llms.txt shows for it.
	 */
	public function test_chunk_puts_the_description_between_the_url_and_the_body(): void {
		$this->stub_cheap_dispatcher_body( 'Short summary' );

		$this->assertSame(
			"---\n\nURL: https://x.test/a/\n\nDescription: Short summary\n\nCustom markdown body\n",
			FullText::chunk( new \WP_Post( [ 'ID' => 2, 'post_type' => 'post', 'post_excerpt' => '' ] ) )
		);
	}

	public function test_chunk_description_leaves_no_live_markup(): void {
		$this->stub_cheap_dispatcher_body( '[x](javascript:alert(1)) &lt;img src=x onerror=alert(1)&gt;' );

		$this->assertStringContainsString(
			"Description: \\[x\\](javascript:alert(1)) \\<img src=x onerror=alert(1)\\>\n",
			FullText::chunk( new \WP_Post( [ 'ID' => 2, 'post_type' => 'post', 'post_excerpt' => '' ] ) )
		);
	}

	public function test_chunk_omits_the_description_line_when_there_is_none(): void {
		$this->stub_cheap_dispatcher_body();

		$this->assertSame(
			"---\n\nURL: https://x.test/a/\n\nCustom markdown body\n",
			FullText::chunk( new \WP_Post( [ 'ID' => 2, 'post_type' => 'post', 'post_excerpt' => '' ] ) )
		);
	}

	/**
	 * Stub Dispatcher::body()'s dependencies with the custom-Markdown path,
	 * the cheapest route through PostRenderer::body().
	 *
	 * @param string $description SEO description meta.
	 */
	private function stub_cheap_dispatcher_body( string $description = '' ): void {
		Functions\when( 'get_post_meta' )->alias(
			static fn( int $id, string $key ): string => '_lw_seo_description' === $key ? $description : 'Custom markdown body'
		);
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'setup_postdata' )->justReturn( true );
		Functions\when( 'get_permalink' )->justReturn( 'https://x.test/a/' );
	}
}
