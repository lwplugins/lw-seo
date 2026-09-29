<?php
/**
 * TaxonomyProvider unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Sitemap;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Sitemap\TaxonomyProvider;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Sitemap\TaxonomyProvider
 */
final class TaxonomyProviderTest extends MonkeyTestCase {

	/**
	 * Without an explicit orderby, WooCommerce switches product taxonomy
	 * queries to its menu-order meta sort, whose termmeta join drops every
	 * term that has other meta but no `order` meta (issue #19).
	 */
	public function test_queries_terms_in_an_explicit_stable_order(): void {
		$args = [];
		Functions\when( 'get_terms' )->alias(
			static function ( array $query ) use ( &$args ): array {
				$args = $query;
				return [];
			}
		);

		( new TaxonomyProvider( 'product_cat' ) )->get_items( 2 );

		$this->assertSame( 'term_id', $args['orderby'] ?? null );
		$this->assertSame( 'ASC', $args['order'] ?? null );
		$this->assertSame( 1000, $args['offset'] );
	}
}
