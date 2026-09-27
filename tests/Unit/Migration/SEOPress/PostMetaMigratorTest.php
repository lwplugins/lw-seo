<?php
/**
 * Tests for the SEOPress post and term meta importers, on real SEOPress data.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\SEOPress;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Migration\SEOPress\MetaMapper;
use LightweightPlugins\SEO\Migration\SEOPress\MetaSource;
use LightweightPlugins\SEO\Migration\SEOPress\PostMetaMigrator;
use LightweightPlugins\SEO\Migration\SEOPress\TermMetaMigrator;
use LightweightPlugins\SEO\Migration\SEOPress\VariableConverter;
use LightweightPlugins\SEO\Migration\Support\VariableLog;
use LightweightPlugins\SEO\Tests\Unit\Migration\MigrationStoreTrait;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\SEOPress\PostMetaMigrator
 * @covers \LightweightPlugins\SEO\Migration\SEOPress\TermMetaMigrator
 * @covers \LightweightPlugins\SEO\Migration\SEOPress\MetaMapper
 * @covers \LightweightPlugins\SEO\Migration\SEOPress\MetaSource
 * @covers \LightweightPlugins\SEO\Migration\Support\TextResolver
 */
final class PostMetaMigratorTest extends MonkeyTestCase {

	use MigrationStoreTrait;

	/**
	 * Fixture post name => test ID.
	 */
	private const POSTS = [
		'seo-import-test-post-a'    => 1,
		'seo-import-test-post-b'    => 2,
		'seo-import-test-page-c'    => 3,
		'seo-import-test-post-d'    => 4,
		'seo-import-test-product-e' => 5,
	];

	/**
	 * Fixture term slug => test ID.
	 */
	private const TERMS = [
		'seo-import-test-category'    => 11,
		'seo-import-test-tag'         => 12,
		'seo-import-test-product-cat' => 13,
	];

	/**
	 * Post ID => post type.
	 *
	 * @var array<int, string>
	 */
	private array $types = [];

	protected function setUp(): void {
		parent::setUp();
		$this->stub_store();
		$fixture = $this->fixture( 'seopress' );

		foreach ( self::POSTS as $name => $id ) {
			$this->meta[ "post:$id" ] = $fixture['posts'][ $name ]['meta'];
			$this->types[ $id ]       = $fixture['posts'][ $name ]['post_type'];
		}
		foreach ( self::TERMS as $slug => $id ) {
			$this->meta[ "term:$id" ] = (array) $fixture['terms'][ $slug ]['meta'];
		}

		$titles = array_flip( self::POSTS );
		Functions\when( 'get_post' )->alias(
			fn( $id ) => isset( $this->types[ $id ] ) ? new \WP_Post(
				[
					'ID'         => $id,
					'post_type'  => $this->types[ $id ],
					'post_title' => ucfirst( str_replace( [ 'seo-import-test-', '-' ], [ 'SEO import test ', ' ' ], $titles[ $id ] ) ),
				]
			) : null
		);
		$names = array_flip( self::TERMS );
		Functions\when( 'get_term' )->alias( fn( $id ) => new \WP_Term( [ 'term_id' => $id, 'name' => 'Term ' . $names[ $id ] ] ) );
		Functions\when( 'get_the_title' )->alias( static fn( $post ) => $post->post_title );
		Functions\when( 'get_bloginfo' )->alias( static fn( $show ) => 'name' === $show ? 'Croco2' : 'Demo site' );
		Functions\when( 'wp_date' )->justReturn( '2026' );

		$wpdb = $this->read_only_wpdb();
		$wpdb->shouldReceive( 'get_col' )->andReturnUsing( fn( string $sql ) => $this->ids_with_keys( str_contains( $sql, 'termmeta' ) ? 'term' : 'post' ) );
	}

	/**
	 * IDs whose fixture meta holds a non-empty key the importer looks for
	 * (what MetaSource's SQL returns).
	 *
	 * @param string $type 'post' or 'term'.
	 * @return array<string>
	 */
	private function ids_with_keys( string $type ): array {
		$keys = array_merge( MetaMapper::keys(), 'post' === $type ? [ '_seopress_robots_primary_cat' ] : [] );
		$ids  = [];
		foreach ( $this->meta as $object => $meta ) {
			[ $object_type, $id ] = explode( ':', $object );
			if ( $object_type === $type && array_filter( array_intersect_key( $meta, array_flip( $keys ) ) ) ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	/**
	 * Post importer bound to the test store.
	 *
	 * @param bool $dry_run Dry run.
	 * @return PostMetaMigrator
	 */
	private function posts( bool $dry_run = false ): PostMetaMigrator {
		return new PostMetaMigrator( new MetaSource(), new MetaMapper( new VariableConverter( new VariableLog() ) ), $dry_run );
	}

	public function test_imports_post_fields_with_converted_variables_and_robots(): void {
		$counts = $this->posts()->migrate();

		$a = $this->meta['post:1'];
		// LW SEO shows per-post text as saved, so variables are filled in at import time.
		$this->assertSame( 'Custom A SEO import test post a - Croco2', $a['_lw_seo_title'] );
		$this->assertSame( 'SEOPress description of post A (Croco2)', $a['_lw_seo_description'] );
		$this->assertSame( 'https://example.test/seo-import-canonical-a/', $a['_lw_seo_canonical'] );
		$this->assertSame( 'FB title A', $a['_lw_seo_og_title'] );
		$this->assertSame( 'FB description A', $a['_lw_seo_og_description'] );
		$this->assertStringEndsWith( '.webp', $a['_lw_seo_og_image'] );
		$this->assertSame( [ '1', '1' ], [ $a['_lw_seo_noindex'], $a['_lw_seo_nofollow'] ] );
		$this->assertSame( 4, $counts['migrated'] );
	}

	public function test_twitter_values_fill_empty_open_graph_targets(): void {
		$this->posts()->migrate();

		$this->assertSame( 'Twitter title B', $this->meta['post:2']['_lw_seo_og_title'] );
		$this->assertSame( 'Twitter description B', $this->meta['post:2']['_lw_seo_og_description'] );
	}

	public function test_never_overwrites_a_filled_lw_seo_value(): void {
		$this->posts()->migrate();

		$this->assertSame( 'LW SEO title kept on B', $this->meta['post:2']['_lw_seo_title'] );
		$this->assertSame( 'SEOPress description of post B', $this->meta['post:2']['_lw_seo_description'] );
	}

	public function test_only_yes_counts_as_noindex(): void {
		$this->posts()->migrate();

		$this->assertArrayNotHasKey( '_lw_seo_noindex', $this->meta['post:3'] );
		$this->assertSame( '1', $this->meta['post:3']['_lw_seo_nofollow'] );
		$this->assertSame( 'Page C - Croco2', $this->meta['post:3']['_lw_seo_title'] );
		$this->assertSame( 'Page C description 2026', $this->meta['post:3']['_lw_seo_description'] );
	}

	public function test_imports_primary_category_per_post_type_and_ignores_none(): void {
		$migrator = $this->posts();
		$migrator->migrate();

		$this->assertSame( 35, $this->meta['post:1']['_lw_seo_primary_category'] );
		$this->assertSame( 37, $this->meta['post:5']['_lw_seo_primary_product_cat'] );
		$this->assertArrayNotHasKey( '_lw_seo_primary_category', $this->meta['post:3'] );
		$this->assertSame(
			[
				'migrated'   => 2,
				'taxonomies' => [
					'primary_category'    => 1,
					'primary_product_cat' => 1,
				],
			],
			$migrator->primary_terms()
		);
	}

	public function test_second_run_imports_nothing(): void {
		$this->posts()->migrate();
		$this->writes = [];

		$second  = $this->posts();
		$counts  = $second->migrate();
		$primary = $second->primary_terms();

		$this->assertSame( 0, $counts['migrated'] );
		$this->assertSame( 4, $counts['skipped_already_present'] );
		$this->assertSame( 0, $primary['migrated'] );
		$this->assertSame( [], $this->writes );
	}

	public function test_dry_run_counts_without_writing(): void {
		$counts = $this->posts( true )->migrate();

		$this->assertSame( 4, $counts['migrated'] );
		$this->assertSame( [], $this->writes );
	}

	public function test_leaves_seopress_data_untouched(): void {
		$before = $this->fixture( 'seopress' )['posts'];

		$this->posts()->migrate();

		$this->assertSame( [], $this->foreign_writes() );
		foreach ( self::POSTS as $name => $id ) {
			$this->assertSame( $before[ $name ]['meta'], array_intersect_key( $this->meta[ "post:$id" ], $before[ $name ]['meta'] ) );
		}
	}

	public function test_imports_term_fields_without_canonical_or_nofollow(): void {
		$counts = ( new TermMetaMigrator( new MetaSource(), new MetaMapper( new VariableConverter( new VariableLog() ) ) ) )->migrate();

		$category = $this->meta['term:11'];
		$this->assertSame( 'Category Term seo-import-test-category - Croco2', $category['_lw_seo_title'] );
		$this->assertSame( 'SEOPress category description', $category['_lw_seo_description'] );
		$this->assertSame( 'FB category title', $category['_lw_seo_og_title'] );
		$this->assertSame( '1', $category['_lw_seo_noindex'] );
		$this->assertArrayNotHasKey( '_lw_seo_canonical', $category );
		$this->assertArrayNotHasKey( '_lw_seo_nofollow', $category );
		$this->assertSame( 'Twitter tag title', $this->meta['term:12']['_lw_seo_og_title'] );
		$this->assertSame( 2, $counts['migrated'] );
		$this->assertSame( [], $this->foreign_writes() );
	}
}
