<?php
/**
 * LLMS.txt document formatter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\LlmsTxt;

use LightweightPlugins\SEO\Helpers\HtmlToMarkdown;
use LightweightPlugins\SEO\Helpers\Markdown\InlineRenderer;

/**
 * Formats LLMS.txt per llmstxt.org: H1, optional blockquote summary,
 * optional free text, then H2 sections of "- [name](url): notes" lists.
 *
 * Titles, descriptions (SEO description or excerpt, author-controlled),
 * the summary and section headings become inert Markdown text, and URLs
 * go through the same scheme filter as the Markdown endpoint, so nothing
 * in them can turn into a live link, image or raw HTML downstream. Only
 * the admin-authored intro is emitted as raw Markdown.
 */
final class Document {

	/**
	 * Heading of the secondary-information section, always rendered last.
	 */
	public const OPTIONAL = 'Optional';

	/**
	 * Render the document. Content sections are a list, so two with the
	 * same heading stay separate, and the Optional links are passed on
	 * their own, so no content section can replace or absorb them.
	 *
	 * @param array{title: string, summary?: string, intro?: string, sections?: array<int, array{heading: string, links: array<int, array{title: string, url: string, description?: string}>}>, optional?: array<int, array{title: string, url: string, description?: string}>} $doc Document parts.
	 * @return string
	 */
	public static function render( array $doc ): string {
		$lines = [ '# ' . HtmlToMarkdown::plain_text( $doc['title'] ), '' ];

		$summary = HtmlToMarkdown::plain_text( $doc['summary'] ?? '' );
		if ( '' !== $summary ) {
			array_push( $lines, '> ' . $summary, '' );
		}

		$intro = trim( $doc['intro'] ?? '' );
		if ( '' !== $intro ) {
			array_push( $lines, $intro, '' );
		}

		$sections   = $doc['sections'] ?? [];
		$sections[] = [
			'heading' => self::OPTIONAL,
			'links'   => $doc['optional'] ?? [],
		];

		foreach ( $sections as $section ) {
			if ( [] === $section['links'] ) {
				continue;
			}
			array_push( $lines, '## ' . HtmlToMarkdown::plain_text( $section['heading'] ), '' );
			foreach ( $section['links'] as $link ) {
				$lines[] = self::link_line( $link );
			}
			$lines[] = '';
		}

		return rtrim( implode( "\n", $lines ) ) . "\n";
	}

	/**
	 * One list item. A URL the scheme filter rejects (javascript:, data:,
	 * vbscript:, however encoded) leaves the title as plain text.
	 *
	 * @param array{title: string, url: string, description?: string} $link Link.
	 * @return string
	 */
	private static function link_line( array $link ): string {
		$title       = HtmlToMarkdown::plain_text( $link['title'] );
		$url         = InlineRenderer::url( $link['url'] );
		$line        = '' === $url ? '- ' . $title : '- [' . $title . '](' . $url . ')';
		$description = HtmlToMarkdown::plain_text( $link['description'] ?? '' );

		return '' === $description ? $line : $line . ': ' . $description;
	}
}
