<?php
/**
 * Table Markdown rendering.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Helpers\Markdown;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API properties (childNodes, nodeName) are camelCase.

/**
 * Renders a table as a pipe table; the first row is the header.
 */
final class TableRenderer {

	/**
	 * Render a table.
	 *
	 * @param \DOMElement $table Table element.
	 * @return string
	 */
	public static function render( \DOMElement $table ): string {
		$rows = self::rows( $table );
		if ( [] === $rows ) {
			return '';
		}

		$width = max( array_map( 'count', $rows ) );
		$rows  = array_map( static fn( array $row ): array => array_pad( $row, $width, '' ), $rows );
		$lines = [ self::line( $rows[0] ), self::line( array_fill( 0, $width, '---' ) ) ];

		foreach ( array_slice( $rows, 1 ) as $row ) {
			$lines[] = self::line( $row );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Rows of this table only (never of nested tables).
	 *
	 * @param \DOMElement $table Table element.
	 * @return array<int, array<int, string>>
	 */
	private static function rows( \DOMElement $table ): array {
		$rows = [];

		foreach ( $table->childNodes as $child ) {
			if ( ! $child instanceof \DOMElement ) {
				continue;
			}
			$trs = in_array( $child->nodeName, [ 'thead', 'tbody', 'tfoot' ], true ) ? $child->childNodes : [ $child ];
			foreach ( $trs as $tr ) {
				if ( $tr instanceof \DOMElement && 'tr' === $tr->nodeName ) {
					$rows[] = self::cells( $tr );
				}
			}
		}

		return array_values( array_filter( $rows ) );
	}

	/**
	 * Cell texts of a row, pipes escaped.
	 *
	 * @param \DOMElement $tr Row.
	 * @return array<int, string>
	 */
	private static function cells( \DOMElement $tr ): array {
		$cells = [];

		foreach ( $tr->childNodes as $cell ) {
			if ( $cell instanceof \DOMElement && in_array( $cell->nodeName, [ 'td', 'th' ], true ) ) {
				$text    = trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\\\n", ' ', InlineRenderer::content( $cell ) ) ) );
				$cells[] = str_replace( '|', '\|', $text );
			}
		}

		return $cells;
	}

	/**
	 * One pipe-table line.
	 *
	 * @param array<int, string> $cells Cells.
	 * @return string
	 */
	private static function line( array $cells ): string {
		return '| ' . implode( ' | ', $cells ) . ' |';
	}
}
