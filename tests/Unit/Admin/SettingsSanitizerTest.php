<?php
/**
 * SettingsSanitizer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Admin\SettingsSanitizer;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class SettingsSanitizerTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ): string => is_string( $v ) ? trim( (string) preg_replace( '/\s+/', ' ', strip_tags( $v ) ) ) : '' );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( $v ): string => trim( strip_tags( (string) $v ) ) );
		Functions\when( 'esc_url_raw' )->alias( static fn( $v ): string => (string) $v );
		Functions\when( 'sanitize_key' )->alias( static fn( $v ): string => (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ) );
		Functions\when( 'absint' )->alias( static fn( $v ): int => abs( (int) $v ) );
	}

	/**
	 * @return array<string, array{0: string, 1: mixed, 2: array<string, mixed>, 3: mixed}>
	 */
	public static function value_provider(): array {
		return [
			// Characterization: behaviour of the pre-1.6.0 SettingsPage::sanitize_settings().
			'bool checked'             => [ 'sitemap_enabled', true, [ 'sitemap_enabled' => '1' ], true ],
			'bool missing'             => [ 'sitemap_enabled', true, [], false ],
			'url missing is empty'     => [ 'social_facebook', '', [], '' ],
			'url kept'                 => [ 'default_og_image', '', [ 'default_og_image' => 'https://x.test/a.jpg' ], 'https://x.test/a.jpg' ],
			'text missing is default'  => [ 'separator', '-', [], '-' ],
			'text stripped'            => [ 'title_home', '', [ 'title_home' => '<b>Home</b>' ], 'Home' ],
			// New in 1.6.0.
			'int parsed'               => [ 'llms_txt_max_items', 100, [ 'llms_txt_max_items' => '250' ], 250 ],
			'int missing is default'   => [ 'llms_txt_max_items', 100, [], 100 ],
			'int negative made absint' => [ 'llms_txt_max_items', 100, [ 'llms_txt_max_items' => '-5' ], 5 ],
			'map cleaned'              => [ 'sitemap_post_types', [], [ 'sitemap_post_types' => [ 'case_study' => '1', 'Bad Key!' => '0' ] ], [ 'case_study' => true, 'badkey' => false ] ],
			'map not array'            => [ 'sitemap_post_types', [], [ 'sitemap_post_types' => 'x' ], [] ],
			'choice kept'              => [ 'content_signals_ai_train', '', [ 'content_signals_ai_train' => 'no' ], 'no' ],
			'choice rejected'          => [ 'content_signals_ai_train', '', [ 'content_signals_ai_train' => 'maybe' ], '' ],
			'textarea keeps newlines'  => [ 'llms_txt_intro', '', [ 'llms_txt_intro' => "Line 1\nLine 2" ], "Line 1\nLine 2" ],
			'opening time normalised'  => [ 'local_hours_friday_close', '', [ 'local_hours_friday_close' => '7:30' ], '07:30' ],
			'opening time rejected'    => [ 'local_hours_friday_close', '', [ 'local_hours_friday_close' => '99:99' ], '' ],
			'closed flag is a bool'    => [ 'local_hours_friday_closed', false, [ 'local_hours_friday_closed' => '1' ], true ],
		];
	}

	/**
	 * @dataProvider value_provider
	 *
	 * @param string               $key      Option key.
	 * @param mixed                $default  Option default.
	 * @param array<string, mixed> $input    Submitted input.
	 * @param mixed                $expected Sanitized value.
	 */
	public function test_sanitizes_value_by_default_type( string $key, $default, array $input, $expected ): void {
		$result = SettingsSanitizer::sanitize( $input, [ $key => $default ] );

		$this->assertSame( $expected, $result[ $key ] );
	}

	public function test_drops_keys_without_a_default(): void {
		$result = SettingsSanitizer::sanitize( [ 'unknown' => 'x' ], [ 'separator' => '-' ] );

		$this->assertSame( [ 'separator' => '-' ], $result );
	}
}
