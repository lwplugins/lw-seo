<?php
/**
 * Bricks integration unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\Bricks;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\Bricks\Integration;
use LightweightPlugins\SEO\Integrations\Bricks\MarkdownContent;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class IntegrationTest extends MonkeyTestCase {

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

		$this->assertTrue( ( new Integration() )->disable_seo( false ) );
	}

	public function test_disables_bricks_open_graph_when_ours_is_on(): void {
		$this->stub_options( [ 'opengraph_enabled' => true ] );

		$this->assertTrue( ( new Integration() )->disable_opengraph( false ) );
	}

	public function test_keeps_bricks_open_graph_when_ours_is_off(): void {
		$this->stub_options( [ 'opengraph_enabled' => false ] );

		$this->assertFalse( ( new Integration() )->disable_opengraph( false ) );
	}

	public function test_keeps_an_open_graph_disable_set_in_bricks(): void {
		$this->stub_options( [ 'opengraph_enabled' => false ] );

		$this->assertTrue( ( new Integration() )->disable_opengraph( true ) );
	}

	public function test_is_available_with_the_bricks_theme_or_a_child_theme(): void {
		Functions\when( 'get_template' )->justReturn( 'bricks' );

		$this->assertTrue( ( new Integration() )->is_available() );
	}

	public function test_is_not_available_with_another_theme(): void {
		Functions\when( 'get_template' )->justReturn( 'twentytwentyfive' );

		$this->assertFalse( ( new Integration() )->is_available() );
	}

	public function test_register_hooks_the_seo_switches_and_the_markdown_source(): void {
		( new Integration() )->register();

		$this->assertNotFalse( has_filter( 'bricks/frontend/disable_seo' ) );
		$this->assertNotFalse( has_filter( 'bricks/frontend/disable_opengraph' ) );
		$this->assertSame( 10, has_filter( 'lw_seo_markdown_source_html', [ MarkdownContent::class, 'filter' ] ) );
		$this->assertNotFalse( has_action( 'updated_post_meta' ) );
	}
}
