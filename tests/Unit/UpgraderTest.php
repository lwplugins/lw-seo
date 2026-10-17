<?php
/**
 * Upgrader unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\RewriteFlusher;
use LightweightPlugins\SEO\Upgrader;

final class UpgraderTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'LW_SEO_VERSION' ) ) {
			define( 'LW_SEO_VERSION', '1.6.0' );
		}
		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}

		// The 404 log table install and prune schedule run on every version change.
		$GLOBALS['wpdb'] = new \wpdb();
		Functions\when( 'dbDelta' )->justReturn( [] );
		Functions\when( 'wp_next_scheduled' )->justReturn( 1234 );
	}

	/**
	 * @param string              $stored Stored plugin version.
	 * @param array<string,mixed> $saved  Saved options.
	 */
	private function stub_stored( string $stored, array $saved = [] ): void {
		Functions\when( 'get_option' )->alias(
			static fn( string $name, $fallback = false ) => match ( $name ) {
				Upgrader::VERSION_OPTION => $stored,
				Options::OPTION_NAME     => $saved,
				default                  => $fallback,
			}
		);
	}

	public function test_does_nothing_when_version_is_current(): void {
		$this->stub_stored( LW_SEO_VERSION );
		Functions\expect( 'update_option' )->never();
		Functions\expect( 'set_transient' )->never();

		( new Upgrader() )->maybe_upgrade();

		$this->addToAssertionCount( 1 );
	}

	public function test_records_version_and_schedules_rewrite_flush_on_change(): void {
		$this->stub_stored( '1.5.1' );
		Functions\when( 'delete_transient' )->justReturn( true );
		$names = [];
		Functions\when( 'update_option' )->alias(
			static function ( string $name, $value ) use ( &$names ): bool {
				$names[ $name ] = $value;
				return true;
			}
		);
		Functions\expect( 'set_transient' )->once()->with( RewriteFlusher::FLAG, 1, 3600 );

		( new Upgrader() )->maybe_upgrade();

		$this->assertSame(
			[
				Upgrader::VERSION_OPTION   => LW_SEO_VERSION,
				'lw_seo_404_table_version' => '1',
			],
			$names
		);
	}

	public function test_installs_the_404_table_and_schedules_the_prune_on_change(): void {
		$this->stub_stored( '1.8.8' );
		$writes = [];
		Functions\when( 'update_option' )->alias(
			static function ( string $name, $value ) use ( &$writes ): bool {
				$writes[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'wp_next_scheduled' )->justReturn( false );
		$tables = 0;
		Functions\when( 'dbDelta' )->alias(
			static function () use ( &$tables ): array {
				++$tables;
				return [];
			}
		);
		Functions\expect( 'wp_schedule_event' )->once()->with( \Mockery::type( 'int' ), 'daily', 'lw_seo_404_prune' );

		( new Upgrader() )->maybe_upgrade();

		$this->assertSame( 1, $tables );
		$this->assertSame( '1', $writes['lw_seo_404_table_version'] );
	}

	public function test_does_not_touch_the_404_table_when_version_is_current(): void {
		$this->stub_stored( LW_SEO_VERSION );
		Functions\expect( 'wp_schedule_event' )->never();

		( new Upgrader() )->maybe_upgrade();

		$this->addToAssertionCount( 1 );
	}

	public function test_drops_the_cached_llms_txt_documents_on_version_change(): void {
		$this->stub_stored( '1.5.1' );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'set_transient' )->justReturn( true );
		$deleted = [];
		Functions\when( 'delete_transient' )->alias(
			static function ( string $key ) use ( &$deleted ): bool {
				$deleted[] = $key;
				return true;
			}
		);

		( new Upgrader() )->maybe_upgrade();

		$this->assertSame( [ 'lw_seo_llms_index', 'lw_seo_llms_full' ], $deleted );
	}

	public function test_migrate_options_leaves_current_data_untouched(): void {
		$options = [ 'separator' => '|' ];

		$this->assertSame( $options, Upgrader::migrate_options( $options, LW_SEO_VERSION ) );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>, 1: string, 2: array<string, mixed>}>
	 */
	public static function migration_provider(): array {
		return [
			'claude-web block becomes claudebot' => [ [ 'block_claude_web' => true, 'block_cohere_ai' => true ], '1.5.1', [ 'block_claudebot' => true ] ],
			'unticked claude-web is just removed' => [ [ 'block_claude_web' => false ], '1.5.1', [] ],
			'already migrated data untouched'     => [ [ 'block_claudebot' => true ], '1.6.0', [ 'block_claudebot' => true ] ],
			'boolean signals become yes/no'       => [ [ 'content_signals_ai_train' => false, 'content_signals_search' => true ], '1.5.1', [ 'content_signals_ai_train' => 'no', 'content_signals_search' => 'yes' ] ],
		];
	}

	/**
	 * @dataProvider migration_provider
	 *
	 * @param array<string, mixed> $saved    Saved options.
	 * @param string               $from     Stored version.
	 * @param array<string, mixed> $expected Migrated options.
	 */
	public function test_migrate_options( array $saved, string $from, array $expected ): void {
		$this->assertSame( $expected, Upgrader::migrate_options( $saved, $from ) );
	}
}
