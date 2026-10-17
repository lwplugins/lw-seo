<?php
/**
 * LogRepository unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\NotFound;

use LightweightPlugins\SEO\NotFound\LogRepository;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\NotFound\LogRepository
 */
final class LogRepositoryTest extends MonkeyTestCase {

	use NotFoundStoreTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->install_wpdb();
	}

	public function test_record_uses_upsert_with_prepare(): void {
		( new LogRepository() )->record( "/o'brien", 'https://example.com/a', 'UA' );

		$this->assertCount( 3, $this->db->queries );
		$this->assertStringContainsString( 'SELECT id, last_seen FROM wp_lw_seo_404_log WHERE url_hash', $this->db->queries[0] );
		$this->assertStringContainsString( 'SELECT COUNT(*)', $this->db->queries[1] );
		$sql = $this->db->queries[2];
		$this->assertStringContainsString( 'INSERT INTO wp_lw_seo_404_log', $sql );
		$this->assertStringContainsString( 'ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = VALUES(last_seen), referrer = VALUES(referrer)', $sql );
		$this->assertStringContainsString( "'" . sha1( "/o'brien" ) . "'", $sql );
		$this->assertStringContainsString( "'/o\\'brien'", $sql, 'values must be escaped by prepare()' );
	}

	public function test_oldest_entry_is_evicted_before_the_insert_at_the_cap(): void {
		$this->db->var_queue = [ '5' ];

		( new LogRepository() )->record( '/new', '', '', 5 );

		$this->assertCount( 4, $this->db->queries );
		$this->assertSame( 'DELETE FROM wp_lw_seo_404_log ORDER BY last_seen ASC LIMIT 1', $this->db->queries[2] );
		$this->assertStringContainsString( 'INSERT INTO', $this->db->queries[3] );
	}

	public function test_eviction_is_bounded_per_request(): void {
		$this->db->var_queue = [ '500' ];

		( new LogRepository() )->record( '/new', '', '', 5 );

		$this->assertStringContainsString( 'ORDER BY last_seen ASC LIMIT 10', $this->db->queries[2] );
	}

	public function test_new_url_is_inserted_below_the_cap(): void {
		$this->db->var_queue = [ '4' ];

		( new LogRepository() )->record( '/new', '', '', 5 );

		$this->assertCount( 3, $this->db->queries, 'no DELETE below the cap' );
		$this->assertStringContainsString( 'INSERT INTO', $this->db->queries[2] );
	}

	public function test_existing_url_is_still_updated_at_the_cap(): void {
		$this->db->results_queue = [ [ [ 'id' => '9', 'last_seen' => gmdate( 'Y-m-d H:i:s', time() - 600 ) ] ] ];
		$this->db->var_queue     = [ '5' ];

		( new LogRepository() )->record( '/old', 'https://example.com/r', '', 5 );

		$this->assertCount( 2, $this->db->queries );
		$this->assertStringContainsString( 'UPDATE wp_lw_seo_404_log SET hits = hits + 1', $this->db->queries[1] );
		$this->assertStringContainsString( 'WHERE id = 9', $this->db->queries[1] );
	}

	public function test_row_seen_within_a_minute_causes_no_write(): void {
		$this->db->results_queue = [ [ [ 'id' => '9', 'last_seen' => gmdate( 'Y-m-d H:i:s', time() - 10 ) ] ] ];

		( new LogRepository() )->record( '/hot', '', '' );

		$this->assertCount( 1, $this->db->queries, 'only the lookup' );
	}

	public function test_paginate_whitelists_orderby(): void {
		$repo = new LogRepository();

		$repo->paginate( '', 'hits; DROP TABLE x', 'sideways', 1, 20 );
		$repo->paginate( '', 'hits', 'asc', 1, 20 );
		$repo->paginate( '', 'url', 'desc', 1, 20 );

		$this->assertStringContainsString( 'ORDER BY last_seen DESC', $this->db->queries[1] );
		$this->assertStringNotContainsString( 'DROP', $this->db->queries[1] );
		$this->assertStringContainsString( 'ORDER BY hits ASC', $this->db->queries[3] );
		$this->assertStringContainsString( 'ORDER BY url DESC', $this->db->queries[5] );
	}

	public function test_paginate_escapes_search_and_offsets_pages(): void {
		$this->db->rows = [
			[ 'id' => '5', 'url' => '/x', 'hits' => '3', 'referrer' => '', 'user_agent' => '', 'first_seen' => 'a', 'last_seen' => 'b' ],
		];
		$this->db->var  = '41';

		$result = ( new LogRepository() )->paginate( '50%_off', 'last_seen', 'desc', 3, 20 );

		$this->assertStringContainsString( "'%50\\\\%\\\\_off%'", $this->db->queries[1] );
		$this->assertStringContainsString( 'LIMIT 20 OFFSET 40', $this->db->queries[1] );
		$this->assertSame( 41, $result['total'] );
		$this->assertSame( 5, $result['items'][0]['id'] );
		$this->assertSame( 3, $result['items'][0]['hits'] );
	}

	public function test_delete_casts_ids_and_skips_empty(): void {
		$repo = new LogRepository();

		$this->assertSame( 0, $repo->delete( [ 0, 'abc' ] ) );
		$this->assertSame( [], $this->db->queries );

		$repo->delete( [ '4', 7, '9; DROP' ] );
		$this->assertStringContainsString( 'WHERE id IN (4, 7, 9)', $this->db->queries[0] );
	}

	public function test_clear_truncates(): void {
		( new LogRepository() )->clear();

		$this->assertSame( 'TRUNCATE TABLE wp_lw_seo_404_log', $this->db->queries[0] );
	}

	public function test_prune_deletes_by_age_then_cap(): void {
		$this->db->query_result = 3;

		$deleted = ( new LogRepository() )->prune( 500, 30 );

		$this->assertSame( 6, $deleted );
		$this->assertCount( 2, $this->db->queries );
		$this->assertMatchesRegularExpression( "/DELETE FROM wp_lw_seo_404_log WHERE last_seen < '\d{4}-\d\d-\d\d \d\d:\d\d:\d\d'/", $this->db->queries[0] );
		$this->assertStringContainsString( 'ORDER BY last_seen DESC, id DESC LIMIT 500', $this->db->queries[1] );
		$this->assertStringContainsString( 'id NOT IN', $this->db->queries[1] );
	}
}
