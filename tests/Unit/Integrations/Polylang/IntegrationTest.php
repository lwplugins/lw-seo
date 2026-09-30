<?php
/**
 * Polylang integration unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\Polylang;

use LightweightPlugins\SEO\Integrations\Polylang\Integration;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class IntegrationTest extends MonkeyTestCase {

	public function test_queries_every_language(): void {
		$this->assertSame(
			[
				'post_type' => 'page',
				'lang'      => '',
			],
			( new Integration() )->all_languages( [ 'post_type' => 'page' ] )
		);
	}

	public function test_leaves_a_non_array_value_alone(): void {
		$this->assertSame( 'x', ( new Integration() )->all_languages( 'x' ) );
	}

	public function test_register_hooks_the_query_args_filter(): void {
		$integration = new Integration();

		$integration->register();

		$this->assertSame( 10, has_filter( 'lw_seo_query_args', [ $integration, 'all_languages' ] ) );
	}
}
