<?php
/**
 * LLMS.txt document formatter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\LlmsTxt;

/**
 * Formats LLMS.txt per llmstxt.org: H1, optional blockquote summary,
 * optional free text, then H2 sections of "- [name](url): notes" lists.
 */
final class Document {

	/**
	 * Heading of the secondary-information section, always rendered last.
	 */
	public const OPTIONAL = 'Optional';

	/**
	 * Render the document.
	 *
	 * @param array{title: string, summary?: string, intro?: string, sections?: array<string, array<int, array{title: string, url: string, description?: string}>>} $doc Document parts.
	 * @return string
	 */
	public static function render( array $doc ): string {
		$lines = [ '# ' . self::text( $doc['title'] ), '' ];

		$summary = self::text( $doc['summary'] ?? '' );
		if ( '' !== $summary ) {
			array_push( $lines, '> ' . $summary, '' );
		}

		$intro = trim( $doc['intro'] ?? '' );
		if ( '' !== $intro ) {
			array_push( $lines, $intro, '' );
		}

		foreach ( self::ordered( $doc['sections'] ?? [] ) as $heading => $links ) {
			if ( [] === $links ) {
				continue;
			}
			array_push( $lines, '## ' . self::text( (string) $heading ), '' );
			foreach ( $links as $link ) {
				$lines[] = self::link_line( $link );
			}
			$lines[] = '';
		}

		return rtrim( implode( "\n", $lines ) ) . "\n";
	}

	/**
	 * Plain one-line text: tags stripped, entities decoded, whitespace collapsed.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function text( string $value ): string {
		$value = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
	}

	/**
	 * One list item.
	 *
	 * @param array{title: string, url: string, description?: string} $link Link.
	 * @return string
	 */
	private static function link_line( array $link ): string {
		$line        = '- [' . addcslashes( self::text( $link['title'] ), '[]' ) . '](' . self::url( $link['url'] ) . ')';
		$description = self::text( $link['description'] ?? '' );

		return '' === $description ? $line : $line . ': ' . $description;
	}

	/**
	 * Encode characters that would end a Markdown link destination.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private static function url( string $url ): string {
		return str_replace( [ ' ', '(', ')' ], [ '%20', '%28', '%29' ], trim( $url ) );
	}

	/**
	 * Move the Optional section to the end.
	 *
	 * @param array<string, array<int, array{title: string, url: string, description?: string}>> $sections Sections.
	 * @return array<string, array<int, array{title: string, url: string, description?: string}>>
	 */
	private static function ordered( array $sections ): array {
		if ( isset( $sections[ self::OPTIONAL ] ) ) {
			$optional = $sections[ self::OPTIONAL ];
			unset( $sections[ self::OPTIONAL ] );
			$sections[ self::OPTIONAL ] = $optional;
		}

		return $sections;
	}
}
