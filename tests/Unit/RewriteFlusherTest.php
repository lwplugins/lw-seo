<?php
/**
 * RewriteFlusher unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\RewriteFlusher;

final class RewriteFlusherTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}
		Functions\when( 'add_action' )->justReturn( true );
	}

	/**
	 * @return array<string, array{0: mixed, 1: mixed, 2: bool}>
	 */
	public static function option_change_provider(): array {
		return [
			'sitemap turned on'       => [ [ 'sitemap_enabled' => false ], [ 'sitemap_enabled' => true ], true ],
			'robots turned off'       => [ [ 'robots_txt_enabled' => true ], [ 'robots_txt_enabled' => false ], true ],
			'llms turned on'          => [ [], [ 'llms_txt_enabled' => true ], true ],
			'llms unchanged (absent)' => [ [], [], false ],
			'truthiness unchanged'    => [ [ 'sitemap_enabled' => 1 ], [ 'sitemap_enabled' => true ], false ],
			'unrelated key changed'   => [ [ 'separator' => '-' ], [ 'separator' => '|' ], false ],
			'old value not an array'  => [ false, [ 'sitemap_enabled' => true ], true ],
			'new value not an array'  => [ [ 'sitemap_enabled' => true ], false, true ],
		];
	}

	/**
	 * @dataProvider option_change_provider
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 * @param bool  $expected  Whether a rewrite-affecting toggle changed.
	 */
	public function test_toggled_detects_rewrite_affecting_changes( $old_value, $new_value, bool $expected ): void {
		$this->assertSame( $expected, RewriteFlusher::toggled( $old_value, $new_value ) );
	}

	public function test_schedule_sets_flag_only_when_toggled(): void {
		$this->expectNotToPerformAssertions(); // Brain Monkey expectations verify the behaviour.
		Functions\expect( 'set_transient' )->once()->with( RewriteFlusher::FLAG, 1, \Mockery::type( 'int' ) );

		$flusher = new RewriteFlusher();
		$flusher->schedule_on_toggle( [ 'sitemap_enabled' => true ], [ 'sitemap_enabled' => false ] );
		$flusher->schedule_on_toggle( [ 'separator' => '-' ], [ 'separator' => '|' ] );
	}

	public function test_maybe_flush_consumes_flag_and_flushes_once(): void {
		$this->expectNotToPerformAssertions(); // Brain Monkey expectations verify the behaviour.
		Functions\when( 'get_transient' )->justReturn( 1 );
		Functions\expect( 'delete_transient' )->once()->with( RewriteFlusher::FLAG );
		Functions\expect( 'flush_rewrite_rules' )->once()->with( false );

		( new RewriteFlusher() )->maybe_flush();
	}

	public function test_maybe_flush_is_a_noop_without_flag(): void {
		$this->expectNotToPerformAssertions(); // Brain Monkey expectations verify the behaviour.
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\expect( 'flush_rewrite_rules' )->never();

		( new RewriteFlusher() )->maybe_flush();
	}
}
