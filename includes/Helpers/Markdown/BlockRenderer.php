<?php
/**
 * Block-level Markdown rendering.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Helpers\Markdown;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API properties (childNodes, nodeName, textContent) are camelCase.

/**
 * Renders block elements; inline runs between blocks become paragraphs.
 */
final class BlockRenderer {

	/**
	 * Elements rendered as their children's blocks.
	 */
	private const CONTAINERS = [ 'div', 'section', 'article', 'main', 'header', 'footer', 'figure', 'details', 'dl', 'dd', 'center', 'body' ];

	/**
	 * Other block elements with their own rendering.
	 */
	private const BLOCKS = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'pre', 'blockquote', 'ul', 'ol', 'table', 'hr', 'figcaption', 'summary', 'dt', 'iframe' ];

	/**
	 * Render a node's children as blocks separated by blank lines.
	 *
	 * @param \DOMNode $parent Parent node.
	 * @return string
	 */
	public static function children( \DOMNode $parent ): string {
		$blocks = [];
		$inline = '';

		foreach ( $parent->childNodes as $node ) {
			if ( $node instanceof \DOMElement && self::is_block( $node ) ) {
				$blocks[] = self::finish_inline( $inline );
				$blocks[] = self::block( $node );
				$inline   = '';
				continue;
			}
			$inline = InlineRenderer::append( $inline, InlineRenderer::node( $node ) );
		}

		$blocks[] = self::finish_inline( $inline );

		return implode( "\n\n", array_filter( $blocks, static fn( string $block ): bool => '' !== $block ) );
	}

	/**
	 * Whether an element starts a new block.
	 *
	 * @param \DOMElement $node Element.
	 * @return bool
	 */
	public static function is_block( \DOMElement $node ): bool {
		return in_array( $node->nodeName, self::CONTAINERS, true ) || in_array( $node->nodeName, self::BLOCKS, true );
	}

	/**
	 * Render one block element.
	 *
	 * @param \DOMElement $node Element.
	 * @return string
	 */
	private static function block( \DOMElement $node ): string {
		$name = $node->nodeName;

		if ( 1 === preg_match( '/^h([1-6])$/', $name, $level ) ) {
			$text = self::finish_inline( InlineRenderer::content( $node ) );
			return '' === $text ? '' : str_repeat( '#', (int) $level[1] ) . ' ' . $text;
		}

		return match ( $name ) {
			'p'               => self::finish_inline( InlineRenderer::content( $node ) ),
			'pre'             => self::code_block( $node ),
			'blockquote'      => self::blockquote( $node ),
			'ul', 'ol'        => ListRenderer::render( $node ),
			'table'           => TableRenderer::render( $node ),
			'hr'              => '---',
			'figcaption'      => self::wrap( '*', InlineRenderer::content( $node ) ),
			'summary', 'dt'   => self::wrap( '**', InlineRenderer::content( $node ) ),
			'iframe'          => self::embed( $node ),
			default           => self::children( $node ),
		};
	}

	/**
	 * Trim an inline run and drop trailing hard breaks.
	 *
	 * @param string $inline Inline Markdown.
	 * @return string
	 */
	private static function finish_inline( string $inline ): string {
		return InlineRenderer::trim_inline( $inline );
	}

	/**
	 * Wrap text in a marker unless empty.
	 *
	 * @param string $marker Marker.
	 * @param string $text   Text.
	 * @return string
	 */
	private static function wrap( string $marker, string $text ): string {
		$text = self::finish_inline( $text );
		return '' === $text ? '' : $marker . $text . $marker;
	}

	/**
	 * Fenced code block with language and a collision-free fence.
	 *
	 * @param \DOMElement $pre Pre element.
	 * @return string
	 */
	private static function code_block( \DOMElement $pre ): string {
		$code  = rtrim( $pre->textContent, "\n" );
		$fence = str_repeat( '`', max( 3, InlineRenderer::longest_backtick_run( $code ) + 1 ) );

		return $fence . self::language( $pre ) . "\n" . $code . "\n" . $fence;
	}

	/**
	 * Language from a language-* / lang-* class on pre or its code child.
	 *
	 * @param \DOMElement $pre Pre element.
	 * @return string
	 */
	private static function language( \DOMElement $pre ): string {
		$classes = $pre->getAttribute( 'class' );
		$code    = $pre->getElementsByTagName( 'code' )->item( 0 );
		if ( $code instanceof \DOMElement ) {
			$classes .= ' ' . $code->getAttribute( 'class' );
		}

		return 1 === preg_match( '/\b(?:language|lang)-([a-z0-9_+-]+)/i', $classes, $match ) ? strtolower( $match[1] ) : '';
	}

	/**
	 * Blockquote with nested blocks.
	 *
	 * @param \DOMElement $node Blockquote.
	 * @return string
	 */
	private static function blockquote( \DOMElement $node ): string {
		$inner = self::children( $node );
		if ( '' === $inner ) {
			return '';
		}

		$lines = array_map( static fn( string $line ): string => '' === $line ? '>' : '> ' . $line, explode( "\n", $inner ) );

		return implode( "\n", $lines );
	}

	/**
	 * Embedded content as a link.
	 *
	 * @param \DOMElement $node Iframe.
	 * @return string
	 */
	private static function embed( \DOMElement $node ): string {
		$src = InlineRenderer::url( $node->getAttribute( 'src' ) );
		if ( '' === $src ) {
			return '';
		}

		$title = InlineRenderer::escape( trim( $node->getAttribute( 'title' ) ) );

		return '[' . ( '' === $title ? 'Embedded content' : $title ) . '](' . $src . ')';
	}
}
