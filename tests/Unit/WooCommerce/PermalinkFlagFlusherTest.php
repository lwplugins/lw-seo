<?php
/**
 * PermalinkFlagFlusher unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\WooCommerce;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\WooCommerce\PermalinkFlagFlusher;

final class PermalinkFlagFlusherTest extends MonkeyTestCase {

	public function test_flushes_when_a_permalink_flag_changes(): void {
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\expect( 'flush_rewrite_rules' )->once()->with( false );

		$this->expectNotToPerformAssertions(); // Brain Monkey expectations verify the behaviour.

		( new PermalinkFlagFlusher() )->flush_on_permalink_change( [ 'wc_remove_product_base' => false ], [ 'wc_remove_product_base' => true ] );
	}

	public function test_does_not_flush_when_other_options_change(): void {
		Functions\expect( 'flush_rewrite_rules' )->never();

		$this->expectNotToPerformAssertions(); // Brain Monkey expectations verify the behaviour.

		( new PermalinkFlagFlusher() )->flush_on_permalink_change( [ 'separator' => '-' ], [ 'separator' => '|' ] );
	}
}
