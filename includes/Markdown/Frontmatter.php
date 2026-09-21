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
	 * Characters YAML does not allow in a scalar: C0 controls, DEL, C1
	 * controls and the noncharacters U+FFFE/U+FFFF. Each run becomes a
	 * space. A C1 control (other than NEL) or a noncharacter makes PyYAML
	 * and libyaml reject the whole block.
	 */
	private const UNPRINTABLE = '/[\x{0000}-\x{001F}\x{007F}-\x{009F}\x{FFFE}\x{FFFF}]+/u';

	/**
	 * YAML double-quoted scalar.
	 *
	 * @param string $value Raw value (may contain HTML entities).
	 * @return string
	 */
	public static function quote( string $value ): string {
		$value = html_entity_decode( self::scrub_utf8( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = strtr( $value, self::ESCAPES );
		$value = preg_replace( self::UNPRINTABLE, ' ', $value ) ?? $value;

		return '"' . trim( $value ) . '"';
	}

	/**
	 * Replace invalid UTF-8 sequences with U+FFFD. Otherwise the /u regex
	 * fails on them and the value would come out empty. htmlspecialchars()
	 * with ENT_SUBSTITUTE substitutes them, and htmlspecialchars_decode()
	 * exactly undoes its own &amp; &lt; &gt;, so valid text is unchanged.
	 * This is core PHP and needs no mbstring.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function scrub_utf8( string $value ): string {
		return htmlspecialchars_decode( htmlspecialchars( $value, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' ), ENT_NOQUOTES );
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
