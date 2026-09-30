<?php
/**
 * Integrations loader unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations;

use Brain\Monkey\Filters;
use LightweightPlugins\SEO\Integrations\IntegrationInterface;
use LightweightPlugins\SEO\Integrations\Loader;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class LoaderTest extends MonkeyTestCase {

	/**
	 * A test integration that records whether it was registered.
	 */
	private function integration( string $id, bool $available ): IntegrationInterface {
		return new class( $id, $available ) implements IntegrationInterface {

			public bool $registered = false;

			public function __construct( private string $id, private bool $available ) {}

			public function id(): string {
				return $this->id;
			}

			public function is_available(): bool {
				return $this->available;
			}

			public function register(): void {
				$this->registered = true;
			}
		};
	}

	public function test_boots_on_after_setup_theme(): void {
		$loader = new Loader( [] );

		$loader->register();

		$this->assertSame( 1, has_action( 'after_setup_theme', [ $loader, 'boot' ] ) );
	}

	public function test_registers_an_available_integration(): void {
		$bricks = $this->integration( 'bricks', true );

		( new Loader( [ $bricks ] ) )->boot();

		$this->assertTrue( $bricks->registered );
		$this->assertTrue( Loader::is_active( 'bricks' ) );
	}

	public function test_skips_an_unavailable_integration(): void {
		$polylang = $this->integration( 'polylang', false );

		( new Loader( [ $polylang ] ) )->boot();

		$this->assertFalse( $polylang->registered );
		$this->assertFalse( Loader::is_active( 'polylang' ) );
	}

	public function test_skips_an_integration_the_filter_disables(): void {
		$woo = $this->integration( 'woocommerce', true );
		Filters\expectApplied( 'lw_seo_integration_enabled' )->once()->with( true, 'woocommerce' )->andReturn( false );

		( new Loader( [ $woo ] ) )->boot();

		$this->assertFalse( $woo->registered );
		$this->assertFalse( Loader::is_active( 'woocommerce' ) );
	}

	public function test_is_active_reflects_the_last_boot_only(): void {
		( new Loader( [ $this->integration( 'bricks', true ) ] ) )->boot();
		( new Loader( [] ) )->boot();

		$this->assertFalse( Loader::is_active( 'bricks' ) );
	}
}
