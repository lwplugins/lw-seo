<?php
/**
 * OptionValueParser unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\CLI;

use LightweightPlugins\SEO\CLI\OptionValueParser;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class OptionValueParserTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function valid_bool_provider(): array {
		return [
			'true'       => [ 'true', true ],
			'1'          => [ '1', true ],
			'on'         => [ 'on', true ],
			'yes'        => [ 'yes', true ],
			'uppercase'  => [ 'TRUE', true ],
			'padded'     => [ '  yes  ', true ],
			'false'      => [ 'false', false ],
			'0'          => [ '0', false ],
			'off'        => [ 'off', false ],
			'no'         => [ 'no', false ],
		];
	}

	/**
	 * @dataProvider valid_bool_provider
	 */
	public function test_parse_bool_accepts_recognised_tokens( string $raw, bool $expected ): void {
		$this->assertSame( $expected, OptionValueParser::parse_bool( $raw ) );
	}

	public function test_parse_bool_rejects_unrecognised_token(): void {
		$this->expectException( \InvalidArgumentException::class );

		OptionValueParser::parse_bool( 'maybe' );
	}

	public function test_parse_int_converts_numeric_string(): void {
		$this->assertSame( 250, OptionValueParser::parse_int( '250' ) );
	}

	public function test_parse_int_accepts_negative_numeric_string(): void {
		$this->assertSame( -5, OptionValueParser::parse_int( '-5' ) );
	}

	public function test_parse_int_rejects_non_numeric_string(): void {
		$this->expectException( \InvalidArgumentException::class );

		OptionValueParser::parse_int( 'lots' );
	}

	public function test_parse_map_decodes_json_object(): void {
		$result = OptionValueParser::parse_map( '{"case_study":false,"event":true}' );

		$this->assertSame( [ 'case_study' => false, 'event' => true ], $result );
	}

	public function test_parse_map_accepts_empty_object(): void {
		$this->assertSame( [], OptionValueParser::parse_map( '{}' ) );
	}

	public function test_parse_map_rejects_invalid_json(): void {
		$this->expectException( \InvalidArgumentException::class );

		OptionValueParser::parse_map( '{not json' );
	}

	public function test_parse_map_rejects_json_array(): void {
		$this->expectException( \InvalidArgumentException::class );

		OptionValueParser::parse_map( '[true,false]' );
	}

	public function test_parse_map_rejects_json_scalar(): void {
		$this->expectException( \InvalidArgumentException::class );

		OptionValueParser::parse_map( '"just a string"' );
	}

	public function test_parse_dispatches_bool_by_default_type(): void {
		$this->assertTrue( OptionValueParser::parse( 'sitemap_enabled', 'yes', true, [] ) );
	}

	public function test_parse_dispatches_int_by_default_type(): void {
		$this->assertSame( 250, OptionValueParser::parse( 'llms_txt_max_items', '250', 100, [] ) );
	}

	public function test_parse_dispatches_map_by_default_type(): void {
		$result = OptionValueParser::parse( 'sitemap_post_types', '{"case_study":true}', [], [] );

		$this->assertSame( [ 'case_study' => true ], $result );
	}

	public function test_parse_returns_string_for_plain_text_default(): void {
		$this->assertSame( 'My Title', OptionValueParser::parse( 'title_home', 'My Title', '', [] ) );
	}

	public function test_parse_accepts_allowed_choice_value(): void {
		$choices = [ 'content_signals_ai_train' => [ '', 'yes', 'no' ] ];

		$this->assertSame( 'no', OptionValueParser::parse( 'content_signals_ai_train', 'no', '', $choices ) );
	}

	public function test_parse_rejects_disallowed_choice_value(): void {
		$choices = [ 'content_signals_ai_train' => [ '', 'yes', 'no' ] ];

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( "Allowed: '', yes, no" );

		OptionValueParser::parse( 'content_signals_ai_train', 'maybe', '', $choices );
	}

	public function test_split_dot_path_splits_base_and_entry(): void {
		$this->assertSame( [ 'sitemap_post_types', 'case_study' ], OptionValueParser::split_dot_path( 'sitemap_post_types.case_study' ) );
	}

	public function test_split_dot_path_splits_only_on_first_dot(): void {
		$this->assertSame( [ 'sitemap_post_types', 'case.study' ], OptionValueParser::split_dot_path( 'sitemap_post_types.case.study' ) );
	}

	public function test_split_dot_path_returns_null_without_a_dot(): void {
		$this->assertNull( OptionValueParser::split_dot_path( 'sitemap_enabled' ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function malformed_dot_path_provider(): array {
		return [
			'leading dot'  => [ '.case_study' ],
			'trailing dot' => [ 'sitemap_post_types.' ],
		];
	}

	/**
	 * @dataProvider malformed_dot_path_provider
	 */
	public function test_split_dot_path_returns_null_for_empty_segment( string $key ): void {
		$this->assertNull( OptionValueParser::split_dot_path( $key ) );
	}

	public function test_map_entry_default_true_for_sitemap_post_types(): void {
		$this->assertTrue( OptionValueParser::map_entry_default( 'sitemap_post_types' ) );
	}

	public function test_map_entry_default_true_for_llms_txt_post_types(): void {
		$this->assertTrue( OptionValueParser::map_entry_default( 'llms_txt_post_types' ) );
	}

	public function test_map_entry_default_false_for_sitemap_taxonomies(): void {
		$this->assertFalse( OptionValueParser::map_entry_default( 'sitemap_taxonomies' ) );
	}

	public function test_map_entry_default_false_for_unknown_key(): void {
		$this->assertFalse( OptionValueParser::map_entry_default( 'not_a_map_option' ) );
	}
}
