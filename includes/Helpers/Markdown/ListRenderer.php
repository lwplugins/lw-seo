<?php
/**
 * List Markdown rendering.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Helpers\Markdown;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API properties (childNodes, nodeName) are camelCase.

/**
 * Renders ul/ol with nesting (direct <li> children only).
 */
final class ListRenderer {

	/**
	 * Indentation per nesting level (valid under "- " and "10. " markers).
	 */
	private const INDENT = '    ';

	/**
	 * Render a list.
	 *
	 * @param \DOMElement $list  List element.
	 * @param int         $depth Nesting depth.
	 * @return string
	 */
	public static function render( \DOMElement $list, int $depth = 0 ): string {
		$ordered = 'ol' === $list->nodeName;
		$number  = max( 1, (int) $list->getAttribute( 'start' ) );
		$lines   = [];

		foreach ( $list->childNodes as $item ) {
			if ( ! $item instanceof \DOMElement || 'li' !== $item->nodeName ) {
				continue;
			}
			$marker  = $ordered ? $number . '.' : '-';
			$lines[] = self::item( $item, $marker, $depth );
			++$number;
		}

		return implode( "\n", $lines );
	}

	/**
	 * Render one item with its nested lists.
	 *
	 * @param \DOMElement $item   Li element.
	 * @param string      $marker List marker.
	 * @param int         $depth  Nesting depth.
	 * @return string
	 */
	private static function item( \DOMElement $item, string $marker, int $depth ): string {
		$text   = '';
		$nested = [];

		foreach ( $item->childNodes as $child ) {
			if ( $child instanceof \DOMElement && in_array( $child->nodeName, [ 'ul', 'ol' ], true ) ) {
				$nested[] = self::render( $child, $depth + 1 );
				continue;
			}
			$separator = $child instanceof \DOMElement && BlockRenderer::is_block( $child ) ? ' ' : '';
			$text      = InlineRenderer::append( $text . $separator, InlineRenderer::node( $child ) );
		}

		$text = trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\\\n", ' ', $text ) ) );
		$line = rtrim( str_repeat( self::INDENT, $depth ) . $marker . ' ' . $text );

		return implode( "\n", array_merge( [ $line ], array_filter( $nested ) ) );
	}
}
