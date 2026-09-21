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
	 * Render all children of a node inline. A backtick left at the end of
	 * one piece (an escaped trailing backtick in text, or a raw code-span
	 * fence) is separated with a space from a backtick starting the next
	 * piece (a raw code-span fence), so the two can never read as one
	 * longer run of backticks — which would merge a closing fence with the
	 * next span's opening fence, or make an escaped backtick look like
	 * part of a fence.
	 *
	 * @param \DOMNode $node Parent node.
	 * @return string
	 */
	public static function content( \DOMNode $node ): string {
		$output = '';

		foreach ( $node->childNodes as $child ) {
			$piece = self::node( $child );
			if ( str_ends_with( $output, '`' ) && str_starts_with( $piece, '`' ) ) {
				$output .= ' ';
			}
			$output .= $piece;
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
	 * javascript:/data: URL slips through as a live link. The probe class is
	 * ASCII-only, so it intentionally runs without the /u modifier: on
	 * invalid UTF-8, a /u regex fails closed to null, which cast to ''
	 * would skip the scheme check entirely and let the raw URL through.
	 * Raw control bytes in the *returned* URL are just as dangerous: a
	 * literal newline in a link destination ends it early and lets the
	 * rest of the text be parsed as a Markdown reference definition, so
	 * every \x00-\x1F/\x7F byte is percent-encoded before the URL is used.
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
		$probe = strtolower( (string) preg_replace( '/[\x00-\x20<>\\\\]+/', '', $probe ) );

		foreach ( self::UNSAFE_SCHEMES as $scheme ) {
			if ( str_starts_with( $probe, $scheme ) ) {
				return '';
			}
		}

		$url = (string) preg_replace_callback(
			'/[\x00-\x1F\x7F]/',
			static fn( array $matches ): string => '%' . strtoupper( bin2hex( $matches[0] ) ),
			$url
		);

		return str_replace( [ ' ', '(', ')', '<', '>' ], [ '%20', '%28', '%29', '%3C', '%3E' ], $url );
	}

	/**
	 * Escape characters that could otherwise start Markdown link/image
	 * syntax, be read as raw HTML (an autolink or a tag), or fuse with an
	 * adjacent code span's fence, by a downstream CommonMark renderer. A
	 * backtick left next to a real code span's fence can join it, so the
	 * span never opens/closes where intended and its raw content (e.g. a
	 * decoded `<img onerror>` payload) is read as live HTML instead. Code
	 * spans and fenced code blocks bypass this (they render their
	 * textContent raw) and must stay unescaped.
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	public static function escape( string $text ): string {
		return (string) preg_replace_callback(
			'/[\x5C\x5B\x5D<>`]/',
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
	 * Inline code span, safe for embedded backticks: the fence is one
	 * backtick longer than the longest run inside the text, so an internal
	 * run (e.g. a double backtick) can never close the span early. When the
	 * content starts or ends with a backtick, a space is added on BOTH
	 * sides — CommonMark only strips that padding when it's present on
	 * both sides, so padding just the affected side would leave a stray
	 * space in the rendered output.
	 *
	 * @param string $text Code text.
	 * @return string
	 */
	private static function code( string $text ): string {
		$text = (string) preg_replace( '/\s+/u', ' ', $text );
		if ( '' === trim( $text ) ) {
			return '';
		}

		$fence = str_repeat( '`', self::longest_backtick_run( $text ) + 1 );
		$pad   = ( str_starts_with( $text, '`' ) || str_ends_with( $text, '`' ) ) ? ' ' : '';

		return $fence . $pad . $text . $pad . $fence;
	}

	/**
	 * Length of the longest run of consecutive backticks in a string, used
	 * to size a fence that can't be closed early by a shorter run inside
	 * the text. Shared with BlockRenderer's fenced code blocks.
	 *
	 * @param string $text Text.
	 * @return int
	 */
	public static function longest_backtick_run( string $text ): int {
		preg_match_all( '/`+/', $text, $runs );

		return [] === $runs[0] ? 0 : max( array_map( 'strlen', $runs[0] ) );
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
