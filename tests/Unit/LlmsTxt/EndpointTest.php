<?php
/**
 * llms.txt Endpoint unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\LlmsTxt;

use LightweightPlugins\SEO\LlmsTxt\Endpoint;
use LightweightPlugins\SEO\LlmsTxt\Invalidation;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class EndpointTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_settings_changes_flush_the_cache_while_llms_txt_is_disabled(): void {
		$this->stub_options( [ 'llms_txt_enabled' => false ] );

		new Endpoint();

		$this->assertNotFalse( has_action( 'update_option_' . Options::OPTION_NAME, [ Invalidation::class, 'flush' ] ) );
	}

	public function test_content_changes_still_flush_while_llms_txt_is_disabled(): void {
		$this->stub_options( [ 'llms_txt_enabled' => false ] );

		new Endpoint();

		// The shared lw_seo_content_changed action also drops the HTML sitemap cache.
		$this->assertNotFalse( has_action( 'save_post', [ Invalidation::class, 'flush_for_post' ] ) );
		$this->assertFalse( has_action( 'template_redirect' ) );
	}
}
