<?php
/**
 * CanonicalGuard unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use LightweightPlugins\SEO\CanonicalGuard;

final class CanonicalGuardTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: array<string, mixed>, 1: bool}>
	 */
	public static function query_vars_provider(): array {
		return [
			'sitemap index'         => [ [ 'lw_sitemap' => 'index' ], true ],
			'sitemap sub-page'      => [ [ 'lw_sitemap' => 'post', 'lw_sitemap_page' => '2' ], true ],
			'llms.txt'              => [ [ 'lw_llms_txt' => '1' ], true ],
			'robots.txt'            => [ [ 'lw_robots_txt' => '1' ], true ],
			'md endpoint (empty)'   => [ [ 'name' => 'hello-world', 'md' => '' ], true ],
			'markdown endpoint'     => [ [ 'name' => 'hello-world', 'markdown' => '' ], true ],
			'regular single'        => [ [ 'name' => 'hello-world' ], false ],
			'regular home'          => [ [], false ],
			'empty sitemap var'     => [ [ 'lw_sitemap' => '' ], false ],
			'unrelated format var'  => [ [ 'name' => 'hello-world', 'format' => 'md' ], false ],
		];
	}

	/**
	 * @dataProvider query_vars_provider
	 *
	 * @param array<string, mixed> $query_vars Parsed query vars.
	 * @param bool                 $expected   Whether this is a virtual endpoint request.
	 */
	public function test_is_virtual_request_detects_plugin_endpoints( array $query_vars, bool $expected ): void {
		$this->assertSame( $expected, CanonicalGuard::is_virtual_request( $query_vars ) );
	}

	public function test_filter_returns_false_for_virtual_endpoint(): void {
		$GLOBALS['wp_query'] = (object) [ 'query_vars' => [ 'lw_sitemap' => 'index' ] ];

		$guard = new CanonicalGuard();

		$this->assertFalse( $guard->filter( 'https://example.com/sitemap.xml/', 'https://example.com/sitemap.xml' ) );
	}

	public function test_filter_passes_through_for_regular_requests(): void {
		$GLOBALS['wp_query'] = (object) [ 'query_vars' => [ 'name' => 'hello-world' ] ];

		$guard = new CanonicalGuard();

		$this->assertSame( 'https://example.com/hello-world/', $guard->filter( 'https://example.com/hello-world/', 'https://example.com/hello-world' ) );
	}

	public function test_filter_passes_through_when_query_is_missing(): void {
		unset( $GLOBALS['wp_query'] );

		$guard = new CanonicalGuard();

		$this->assertSame( 'https://example.com/x/', $guard->filter( 'https://example.com/x/', 'https://example.com/x' ) );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_query'] );
		parent::tearDown();
	}
}
