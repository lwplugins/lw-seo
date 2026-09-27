<?php
/**
 * Tests for the SEOPress redirect importer, on real SEOPress PRO redirects.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\SEOPress;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Migration\SEOPress\RedirectsMigrator;
use LightweightPlugins\SEO\Tests\Unit\Migration\MigrationStoreTrait;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\SEOPress\RedirectsMigrator
 */
final class RedirectsMigratorTest extends MonkeyTestCase {

	use MigrationStoreTrait;

	/**
	 * Redirect entries built from the fixture the way RedirectSource builds them.
	 *
	 * @var array<array{source: string, destination: string, type: int, regex: bool, enabled: bool, logged_status: string, label: string}>
	 */
	private array $entries = [];

	protected function setUp(): void {
		parent::setUp();
		$this->stub_store();
		Functions\when( 'home_url' )->justReturn( 'https://example.test' );
		Functions\when( 'current_time' )->justReturn( '2026-09-27 10:00:00' );
		Functions\when( 'wp_generate_uuid4' )->justReturn( 'uuid' );

		$fixture = $this->fixture( 'seopress' );
		foreach ( $fixture['redirects'] as $index => $redirect ) {
			$meta            = $redirect['meta'];
			$this->entries[] = [
				'source'        => $redirect['post_title'],
				'destination'   => $meta['_seopress_redirections_value'] ?? '',
				'type'          => (int) ( $meta['_seopress_redirections_type'] ?? 0 ),
				'regex'         => 'yes' === ( $meta['_seopress_redirections_enabled_regex'] ?? '' ),
				'enabled'       => 'yes' === ( $meta['_seopress_redirections_enabled'] ?? '' ),
				'logged_status' => $meta['_seopress_redirections_logged_status'] ?? '',
				'label'         => 'seopress_404 #' . $index,
			];
		}
		// The free per-post redirect of "post D".
		$d               = $fixture['posts']['seo-import-test-post-d']['meta'];
		$this->entries[] = [
			'source'        => 'https://example.test/seo-import-test-post-d/',
			'destination'   => $d['_seopress_redirections_value'],
			'type'          => (int) $d['_seopress_redirections_type'],
			'regex'         => false,
			'enabled'       => 'yes' === $d['_seopress_redirections_enabled'],
			'logged_status' => $d['_seopress_redirections_logged_status'],
			'label'         => 'post #4',
		];
	}

	public function test_imports_enabled_public_redirects(): void {
		$result = ( new RedirectsMigrator() )->migrate( $this->entries );

		$stored = array_column( $this->options['lw_seo_redirects'], null, 'source' );
		$this->assertSame( 4, $result['migrated'] );
		$this->assertSame( 301, $stored['/seo-import-test-old']['type'] );
		$this->assertSame( 'https://example.test/seo-import-test-post-a/', $stored['/seo-import-test-old']['destination'] );
		$this->assertTrue( $stored['^/seo-import-test-regex/(.*)$']['regex'] );
		$this->assertSame( 410, $stored['/seo-import-test-gone']['type'] );
		$this->assertSame( 301, $stored['/seo-import-test-post-d']['type'] );
	}

	public function test_skips_disabled_and_logged_in_only_redirects(): void {
		$result = ( new RedirectsMigrator() )->migrate( $this->entries );

		$sources = array_column( $this->options['lw_seo_redirects'], 'source' );
		$this->assertNotContains( '/seo-import-test-disabled', $sources );
		$this->assertNotContains( '/seo-import-test-members', $sources );
		$this->assertSame( 2, $result['skipped'] );
	}

	public function test_second_run_adds_no_duplicates(): void {
		( new RedirectsMigrator() )->migrate( $this->entries );

		$second = ( new RedirectsMigrator() )->migrate( $this->entries );

		$this->assertSame( 0, $second['migrated'] );
		$this->assertSame( 4, $second['skipped_already_present'] );
		$this->assertCount( 4, $this->options['lw_seo_redirects'] );
	}

	public function test_dry_run_writes_nothing(): void {
		$result = ( new RedirectsMigrator( true ) )->migrate( $this->entries );

		$this->assertSame( 4, $result['migrated'] );
		$this->assertSame( [], $this->writes );
	}
}
