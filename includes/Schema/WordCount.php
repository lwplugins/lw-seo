<?php
/**
 * Unicode-aware word counter for the schema wordCount property.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Schema;

/**
 * Counts words in any script.
 *
 * PHP's str_word_count() only knows ASCII letters: accented Latin words are
 * split apart and a whole Chinese paragraph counts as a handful of "words".
 * Space-separated scripts are counted per letter/number run; Chinese,
 * Japanese and Korean have no reliable word boundaries, so (by the common
 * convention) each ideograph, kana or Hangul syllable counts as one word.
 */
final class WordCount {

	/**
	 * One CJK ideograph, Hiragana, Katakana or Hangul syllable. The letter
	 * lookahead keeps out CJK punctuation (。、「」), which PCRE also files
	 * under these scripts.
	 */
	private const CJK_CHAR = '/(?=\p{L})[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u';

	/**
	 * A word in a space-separated script: letters, numbers, combining marks,
	 * and in-word apostrophes and hyphens.
	 */
	private const WORD = '/[\p{L}\p{N}][\p{L}\p{N}\p{M}\'’-]*/u';

	/**
	 * Count the words of post content (shortcodes and HTML tags removed).
	 *
	 * @param string $content Raw post content.
	 * @return int
	 */
	public static function from_content( string $content ): int {
		return self::count( wp_strip_all_tags( strip_shortcodes( $content ) ) );
	}

	/**
	 * Count the words of plain text.
	 *
	 * @param string $text Plain text.
	 * @return int
	 */
	public static function count( string $text ): int {
		// The Katakana long-vowel mark (U+30FC) lengthens the previous kana.
		$text = str_replace( "\u{30FC}", '', $text );

		$cjk  = (int) preg_match_all( self::CJK_CHAR, $text );
		$rest = preg_replace( self::CJK_CHAR, ' ', $text );

		if ( null === $rest ) {
			// Invalid UTF-8: fall back to whitespace-separated tokens.
			$tokens = preg_split( '/\s+/', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
			return is_array( $tokens ) ? count( $tokens ) : 0;
		}

		return $cjk + (int) preg_match_all( self::WORD, $rest );
	}
}
