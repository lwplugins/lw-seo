<?php
/**
 * Inline Markdown rendering.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Helpers\Markdown;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API properties (childNodes, nodeName, textContent) are camelCase.

/**
 * Renders inline content: text, emphasis, links, images, code, breaks.
 */
final class InlineRenderer {

	/**
	 * Render all children of a node inline.
	 *
	 * @param \DOMNode $node Parent node.
	 * @return string
	 */
	public static function content( \DOMNode $node ): string {
		$output = '';

		foreach ( $node->childNodes as $child ) {
			$output .= self::node( $child );
		}

		return $output;
	}

	/**
	 * Render one node inline.
	 *
	 * @param \DOMNode $node Node.
	 * @return string
	 */
	public static function node( \DOMNode $node ): string {
		if ( $node instanceof \DOMText ) {
			return (string) preg_replace( '/\s+/u', ' ', $node->textContent );
		}

		if ( ! $node instanceof \DOMElement ) {
			return '';
		}

		return match ( $node->nodeName ) {
			'a'            => self::link( $node ),
			'strong', 'b'  => self::wrap( '**', $node ),
			'em', 'i'      => self::wrap( '*', $node ),
			'del', 's'     => self::wrap( '~~', $node ),
			'code', 'kbd'  => self::code( $node->textContent ),
			'img'          => self::image( $node ),
			'br'           => "\\\n",
			'meta', 'link' => '',
			default        => self::content( $node ),
		};
	}

	/**
	 * Clean a URL for a Markdown destination.
	 *
	 * @param string $url Raw URL.
	 * @return string '' when unusable.
	 */
	public static function url( string $url ): string {
		$url = trim( $url );

		return str_replace( [ ' ', '(', ')' ], [ '%20', '%28', '%29' ], $url );
	}

	/**
	 * Link.
	 *
	 * @param \DOMElement $node Anchor.
	 * @return string
	 */
	private static function link( \DOMElement $node ): string {
		$text = trim( self::content( $node ) );
		$href = self::url( $node->getAttribute( 'href' ) );

		if ( '' === $text || '' === $href || str_starts_with( $href, '#' ) ) {
			return $text;
		}

		return '[' . $text . '](' . $href . ')';
	}

	/**
	 * Emphasis-style wrapper.
	 *
	 * @param string      $marker Marker.
	 * @param \DOMElement $node   Element.
	 * @return string
	 */
	private static function wrap( string $marker, \DOMElement $node ): string {
		$inner = trim( self::content( $node ) );

		return '' === $inner ? '' : $marker . $inner . $marker;
	}

	/**
	 * Inline code span, safe for embedded backticks.
	 *
	 * @param string $text Code text.
	 * @return string
	 */
	private static function code( string $text ): string {
		$text = (string) preg_replace( '/\s+/u', ' ', $text );
		if ( '' === trim( $text ) ) {
			return '';
		}

		return str_contains( $text, '`' ) ? '`` ' . $text . ' ``' : '`' . $text . '`';
	}

	/**
	 * Image; lazy-load attributes win over placeholder data: URIs.
	 *
	 * @param \DOMElement $node Img element.
	 * @return string
	 */
	private static function image( \DOMElement $node ): string {
		$alt = str_replace( [ '[', ']' ], '', trim( $node->getAttribute( 'alt' ) ) );

		foreach ( [ 'data-src', 'data-lazy-src', 'src' ] as $attribute ) {
			$src = self::url( $node->getAttribute( $attribute ) );
			if ( '' !== $src && ! str_starts_with( strtolower( $src ), 'data:' ) ) {
				return '![' . $alt . '](' . $src . ')';
			}
		}

		return '';
	}
}
