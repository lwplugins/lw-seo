<?php
/**
 * Bricks compatibility unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Compat;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Compat\Bricks;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class BricksTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();
		Functions\stubTranslationFunctions();
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_disables_bricks_seo_tags(): void {
		$this->stub_options();

		$this->assertTrue( ( new Bricks() )->disable_seo( false ) );
	}

	public function test_disables_bricks_open_graph_when_ours_is_on(): void {
		$this->stub_options( [ 'opengraph_enabled' => true ] );

		$this->assertTrue( ( new Bricks() )->disable_opengraph( false ) );
	}

	public function test_keeps_bricks_open_graph_when_ours_is_off(): void {
		$this->stub_options( [ 'opengraph_enabled' => false ] );

		$this->assertFalse( ( new Bricks() )->disable_opengraph( false ) );
	}

	public function test_keeps_an_open_graph_disable_set_in_bricks(): void {
		$this->stub_options( [ 'opengraph_enabled' => false ] );

		$this->assertTrue( ( new Bricks() )->disable_opengraph( true ) );
	}
}
