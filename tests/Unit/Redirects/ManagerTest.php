<?php
/**
 * Redirect Manager unit tests (CSV import, ids on add).
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Redirects;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Redirects\Manager;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class ManagerTest extends MonkeyTestCase {

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
		Functions\when( 'current_time' )->justReturn( '2026-09-24 10:00:00' );
		Functions\when( 'wp_generate_uuid4' )->justReturn( 'new-uuid' );
	}

	public function test_add_stores_a_stable_id(): void {
		Manager::add( '/old', '/new' );

		$this->assertSame( 'new-uuid', $this->options[ Manager::OPTION_NAME ][0]['id'] ?? null );
	}

	public function test_import_counts_valid_rows_and_skips_the_header(): void {
		$result = Manager::import_csv( "source,destination,type,regex\n/a,/b,301,false\n/gone,,410,false\n" );

		$this->assertSame( [ 2, 0, [] ], [ $result['imported'], $result['skipped'], $result['errors'] ] );
	}

	public function test_import_reports_invalid_rows_by_line(): void {
		$csv = "/only-one-column\n/a,,302\n/re/(,/b,301,true\n";

		$result = Manager::import_csv( $csv );

		$this->assertSame(
			[
				'Line 1: Invalid format.',
				'Line 2: Destination URL is required for this redirect type.',
				'Line 3: Invalid regex pattern.',
			],
			$result['errors']
		);
	}

	public function test_add_keeps_a_regex_source_as_written(): void {
		Manager::add( '^/old/(\d+)$', '/new/$1', 301, true );

		$this->assertSame( '^/old/(\d+)$', $this->options[ Manager::OPTION_NAME ][0]['source'] ?? null );
	}

	public function test_update_keeps_a_regex_source_as_written(): void {
		Manager::add( '/old', '/new' );

		Manager::update( 0, '^/old/(\d+)$', '/new/$1', 301, true );

		$this->assertSame( '^/old/(\d+)$', $this->options[ Manager::OPTION_NAME ][0]['source'] ?? null );
	}

	public function test_find_match_matches_an_anchored_regex(): void {
		Manager::add( '^/old/(\d+)$', '/new/$1', 301, true );

		$this->assertNotNull( Manager::find_match( '/old/5' ) );
	}

	public function test_find_match_repairs_a_stored_slash_before_the_anchor(): void {
		$this->options[ Manager::OPTION_NAME ] = [
			[
				'source'      => '/^/old/(\d+)$',
				'destination' => '/new/$1',
				'type'        => 301,
				'regex'       => true,
			],
		];

		$this->assertNotNull( Manager::find_match( '/old/5' ) );
	}

	public function test_regex_pattern_fills_the_destination_of_a_repaired_source(): void {
		$result = preg_replace( Manager::regex_pattern( '/^/old/(\d+)$' ), '/new/$1', '/old/5' );

		$this->assertSame( '/new/5', $result );
	}
}
