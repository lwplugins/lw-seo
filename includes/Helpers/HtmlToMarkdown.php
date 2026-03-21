<?php
/**
 * HTML to Markdown converter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Helpers;

/**
 * Converts HTML content to Markdown using DOM parsing.
 */
final class HtmlToMarkdown {

	/**
	 * Node name to handler method mapping.
	 *
	 * @var array<string, string>
	 */
	private const NODE_MAP = [
		'h1'         => 'heading',
		'h2'         => 'heading',
		'h3'         => 'heading',
		'h4'         => 'heading',
		'h5'         => 'heading',
		'h6'         => 'heading',
		'p'          => 'paragraph',
		'pre'        => 'code_block',
		'ul'         => 'unordered_list',
		'ol'         => 'ordered_list',
		'blockquote' => 'blockquote',
		'table'      => 'table',
		'img'        => 'image',
	];

	/**
	 * Convert HTML string to Markdown.
	 *
	 * @param string $html HTML content.
	 * @return string Markdown content.
	 */
	public static function convert( string $html ): string {
		if ( '' === trim( $html ) ) {
			return '';
		}

		$html = self::prepare_html( $html );

		$doc = new \DOMDocument();
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@$doc->loadHTML(
			'<html><body>' . $html . '</body></html>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		if ( ! $body instanceof \DOMElement ) {
			return wp_strip_all_tags( $html );
		}

		return self::process_children( $body );
	}

	/**
	 * Process child nodes of an element.
	 *
	 * @param \DOMElement $parent Parent element.
	 * @return string
	 */
	private static function process_children( \DOMElement $parent ): string {
		$output = '';

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		foreach ( $parent->childNodes as $node ) {
			if ( $node instanceof \DOMText ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$text = trim( $node->textContent );
				if ( '' !== $text ) {
					$output .= $text . "\n\n";
				}
			} elseif ( $node instanceof \DOMElement ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$name = $node->nodeName;

				if ( isset( self::NODE_MAP[ $name ] ) ) {
					$method  = self::NODE_MAP[ $name ];
					$output .= MarkdownNodes::$method( $node );
				} elseif ( in_array( $name, [ 'div', 'section', 'article' ], true ) ) {
					$output .= self::process_children( $node );
				} else {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$text = trim( $node->textContent );
					if ( '' !== $text ) {
						$output .= $text . "\n\n";
					}
				}
			}
		}

		return trim( $output ) . "\n";
	}

	/**
	 * Prepare HTML for DOM parsing.
	 *
	 * @param string $html Raw HTML.
	 * @return string Cleaned HTML.
	 */
	private static function prepare_html( string $html ): string {
		// Remove script and style tags entirely.
		$html = (string) preg_replace( '/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html );

		// Ensure proper UTF-8 encoding for DOMDocument.
		$html = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . $html;

		return $html;
	}
}
