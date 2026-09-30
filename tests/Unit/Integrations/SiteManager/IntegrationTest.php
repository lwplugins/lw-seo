<?php
/**
 * LW Site Manager integration unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\SiteManager;

use LightweightPlugins\SEO\Integrations\SiteManager\Integration;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class IntegrationTest extends MonkeyTestCase {

	public function test_register_hooks_the_site_manager_registration_actions(): void {
		( new Integration() )->register();

		$this->assertNotFalse( has_action( 'lw_site_manager_register_categories', [ Integration::class, 'register_category' ] ) );
		$this->assertNotFalse( has_action( 'lw_site_manager_register_abilities', [ Integration::class, 'register_abilities' ] ) );
	}

	public function test_id_is_site_manager(): void {
		$this->assertSame( 'site-manager', ( new Integration() )->id() );
	}
}
