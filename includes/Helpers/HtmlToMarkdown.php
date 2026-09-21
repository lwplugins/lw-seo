<?php
/**
 * HTML to Markdown converter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Helpers;

use LightweightPlugins\SEO\Helpers\Markdown\BlockRenderer;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API properties (parentNode, ownerDocument) are camelCase.

/**
 * Converts rendered post HTML to Markdown by walking the DOM.
 */
final class HtmlToMarkdown {

	/**
	 * Elements dropped together with their content.
	 */
	private const NOISE_TAGS = [ 'script', 'style', 'noscript', 'template', 'nav', 'aside', 'form', 'button', 'select', 'input', 'textarea', 'svg', 'canvas' ];

	/**
	 * Convert HTML to Markdown.
	 *
	 * @param string $html HTML content.
	 * @return string Markdown ending with one newline, or '' for empty input.
	 */
	public static function convert( string $html ): string {
		if ( '' === trim( $html ) ) {
			return '';
		}

		$doc = new \DOMDocument();
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- libxml warns on HTML5 tags.
		@$doc->loadHTML(
			'<html><body><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . $html . '</body></html>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		if ( ! $body instanceof \DOMElement ) {
			return wp_strip_all_tags( $html );
		}

		self::remove_noise( $doc, $body );

		$markdown = trim( BlockRenderer::children( $body ) );

		return '' === $markdown ? '' : $markdown . "\n";
	}

	/**
	 * Remove comments and non-content elements.
	 *
	 * @param \DOMDocument $doc  Document.
	 * @param \DOMElement  $body Body element.
	 * @return void
	 */
	private static function remove_noise( \DOMDocument $doc, \DOMElement $body ): void {
		$comments = ( new \DOMXPath( $doc ) )->query( '//comment()' );
		$nodes    = false === $comments ? [] : iterator_to_array( $comments );

		foreach ( self::NOISE_TAGS as $tag ) {
			$nodes = array_merge( $nodes, iterator_to_array( $body->getElementsByTagName( $tag ) ) );
		}

		foreach ( $nodes as $node ) {
			$node->parentNode?->removeChild( $node );
		}
	}
}
