<?php
/**
 * YAML frontmatter builder.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

/**
 * Builds a YAML frontmatter block with safely quoted scalars.
 */
final class Frontmatter {

	/**
	 * Build the block.
	 *
	 * @param array<string, mixed> $data Key-value pairs.
	 * @return string
	 */
	public static function build( array $data ): string {
		$lines = [ '---' ];

		foreach ( $data as $key => $value ) {
			$lines[] = $key . ': ' . self::value( $value );
		}

		$lines[] = '---';

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * YAML escapes for a double-quoted scalar. `<` `>` `[` `]` and backtick
	 * use \u escapes (the parsed YAML value is unchanged): a CommonMark
	 * renderer without a frontmatter extension reads the block as a
	 * thematic break plus a setext heading and parses the values as inline
	 * Markdown, where they would otherwise become raw HTML, links or code.
	 */
	private const ESCAPES = [
		'\\' => '\\\\',
		'"'  => '\\"',
		'<'  => '\\u003C',
		'>'  => '\\u003E',
		'['  => '\\u005B',
		']'  => '\\u005D',
		'`'  => '\\u0060',
	];

	/**
	 * YAML double-quoted scalar.
	 *
	 * @param string $value Raw value (may contain HTML entities).
	 * @return string
	 */
	public static function quote( string $value ): string {
		$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = strtr( $value, self::ESCAPES );
		$value = (string) preg_replace( '/[\x00-\x1F\x7F]+/u', ' ', $value );

		return '"' . trim( $value ) . '"';
	}

	/**
	 * Render one value.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function value( mixed $value ): string {
		if ( is_array( $value ) ) {
			return '[' . implode( ', ', array_map( static fn( $item ): string => self::quote( (string) $item ), $value ) ) . ']';
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_int( $value ) || is_float( $value ) ) {
			return (string) $value;
		}

		if ( null === $value ) {
			return 'null';
		}

		return self::quote( (string) $value );
	}
}
