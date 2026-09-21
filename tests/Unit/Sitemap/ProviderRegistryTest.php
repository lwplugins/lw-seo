<?php
/**
 * ProviderRegistry unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Sitemap;

use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Sitemap\ProviderRegistry;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class ProviderRegistryTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * @return array<string, array{0: string, 1: array<string, mixed>, 2: bool}>
	 */
	public static function post_type_provider(): array {
		return [
			'posts on by default'         => [ 'post', [], true ],
			'posts toggled off'           => [ 'post', [ 'sitemap_posts' => false ], false ],
			'pages on by default'         => [ 'page', [], true ],
			'products need woo_enabled'   => [ 'product', [ 'woo_enabled' => false ], false ],
			'products on with woo'        => [ 'product', [], true ],
			'new CPT included by default' => [ 'case_study', [], true ],
			'CPT toggled off'             => [ 'case_study', [ 'sitemap_post_types' => [ 'case_study' => false ] ], false ],
			'CPT toggled on explicitly'   => [ 'case_study', [ 'sitemap_post_types' => [ 'case_study' => true ] ], true ],
		];
	}

	/**
	 * @dataProvider post_type_provider
	 *
	 * @param string               $type     Post type.
	 * @param array<string, mixed> $saved    Saved options.
	 * @param bool                 $expected Expected state.
	 */
	public function test_post_type_enabled( string $type, array $saved, bool $expected ): void {
		$this->stub_options( $saved );

		$this->assertSame( $expected, ProviderRegistry::post_type_enabled( $type ) );
	}

	/**
	 * @return array<string, array{0: string, 1: array<string, mixed>, 2: bool}>
	 */
	public static function taxonomy_provider(): array {
		return [
			'categories on by default'        => [ 'category', [], true ],
			'tags off by default'             => [ 'post_tag', [], false ],
			'product_cat needs woo'           => [ 'product_cat', [ 'woo_enabled' => false ], false ],
			'custom taxonomy opt-in'          => [ 'genre', [], false ],
			'custom taxonomy toggled on'      => [ 'genre', [ 'sitemap_taxonomies' => [ 'genre' => true ] ], true ],
		];
	}

	/**
	 * @dataProvider taxonomy_provider
	 *
	 * @param string               $taxonomy Taxonomy.
	 * @param array<string, mixed> $saved    Saved options.
	 * @param bool                 $expected Expected state.
	 */
	public function test_taxonomy_enabled( string $taxonomy, array $saved, bool $expected ): void {
		$this->stub_options( $saved );

		$this->assertSame( $expected, ProviderRegistry::taxonomy_enabled( $taxonomy ) );
	}
}
