<?php
/**
 * NotFoundHandler unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use LightweightPlugins\SEO\NotFoundHandler;
use LightweightPlugins\SEO\Options;

/**
 * @covers \LightweightPlugins\SEO\NotFoundHandler
 */
final class NotFoundHandlerTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Core redirects a 404 on template_redirect at priority 10
	 * (wp_old_slug_redirect, redirect_canonical) and 1000
	 * (wp_redirect_admin_locations); the homepage fallback must come last.
	 */
	public function test_homepage_fallback_runs_after_core_404_redirects(): void {
		$this->stub_options( [ 'redirect_404_to_home' => true ] );

		$handler = new NotFoundHandler();

		$this->assertGreaterThan( 1000, has_action( 'template_redirect', [ $handler, 'maybe_redirect_404' ] ) );
	}

	public function test_nothing_is_hooked_when_the_option_is_off(): void {
		$this->stub_options( [ 'redirect_404_to_home' => false ] );

		$handler = new NotFoundHandler();

		$this->assertFalse( has_action( 'template_redirect', [ $handler, 'maybe_redirect_404' ] ) );
	}
}
