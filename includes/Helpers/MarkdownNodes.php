<?php
/**
 * Markdown node handlers.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Helpers;

/**
 * Static methods for converting HTML DOM nodes to markdown.
 */
final class MarkdownNodes {

	/**
	 * Convert a heading node (h1-h6).
	 *
	 * @param \DOMElement $node The heading element.
	 * @return string
	 */
	public static function heading( \DOMElement $node ): string {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$level = (int) substr( $node->nodeName, 1 );
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$text   = trim( $node->textContent );
		$prefix = str_repeat( '#', $level );
		return $prefix . ' ' . $text . "\n\n";
	}

	/**
	 * Convert a paragraph node.
	 *
	 * @param \DOMElement $node The paragraph element.
	 * @return string
	 */
	public static function paragraph( \DOMElement $node ): string {
		$text = self::inline_content( $node );
		$text = trim( $text );
		if ( '' === $text ) {
			return '';
		}
		return $text . "\n\n";
	}

	/**
	 * Convert a link node.
	 *
	 * @param \DOMElement $node The anchor element.
	 * @return string
	 */
	public static function link( \DOMElement $node ): string {
		$href = $node->getAttribute( 'href' );
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$text = trim( $node->textContent );
		if ( '' === $href || '' === $text ) {
			return $text;
		}
		return '[' . $text . '](' . $href . ')';
	}

	/**
	 * Convert a bold/strong node.
	 *
	 * @param \DOMElement $node The element.
	 * @return string
	 */
	public static function bold( \DOMElement $node ): string {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return '**' . trim( $node->textContent ) . '**';
	}

	/**
	 * Convert an italic/em node.
	 *
	 * @param \DOMElement $node The element.
	 * @return string
	 */
	public static function italic( \DOMElement $node ): string {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return '*' . trim( $node->textContent ) . '*';
	}

	/**
	 * Convert an inline code node.
	 *
	 * @param \DOMElement $node The code element.
	 * @return string
	 */
	public static function inline_code( \DOMElement $node ): string {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return '`' . $node->textContent . '`';
	}

	/**
	 * Convert a pre/code block.
	 *
	 * @param \DOMElement $node The pre element.
	 * @return string
	 */
	public static function code_block( \DOMElement $node ): string {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$code = $node->textContent;
		return "```\n" . $code . "\n```\n\n";
	}

	/**
	 * Convert an unordered list.
	 *
	 * @param \DOMElement $node The ul element.
	 * @return string
	 */
	public static function unordered_list( \DOMElement $node ): string {
		return self::convert_list( $node, '-' );
	}

	/**
	 * Convert an ordered list.
	 *
	 * @param \DOMElement $node The ol element.
	 * @return string
	 */
	public static function ordered_list( \DOMElement $node ): string {
		return self::convert_list( $node, '1.' );
	}

	/**
	 * Convert a blockquote.
	 *
	 * @param \DOMElement $node The blockquote element.
	 * @return string
	 */
	public static function blockquote( \DOMElement $node ): string {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$text  = trim( $node->textContent );
		$lines = explode( "\n", $text );
		$lines = array_map( fn( string $line ): string => '> ' . trim( $line ), $lines );
		return implode( "\n", $lines ) . "\n\n";
	}

	/**
	 * Convert an image.
	 *
	 * @param \DOMElement $node The img element.
	 * @return string
	 */
	public static function image( \DOMElement $node ): string {
		$src = $node->getAttribute( 'src' );
		$alt = $node->getAttribute( 'alt' );
		return '![' . $alt . '](' . $src . ')';
	}

	/**
	 * Convert a table.
	 *
	 * @param \DOMElement $node The table element.
	 * @return string
	 */
	public static function table( \DOMElement $node ): string {
		$rows       = [];
		$has_header = false;

		foreach ( $node->getElementsByTagName( 'tr' ) as $tr ) {
			$cells  = [];
			$has_th = $tr->getElementsByTagName( 'th' )->length > 0;

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			foreach ( $tr->childNodes as $cell ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( $cell instanceof \DOMElement && in_array( $cell->nodeName, [ 'td', 'th' ], true ) ) {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$cells[] = trim( $cell->textContent );
				}
			}

			if ( empty( $cells ) ) {
				continue;
			}

			$rows[] = '| ' . implode( ' | ', $cells ) . ' |';

			// Add separator row after first header row.
			if ( $has_th && ! $has_header ) {
				$rows[]     = '| ' . implode( ' | ', array_fill( 0, count( $cells ), '---' ) ) . ' |';
				$has_header = true;
			}
		}

		// If no header row was found, add separator after first row.
		if ( ! $has_header && ! empty( $rows ) ) {
			$first_row = $rows[0];
			$col_count = substr_count( $first_row, '|' ) - 1;
			$separator = '| ' . implode( ' | ', array_fill( 0, $col_count, '---' ) ) . ' |';
			array_splice( $rows, 1, 0, [ $separator ] );
		}

		return implode( "\n", $rows ) . "\n\n";
	}

	/**
	 * Process inline content of an element.
	 *
	 * @param \DOMElement $node Parent element.
	 * @return string
	 */
	public static function inline_content( \DOMElement $node ): string {
		$output = '';

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		foreach ( $node->childNodes as $child ) {
			if ( $child instanceof \DOMText ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$output .= $child->textContent;
			} elseif ( $child instanceof \DOMElement ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$output .= match ( $child->nodeName ) {
					'a'            => self::link( $child ),
					'strong', 'b'  => self::bold( $child ),
					'em', 'i'      => self::italic( $child ),
					'code'         => self::inline_code( $child ),
					'img'          => self::image( $child ),
					'br'           => "\n",
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					default        => $child->textContent,
				};
			}
		}

		return $output;
	}

	/**
	 * Convert a list (ul or ol).
	 *
	 * @param \DOMElement $node   List element.
	 * @param string      $marker List marker (- or 1.).
	 * @return string
	 */
	private static function convert_list( \DOMElement $node, string $marker ): string {
		$items = [];
		$index = 1;

		foreach ( $node->getElementsByTagName( 'li' ) as $li ) {
			$prefix = '1.' === $marker ? $index . '.' : $marker;
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$items[] = $prefix . ' ' . trim( $li->textContent );
			++$index;
		}

		return implode( "\n", $items ) . "\n\n";
	}
}
