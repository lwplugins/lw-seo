<?php
/**
 * TaxonomyRenderer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Markdown\TaxonomyRenderer;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class TaxonomyRendererTest extends MonkeyTestCase {

	/**
	 * The term name is plain text in the heading. (The post list goes through
	 * Eligibility/ContentSignals and needs too many stubs to test here; its
	 * link text and URL use HtmlToMarkdown::plain_text() and
	 * InlineRenderer::url(), which are tested directly.)
	 */
	public function test_body_heading_escapes_term_name(): void {
		Functions\when( 'get_term_meta' )->justReturn( '' );
		Functions\when( 'term_description' )->justReturn( '' );
		Functions\when( 'get_posts' )->justReturn( [] );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );

		$term = new \WP_Term(
			[
				'term_id'  => 3,
				'name'     => 'Cats &amp; [dogs](javascript:alert(1))',
				'taxonomy' => 'category',
			]
		);

		$this->assertSame( "# Cats & \\[dogs\\](javascript:alert(1))\n\n", ( new TaxonomyRenderer( $term ) )->body() );
	}
}
