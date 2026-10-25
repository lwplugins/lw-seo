<?php
/**
 * Base test case wiring Brain Monkey setup/teardown.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Extends PHPUnit's TestCase with Brain Monkey lifecycle hooks so WordPress
 * functions can be stubbed without loading WordPress.
 */
abstract class MonkeyTestCase extends TestCase {

	/**
	 * Set up Brain Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		// A production site unless a test says otherwise (IndexNow gates on it).
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );
	}

	/**
	 * Tear down Brain Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
