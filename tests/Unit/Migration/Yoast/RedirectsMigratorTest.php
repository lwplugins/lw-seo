<?php
/**
 * Tests for the Yoast SEO Premium redirect importer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\Yoast;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Migration\Yoast\RedirectsMigrator;
use LightweightPlugins\SEO\Tests\Unit\Migration\MigrationStoreTrait;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\Yoast\RedirectsMigrator
 */
final class RedirectsMigratorTest extends MonkeyTestCase {

	use MigrationStoreTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->stub_store();
		Functions\when( 'home_url' )->justReturn( 'https://example.test' );
		Functions\when( 'current_time' )->justReturn( '2026-09-27 10:00:00' );
		Functions\when( 'wp_generate_uuid4' )->justReturn( 'uuid' );

		$this->options['wpseo-premium-redirects-base'] = [
			[ 'origin' => 'old-page', 'url' => '/new-page/', 'type' => 301, 'format' => 'plain' ],
			[ 'origin' => '^blog/(\d+)$', 'url' => '/posts/$1', 'type' => 302, 'format' => 'regex' ],
			[ 'origin' => 'gone', 'url' => '', 'type' => 410, 'format' => 'plain' ],
		];
	}

	public function test_imports_the_redirects(): void {
		$result = ( new RedirectsMigrator() )->migrate();

		$this->assertSame( [ 3, 0 ], [ $result['migrated'], $result['skipped'] ] );
		$this->assertSame( [ '/old-page', '^blog/(\d+)$', '/gone' ], array_column( $this->options['lw_seo_redirects'], 'source' ) );
	}

	public function test_running_the_import_again_adds_no_duplicates(): void {
		( new RedirectsMigrator() )->migrate();

		$result = ( new RedirectsMigrator() )->migrate();

		$this->assertCount( 3, $this->options['lw_seo_redirects'] );
		$this->assertSame( [ 0, 3, 3 ], [ $result['migrated'], $result['skipped'], $result['skipped_already_present'] ] );
	}

	public function test_dry_run_reports_redirects_lw_seo_already_has(): void {
		( new RedirectsMigrator() )->migrate();

		$result = ( new RedirectsMigrator( true ) )->migrate();

		$this->assertSame( [ 0, 3 ], [ $result['migrated'], $result['skipped_already_present'] ] );
	}
}
