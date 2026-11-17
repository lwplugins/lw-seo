<?php
/**
 * Redirect import tests (CSV and All in One SEO JSON).
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Redirects;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Redirects\Importer;
use LightweightPlugins\SEO\Redirects\Manager;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Redirects\Importer
 */
final class ImporterTest extends MonkeyTestCase {

	/**
	 * In-memory wp_options.
	 *
	 * @var array<string, mixed>
	 */
	private array $options = [];

	protected function setUp(): void {
		parent::setUp();
		$this->options = [];

		Functions\stubTranslationFunctions();
		Functions\when( 'get_option' )->alias( fn( string $name, $fallback = false ) => $this->options[ $name ] ?? $fallback );
		Functions\when( 'update_option' )->alias(
			function ( string $name, $value ): bool {
				$this->options[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'home_url' )->justReturn( 'https://example.test' );
		Functions\when( 'current_time' )->justReturn( '2026-11-17 10:00:00' );
		Functions\when( 'wp_generate_uuid4' )->justReturn( 'new-uuid' );
	}

	/**
	 * @return array<int, array{source: string, destination: string, type: int, regex: bool}>
	 */
	private function stored(): array {
		return array_map(
			static fn( array $r ) => [ 'source' => $r['source'], 'destination' => $r['destination'], 'type' => $r['type'], 'regex' => $r['regex'] ],
			$this->options[ Manager::OPTION_NAME ] ?? []
		);
	}

	/**
	 * The sample row from issue #29 (the source is stored normalized, as from CSV).
	 */
	public function test_imports_an_aioseo_json_export(): void {
		$json = '[{"id":"1","source_url":"/example/","target_url":"https://example/","type":"301","query_param":"exact","custom_rules":null,"group":"manual","regex":"0","ignore_slash":"1","ignore_case":"1","enabled":"1","comment":null,"created":"2026-03-29 14:16:29","updated":"2026-03-29 14:16:29"}]';

		$result = Importer::import( $json, 'redirects.json' );

		$this->assertSame( [ 1, 0, [] ], [ $result['imported'], $result['skipped'], $result['errors'] ] );
		$this->assertSame( [ [ 'source' => '/example', 'destination' => 'https://example/', 'type' => 301, 'regex' => false ] ], $this->stored() );
	}

	public function test_json_is_detected_by_content_without_a_filename(): void {
		$result = Importer::import( "  \n[{\"source_url\":\"/a\",\"target_url\":\"/b\",\"type\":\"302\"}]" );

		$this->assertSame( 1, $result['imported'] );
		$this->assertSame( 302, $this->stored()[0]['type'] );
	}

	public function test_json_rows_go_through_the_validator_and_duplicate_check(): void {
		Manager::add( '/taken', '/x' );

		$json = '[{"source_url":"/taken/","target_url":"/y","type":"301"},'
			. '{"source_url":"/no-dest","target_url":"","type":"302"},'
			. '{"source_url":"/re/(","target_url":"/z","type":"301","regex":"1"},'
			. '{"source_url":"/ok","target_url":"/fine","type":"301"},'
			. '{"source_url":"/ok/","target_url":"/again","type":"301"}]';

		$result = Importer::import( $json );

		$this->assertSame(
			[
				1,
				4,
				[
					'Item 1: A redirect for this source already exists.',
					'Item 2: Destination URL is required for this redirect type.',
					'Item 3: Invalid regex pattern.',
					'Item 5: A redirect for this source already exists.',
				],
			],
			[ $result['imported'], $result['skipped'], $result['errors'] ]
		);
	}

	public function test_disabled_json_rows_are_skipped(): void {
		$result = Importer::import( '[{"source_url":"/off","target_url":"/x","enabled":"0"},{"source_url":"/on","target_url":"/y","enabled":"1"}]' );

		$this->assertSame( [ 1, 1, [] ], [ $result['imported'], $result['skipped'], $result['errors'] ] );
		$this->assertSame( '/on', $this->stored()[0]['source'] );
	}

	public function test_malformed_json_imports_nothing(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The file is not a valid All in One SEO redirects export (JSON list).' );

		try {
			Importer::import( '[{"source_url":"/a","target_url":"/b"},', 'export.json' );
		} finally {
			$this->assertSame( [], $this->stored() );
		}
	}

	public function test_a_json_extension_forces_json_parsing(): void {
		$this->expectException( \InvalidArgumentException::class );

		Importer::import( "source,destination\n/a,/b\n", 'redirects.JSON' );
	}

	public function test_csv_still_imports(): void {
		$result = Importer::import( "source,destination,type,regex\n/a,/b,301,false\n", 'redirects.csv' );

		$this->assertSame( [ 1, 0, [] ], [ $result['imported'], $result['skipped'], $result['errors'] ] );
	}
}
