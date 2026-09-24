<?php
/**
 * SettingsStore unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Admin\SettingsStore;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class SettingsStoreTest extends MonkeyTestCase {

	private const DEFAULTS = [
		'separator'               => '-',
		'sitemap_enabled'         => true,
		'noindex_date'            => true,
		'noindex_post'            => false,
		'default_og_image'        => '',
		'llms_txt_max_items'      => 100,
		'sitemap_post_types'      => [],
		'local_hours_monday_open' => '',
	];

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ): string => is_string( $v ) ? trim( strip_tags( $v ) ) : '' );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( $v ): string => trim( strip_tags( (string) $v ) ) );
		Functions\when( 'esc_url_raw' )->alias( static fn( $v ): string => (string) $v );
		Functions\when( 'sanitize_key' )->alias( static fn( $v ): string => (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ) );
		Functions\when( 'absint' )->alias( static fn( $v ): int => abs( (int) $v ) );
	}

	public function test_merge_keeps_stored_values_for_absent_keys(): void {
		$stored = [
			'sitemap_enabled'  => false,
			'noindex_post'     => true,
			'default_og_image' => 'https://x.test/og.jpg',
		];

		$result = SettingsStore::merge( [ 'separator' => '|' ], $stored, self::DEFAULTS );

		$this->assertSame(
			[
				'separator'               => '|',
				'sitemap_enabled'         => false,
				'noindex_date'            => true,
				'noindex_post'            => true,
				'default_og_image'        => 'https://x.test/og.jpg',
				'llms_txt_max_items'      => 100,
				'sitemap_post_types'      => [],
				'local_hours_monday_open' => '',
			],
			$result
		);
	}

	/**
	 * WordPress' sanitize_text_field() drops percent-encoded octets; the
	 * stub below does the same so the test catches a damaged %%variable%%.
	 */
	private function stub_octet_stripping(): void {
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ): string => trim( (string) preg_replace( '/%[a-f0-9]{2}/i', '', strip_tags( (string) $v ) ) ) );
	}

	public function test_merge_keeps_title_variables_that_look_like_octets(): void {
		$this->stub_octet_stripping();

		$result = SettingsStore::merge( [ 'separator' => '%%date%% %%category%% %%sep%%' ], [], self::DEFAULTS );

		$this->assertSame( '%%date%% %%category%% %%sep%%', $result['separator'] );
	}

	public function test_merge_does_not_reprocess_stored_values_that_were_not_sent(): void {
		$this->stub_octet_stripping();

		$result = SettingsStore::merge( [ 'noindex_post' => true ], [ 'separator' => 'a%dab' ], self::DEFAULTS );

		$this->assertSame( 'a%dab', $result['separator'] );
	}

	public function test_merge_applies_submitted_false(): void {
		$result = SettingsStore::merge( [ 'noindex_date' => false ], [ 'noindex_date' => true ], self::DEFAULTS );

		$this->assertFalse( $result['noindex_date'] );
	}

	public function test_merge_drops_unknown_keys(): void {
		$result = SettingsStore::merge( [ 'evil' => 'x', '_locale' => 'user' ], [ 'legacy' => 'y' ], self::DEFAULTS );

		$this->assertSame( array_keys( self::DEFAULTS ), array_keys( $result ) );
	}

	public function test_merge_sanitizes_submitted_values(): void {
		$result = SettingsStore::merge( [ 'separator' => '<b>|</b>', 'llms_txt_max_items' => '-20' ], [], self::DEFAULTS );

		$this->assertSame( [ '|', 20 ], [ $result['separator'], $result['llms_txt_max_items'] ] );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function opening_hours_provider(): array {
		return [
			'valid time kept'       => [ '08:15', '08:15' ],
			'seconds dropped'       => [ '08:15:00', '08:15' ],
			'invalid time cleared'  => [ '25:00', '' ],
			'markup cleared'        => [ '<script>', '' ],
		];
	}

	/**
	 * @dataProvider opening_hours_provider
	 *
	 * @param mixed  $submitted Submitted time.
	 * @param string $expected  Stored time.
	 */
	public function test_merge_sanitizes_opening_hours( $submitted, string $expected ): void {
		$result = SettingsStore::merge( [ 'local_hours_monday_open' => $submitted ], [], self::DEFAULTS );

		$this->assertSame( $expected, $result['local_hours_monday_open'] );
	}

	public function test_merge_treats_a_non_array_stored_value_as_empty(): void {
		$result = SettingsStore::merge( [], false, self::DEFAULTS );

		$this->assertSame( '-', $result['separator'] );
	}

	public function test_typed_casts_values_like_their_defaults(): void {
		$values = [
			'separator'          => 5,
			'sitemap_enabled'    => '1',
			'llms_txt_max_items' => '250',
			'sitemap_post_types' => [ 'book' => true ],
		];

		$result = SettingsStore::typed( $values, self::DEFAULTS );

		$this->assertSame( '5', $result['separator'] );
		$this->assertTrue( $result['sitemap_enabled'] );
		$this->assertSame( 250, $result['llms_txt_max_items'] );
		$this->assertEquals( (object) [ 'book' => true ], $result['sitemap_post_types'] );
	}

	public function test_typed_encodes_an_empty_map_as_json_object(): void {
		$result = SettingsStore::typed( [ 'sitemap_post_types' => [] ], [ 'sitemap_post_types' => [] ] );

		$this->assertSame( '{"sitemap_post_types":{}}', json_encode( $result ) );
	}
}
