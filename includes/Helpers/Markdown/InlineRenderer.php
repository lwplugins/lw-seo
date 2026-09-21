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
	 * Schemes that must never become Markdown links.
	 */
	private const UNSAFE_SCHEMES = [ 'javascript:', 'vbscript:', 'data:' ];

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
			return self::escape( (string) preg_replace( '/\s+/u', ' ', $node->textContent ) );
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
	 * CommonMark renderers decode entities/backslash escapes and strip a
	 * wrapping `<…>` from link targets before applying scheme rules, so the
	 * scheme probe has to undo the same tricks or an encoded/wrapped
	 * javascript:/data: URL slips through as a live link.
	 *
	 * @param string $url Raw URL.
	 * @return string '' when unusable.
	 */
	public static function url( string $url ): string {
		$url = trim( $url );

		$probe = $url;
		for ( $pass = 0; $pass < 5; $pass++ ) {
			$decoded = html_entity_decode( $probe, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( $decoded === $probe ) {
				break;
			}
			$probe = $decoded;
		}
		$probe = strtolower( (string) preg_replace( '/[\x00-\x20<>\\\\]+/u', '', $probe ) );

		foreach ( self::UNSAFE_SCHEMES as $scheme ) {
			if ( str_starts_with( $probe, $scheme ) ) {
				return '';
			}
		}

		return str_replace( [ ' ', '(', ')', '<', '>' ], [ '%20', '%28', '%29', '%3C', '%3E' ], $url );
	}

	/**
	 * Escape characters that could otherwise start Markdown link/image
	 * syntax or be read as raw HTML (an autolink or a tag) by a downstream
	 * CommonMark renderer. Code spans and fenced code blocks bypass this
	 * (they render their textContent raw) and must stay unescaped.
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	public static function escape( string $text ): string {
		return (string) preg_replace_callback(
			'/[\x5C\x5B\x5D<>]/',
			static fn( array $matches ): string => '\\' . $matches[0],
			$text
		);
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
	 * Emphasis-style wrapper. Leading/trailing whitespace stays outside the
	 * markers so it doesn't glue the emphasis to adjacent text (CommonMark
	 * ignores emphasis markers with whitespace immediately inside them).
	 *
	 * @param string      $marker Marker.
	 * @param \DOMElement $node   Element.
	 * @return string
	 */
	private static function wrap( string $marker, \DOMElement $node ): string {
		$text  = self::content( $node );
		$inner = trim( $text );
		if ( '' === $inner ) {
			return '';
		}

		$leading  = substr( $text, 0, strlen( $text ) - strlen( ltrim( $text ) ) );
		$trailing = substr( $text, strlen( rtrim( $text ) ) );

		return $leading . $marker . $inner . $marker . $trailing;
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
		$alt = str_replace( [ '[', ']', '\\', '<', '>' ], '', trim( $node->getAttribute( 'alt' ) ) );

		foreach ( [ 'data-src', 'data-lazy-src', 'src' ] as $attribute ) {
			$src = self::url( $node->getAttribute( $attribute ) );
			if ( '' !== $src ) {
				return '![' . $alt . '](' . $src . ')';
			}
		}

		return '';
	}
}
