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
		Functions\expect( 'update_option' )->once()->with( Upgrader::VERSION_OPTION, LW_SEO_VERSION );
		Functions\expect( 'set_transient' )->once()->with( RewriteFlusher::FLAG, 1, 3600 );

		( new Upgrader() )->maybe_upgrade();

		$this->addToAssertionCount( 1 );
	}

	public function test_migrate_options_leaves_current_data_untouched(): void {
		$options = [ 'separator' => '|' ];

		$this->assertSame( $options, Upgrader::migrate_options( $options, LW_SEO_VERSION ) );
	}
}
