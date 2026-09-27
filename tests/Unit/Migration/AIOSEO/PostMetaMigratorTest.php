<?php
/**
 * Tests for the All in One SEO post importer, on real aioseo_posts rows.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\AIOSEO;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Migration\AIOSEO\PostMetaMigrator;
use LightweightPlugins\SEO\Migration\AIOSEO\PostRowMapper;
use LightweightPlugins\SEO\Migration\AIOSEO\PostsTable;
use LightweightPlugins\SEO\Migration\AIOSEO\SmartTagConverter;
use LightweightPlugins\SEO\Migration\Support\VariableLog;
use LightweightPlugins\SEO\Tests\Unit\Migration\MigrationStoreTrait;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\AIOSEO\PostMetaMigrator
 * @covers \LightweightPlugins\SEO\Migration\AIOSEO\PostRowMapper
 * @covers \LightweightPlugins\SEO\Migration\AIOSEO\PostsTable
 */
final class PostMetaMigratorTest extends MonkeyTestCase {

	use MigrationStoreTrait;

	/**
	 * Fixture rows keyed by post ID.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $rows = [];

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'ARRAY_A' ) ) {
			define( 'ARRAY_A', 'ARRAY_A' );
		}
		$this->stub_store();

		$fixture = $this->fixture( 'aioseo' );
		foreach ( $fixture['rows'] as $name => $data ) {
			$row               = $data['row'];
			$row['id']         = $row['post_id'];
			$id                = (int) $row['post_id'];
			$this->rows[ $id ] = $row;
			$this->meta[ "post:$id" ] = (array) $fixture['lw_meta'][ $name ];
		}

		$names = [];
		foreach ( $fixture['rows'] as $name => $data ) {
			$names[ (int) $data['row']['post_id'] ] = $name;
		}
		Functions\when( 'get_post' )->alias(
			fn( $id ) => isset( $this->rows[ $id ] ) ? new \WP_Post(
				[
					'ID'         => $id,
					'post_title' => 'Post ' . $names[ $id ],
				]
			) : null
		);
		Functions\when( 'get_the_title' )->alias( static fn( $post ) => $post->post_title );
		Functions\when( 'get_bloginfo' )->alias( static fn( $show ) => 'name' === $show ? 'Croco2' : 'Demo site' );
		Functions\when( 'wp_date' )->justReturn( '2026' );

		$wpdb = $this->read_only_wpdb();
		$wpdb->shouldReceive( 'get_var' )->andReturn( 'wp_aioseo_posts' );
		$wpdb->shouldReceive( 'get_results' )->andReturnUsing(
			// The HAS_DATA condition drops rows without any custom value (post I).
			fn() => array_values( array_filter( $this->rows, static fn( array $row ): bool => '0' === $row['robots_default'] || null !== $row['title'] || null !== $row['description'] ) )
		);
	}

	/**
	 * Importer bound to the test store.
	 *
	 * @param bool $dry_run Dry run.
	 * @return PostMetaMigrator
	 */
	private function importer( bool $dry_run = false ): PostMetaMigrator {
		return new PostMetaMigrator( new PostsTable(), new PostRowMapper( new SmartTagConverter( new VariableLog() ) ), $dry_run );
	}

	public function test_imports_custom_fields_with_converted_smart_tags_and_robots(): void {
		$counts = $this->importer()->migrate();

		$f = $this->meta['post:448'];
		// LW SEO shows per-post text as saved, so smart tags are filled in at import time.
		$this->assertSame( 'Custom F Post seo-import-test-aioseo-post-f - Croco2', $f['_lw_seo_title'] );
		$this->assertSame( 'AIOSEO description of F on Croco2', $f['_lw_seo_description'] );
		$this->assertSame( 'https://example.test/seo-import-canonical-f/', $f['_lw_seo_canonical'] );
		$this->assertSame( 'OG title F', $f['_lw_seo_og_title'] );
		$this->assertSame( 'OG description F', $f['_lw_seo_og_description'] );
		$this->assertStringEndsWith( '.webp', $f['_lw_seo_og_image'] );
		$this->assertSame( [ '1', '1' ], [ $f['_lw_seo_noindex'], $f['_lw_seo_nofollow'] ] );
		$this->assertSame( 3, $counts['migrated'] );
	}

	public function test_twitter_values_are_fallbacks_only_when_not_reusing_open_graph(): void {
		$this->importer()->migrate();

		$this->assertSame( 'Twitter title G', $this->meta['post:449']['_lw_seo_og_title'] );
		$this->assertStringEndsWith( '.webp', $this->meta['post:449']['_lw_seo_og_image'] );
		$this->assertArrayNotHasKey( '_lw_seo_og_title', $this->meta['post:450'] );
	}

	public function test_robots_columns_are_ignored_while_robots_default_is_on(): void {
		$this->importer()->migrate();

		$this->assertArrayNotHasKey( '_lw_seo_noindex', $this->meta['post:450'] );
		$this->assertSame( 'Page H 2026 Demo site', $this->meta['post:450']['_lw_seo_description'] );
	}

	public function test_never_overwrites_a_filled_lw_seo_value(): void {
		$this->importer()->migrate();

		$this->assertSame( 'LW SEO description kept on G', $this->meta['post:449']['_lw_seo_description'] );
		$this->assertSame( 'G Post seo-import-test-aioseo-post-g - Croco2', $this->meta['post:449']['_lw_seo_title'] );
	}

	public function test_imports_the_primary_term(): void {
		$importer = $this->importer();
		$importer->migrate();

		$this->assertSame( 38, $this->meta['post:448']['_lw_seo_primary_category'] );
		$this->assertSame( 1, $importer->primary_terms()['migrated'] );
	}

	public function test_second_run_imports_nothing(): void {
		$this->importer()->migrate();
		$this->writes = [];

		$importer = $this->importer();
		$counts   = $importer->migrate();

		$this->assertSame( 0, $counts['migrated'] );
		$this->assertSame( 3, $counts['skipped_already_present'] );
		$this->assertSame( 0, $importer->primary_terms()['migrated'] );
		$this->assertSame( [], $this->writes );
	}

	public function test_dry_run_writes_nothing(): void {
		$counts = $this->importer( true )->migrate();

		$this->assertSame( 3, $counts['migrated'] );
		$this->assertSame( [], $this->writes );
	}

	public function test_only_lw_seo_meta_is_written(): void {
		$this->importer()->migrate();

		$this->assertSame( [], $this->foreign_writes() );
	}

	public function test_computed_image_cache_is_not_imported(): void {
		$mapper = new PostRowMapper( new SmartTagConverter( new VariableLog() ) );

		$fields = $mapper->fields(
			[
				'og_image_type'       => 'featured',
				'og_image_url'        => 'https://example.test/cache.jpg',
				'og_image_custom_url' => 'https://example.test/custom.jpg',
				'twitter_use_og'      => '1',
			]
		);

		$this->assertSame( '', $fields['og_image'] );
	}

	public function test_invalid_primary_term_json_is_ignored(): void {
		$mapper = new PostRowMapper( new SmartTagConverter( new VariableLog() ) );

		$this->assertSame( [], $mapper->primary_terms( [ 'primary_term' => '{broken' ] ) );
		$this->assertSame( [ 'product_cat' => 5 ], $mapper->primary_terms( [ 'primary_term' => '{"product_cat":"5","category":0}' ] ) );
	}
}
