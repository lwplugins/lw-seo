<?php
/**
 * LegacyAliases unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Sitemap;

use LightweightPlugins\SEO\Sitemap\LegacyAliases;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Sitemap\LegacyAliases
 */
final class LegacyAliasesTest extends MonkeyTestCase {

	/**
	 * @dataProvider provide_aliases
	 */
	public function test_recognises_other_plugins_index_addresses( string $uri ): void {
		$this->assertTrue( LegacyAliases::is_alias( $uri, '' ) );
	}

	public static function provide_aliases(): array {
		return [
			'index'            => [ '/sitemap_index.xml' ],
			'core'             => [ '/wp-sitemap.xml' ],
			'with query'       => [ '/sitemap_index.xml?x=1' ],
		];
	}

	/**
	 * @dataProvider provide_other_paths
	 */
	public function test_leaves_other_paths_alone( string $uri ): void {
		$this->assertFalse( LegacyAliases::is_alias( $uri, '' ) );
	}

	public static function provide_other_paths(): array {
		return [
			'own index'     => [ '/sitemap.xml' ],
			'own sub-map'   => [ '/sitemap-post.xml' ],
			'page'          => [ '/about/' ],
			'nested'        => [ '/blog/sitemap_index.xml' ],
		];
	}

	public function test_respects_a_subdirectory_home(): void {
		$this->assertTrue( LegacyAliases::is_alias( '/hu/sitemap_index.xml', '/hu' ) );
		$this->assertFalse( LegacyAliases::is_alias( '/sitemap_index.xml', '/hu' ) );
	}
}
