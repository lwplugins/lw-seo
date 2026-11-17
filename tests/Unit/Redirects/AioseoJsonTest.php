<?php
/**
 * Tests for the All in One SEO redirects JSON parser.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Redirects;

use LightweightPlugins\SEO\Redirects\AioseoJson;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\SEO\Redirects\AioseoJson
 */
final class AioseoJsonTest extends TestCase {

	/**
	 * A row exactly as the export writes it (issue #29).
	 */
	private const SAMPLE = '{"id":"1","source_url":"/example/","target_url":"https://example/","type":"301","query_param":"exact","custom_rules":null,"group":"manual","regex":"0","ignore_slash":"1","ignore_case":"1","enabled":"1","comment":null,"created":"2026-03-29 14:16:29","updated":"2026-03-29 14:16:29"}';

	public function test_maps_an_export_row_to_a_redirect(): void {
		$parsed = AioseoJson::parse( '[' . self::SAMPLE . ']' );

		$this->assertSame(
			[
				'rows'     => [
					1 => [ 'source' => '/example/', 'destination' => 'https://example/', 'type' => 301, 'regex' => false ],
				],
				'disabled' => 0,
			],
			$parsed
		);
	}

	public function test_reads_type_and_regex_flag(): void {
		$parsed = AioseoJson::parse( '[{"source_url":"^/old/(.*)$","target_url":"/new/$1","type":"302","regex":"1","enabled":"1"},{"source_url":"/gone","target_url":"","type":410}]' );

		$this->assertSame(
			[
				1 => [ 'source' => '^/old/(.*)$', 'destination' => '/new/$1', 'type' => 302, 'regex' => true ],
				2 => [ 'source' => '/gone', 'destination' => '', 'type' => 410, 'regex' => false ],
			],
			$parsed['rows'] ?? null
		);
	}

	public function test_disabled_rows_are_counted_not_returned(): void {
		$parsed = AioseoJson::parse( '[{"source_url":"/off","target_url":"/x","enabled":"0"},{"source_url":"/on","target_url":"/y","enabled":"1"}]' );

		$this->assertSame( [ 2 ], array_keys( $parsed['rows'] ?? [] ) );
		$this->assertSame( 1, $parsed['disabled'] ?? null );
	}

	public function test_a_non_object_item_becomes_an_empty_row_for_the_validator(): void {
		$parsed = AioseoJson::parse( '["nonsense"]' );

		$this->assertSame( [ 1 => [ 'source' => '', 'destination' => '', 'type' => 301, 'regex' => false ] ], $parsed['rows'] ?? null );
	}

	/**
	 * @dataProvider provide_malformed
	 */
	public function test_malformed_json_returns_null( string $json ): void {
		$this->assertNull( AioseoJson::parse( $json ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function provide_malformed(): array {
		return [
			'syntax error'    => [ '[{"source_url":"/a",}' ],
			'object, no list' => [ '{"source_url":"/a"}' ],
			'scalar'          => [ '"text"' ],
			'empty'           => [ '' ],
		];
	}

	public function test_a_utf8_bom_is_ignored(): void {
		$parsed = AioseoJson::parse( "\xEF\xBB\xBF[" . self::SAMPLE . ']' );

		$this->assertCount( 1, $parsed['rows'] ?? [] );
	}
}
