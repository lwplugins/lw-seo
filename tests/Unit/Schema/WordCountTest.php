<?php
/**
 * Tests for the Unicode-aware schema word counter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Schema;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Schema\WordCount;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Schema\WordCount
 */
final class WordCountTest extends MonkeyTestCase {

	public function test_english_words(): void {
		$this->assertSame( 9, WordCount::count( "The quick brown fox jumps over the lazy dog." ) );
	}

	public function test_apostrophes_and_hyphens_stay_inside_a_word(): void {
		$this->assertSame( 3, WordCount::count( "Don't over-think it" ) );
		$this->assertSame( 2, WordCount::count( 'It’s well-known' ) );
	}

	public function test_hungarian_accented_words(): void {
		$this->assertSame( 2, WordCount::count( 'Árvíztűrő tükörfúrógép' ) );
		$this->assertSame( 5, WordCount::count( 'Ő egy őszi éjszakán érkezett.' ) );
	}

	public function test_combining_marks_do_not_split_a_word(): void {
		// "Árvíz" with a decomposed Á (A + U+0301 combining acute).
		$this->assertSame( 1, WordCount::count( "A\u{0301}rvi\u{0301}z" ) );
	}

	public function test_chinese_counts_each_ideograph(): void {
		$this->assertSame( 7, WordCount::count( '我喜欢学习中文。' ) );
		$this->assertSame( 4, WordCount::count( '「你好」、世界。' ) );
	}

	public function test_japanese_counts_each_kana_and_kanji(): void {
		// 日本語 (3) + が (1) + 好き (2) + です (2).
		$this->assertSame( 8, WordCount::count( '日本語が好きです' ) );
		// The long-vowel mark (ー) is not a character of its own: コ + ヒ.
		$this->assertSame( 2, WordCount::count( 'コーヒー' ) );
	}

	public function test_korean_counts_each_syllable(): void {
		$this->assertSame( 5, WordCount::count( '안녕하세요' ) );
	}

	public function test_mixed_scripts(): void {
		// 2 Latin words + 2 ideographs + 1 number.
		$this->assertSame( 5, WordCount::count( 'WordPress 插件 version 2' ) );
	}

	public function test_empty_and_punctuation_only(): void {
		$this->assertSame( 0, WordCount::count( '' ) );
		$this->assertSame( 0, WordCount::count( ' — … !? ' ) );
	}

	public function test_from_content_strips_shortcodes_and_tags(): void {
		Functions\when( 'strip_shortcodes' )->alias( static fn( string $c ) => (string) preg_replace( '/\[[^\]]*\]/', '', $c ) );
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );

		$this->assertSame( 3, WordCount::from_content( '<p>Szia <strong>kedves</strong> olvasó</p>[gallery ids="1,2"]' ) );
	}
}
