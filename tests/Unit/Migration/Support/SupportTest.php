<?php
/**
 * Tests for the shared migration write helpers.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\Support;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Migration\Support\MetaWriter;
use LightweightPlugins\SEO\Migration\Support\OptionWriter;
use LightweightPlugins\SEO\Migration\Support\RedirectWriter;
use LightweightPlugins\SEO\Migration\Support\ResultTally;
use LightweightPlugins\SEO\Migration\Support\Separator;
use LightweightPlugins\SEO\Migration\Support\VariableLog;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\Support\MetaWriter
 * @covers \LightweightPlugins\SEO\Migration\Support\OptionWriter
 * @covers \LightweightPlugins\SEO\Migration\Support\RedirectWriter
 * @covers \LightweightPlugins\SEO\Migration\Support\ResultTally
 * @covers \LightweightPlugins\SEO\Migration\Support\Separator
 * @covers \LightweightPlugins\SEO\Migration\Support\VariableLog
 */
final class SupportTest extends MonkeyTestCase {

	/**
	 * In-memory meta store: "post:7:_lw_seo_title" => value.
	 *
	 * @var array<string, mixed>
	 */
	private array $meta = [];

	/**
	 * In-memory options.
	 *
	 * @var array<string, mixed>
	 */
	private array $options = [];

	protected function setUp(): void {
		parent::setUp();
		$this->meta    = [];
		$this->options = [];

		Functions\stubTranslationFunctions();
		foreach ( [ 'post', 'term' ] as $type ) {
			Functions\when( "get_{$type}_meta" )->alias( fn( $id, $key ) => $this->meta[ "$type:$id:$key" ] ?? '' );
			Functions\when( "update_{$type}_meta" )->alias(
				function ( $id, $key, $value ) use ( $type ): bool {
					$this->meta[ "$type:$id:$key" ] = $value;
					return true;
				}
			);
		}
		Functions\when( 'get_option' )->alias( fn( string $name, $fallback = false ) => $this->options[ $name ] ?? $fallback );
		Functions\when( 'update_option' )->alias(
			function ( string $name, $value ): bool {
				$this->options[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'home_url' )->justReturn( 'https://example.test' );
		Functions\when( 'current_time' )->justReturn( '2026-09-27 10:00:00' );
		Functions\when( 'wp_generate_uuid4' )->justReturn( 'uuid' );
	}

	public function test_meta_writer_fills_empty_slots_and_keeps_filled_ones(): void {
		$this->meta['post:7:_lw_seo_title'] = 'Mine';

		$result = ( new MetaWriter( 'post' ) )->write(
			7,
			[
				'title'       => 'Theirs',
				'description' => ' Desc ',
				'og_title'    => '',
			]
		);

		$this->assertSame( 'Mine', $this->meta['post:7:_lw_seo_title'] );
		$this->assertSame( 'Desc', $this->meta['post:7:_lw_seo_description'] );
		$this->assertArrayNotHasKey( 'post:7:_lw_seo_og_title', $this->meta );
		$this->assertSame(
			[
				'migrated'    => true,
				'target_full' => true,
			],
			$result
		);
	}

	public function test_meta_writer_rejects_arrays_and_writes_nothing_in_dry_run(): void {
		$writer = new MetaWriter( 'term', true );

		$result = $writer->write(
			3,
			[
				'og_image'    => [ 'check' => 'x' ],
				'description' => [ 'a' ],
				'title'       => 'T',
			]
		);

		$this->assertSame( [], $this->meta );
		$this->assertTrue( $result['migrated'] );
	}

	public function test_meta_writer_takes_the_url_from_an_image_array(): void {
		( new MetaWriter( 'post' ) )->write( 7, [ 'og_image' => [ 'url' => 'https://example.test/a.jpg' ] ] );

		$this->assertSame( 'https://example.test/a.jpg', $this->meta['post:7:_lw_seo_og_image'] );
	}

	public function test_result_tally_buckets_and_combines(): void {
		$tally = new ResultTally();
		$tally->add(
			ResultTally::combine(
				[
					[
						'migrated'    => false,
						'target_full' => true,
					],
					[
						'migrated'    => true,
						'target_full' => false,
					],
				]
			)
		);
		$tally->add(
			[
				'migrated'    => false,
				'target_full' => true,
			]
		);
		$tally->add(
			[
				'migrated'    => false,
				'target_full' => false,
			]
		);

		$this->assertSame(
			[
				'migrated'                => 1,
				'skipped_already_present' => 1,
				'skipped_no_data'         => 1,
			],
			$tally->to_array()
		);
	}

	public function test_option_writer_replaces_defaults_but_never_customised_values(): void {
		$defaults = [
			'title_post'   => '%%title%% %%sep%% %%sitename%%',
			'separator'    => '-',
			'noindex_date' => true,
		];
		$writer   = new OptionWriter(
			[
				'title_post'   => '%%title%% %%sep%% %%sitename%%',
				'separator'    => '|',
				'noindex_date' => true,
			],
			$defaults
		);

		$this->assertTrue( $writer->set( 'title_post', '%%title%%', 'single post' ) );
		$this->assertFalse( $writer->set( 'separator', '»', 'sep' ) );
		$this->assertTrue( $writer->set( 'noindex_date', false, 'date' ) );
		$this->assertTrue( $writer->set( 'desc_home', 'Home', 'home' ) );
		$this->assertFalse( $writer->set( 'social_twitter', '', 'twitter' ) );

		$this->assertSame( '%%title%%', $writer->options()['title_post'] );
		$this->assertSame( '|', $writer->options()['separator'] );
		$this->assertFalse( $writer->options()['noindex_date'] );
		$this->assertSame( 3, $writer->count() );
		$this->assertSame( 'single post -> title_post', $writer->details()[0] );
	}

	public function test_option_writer_does_not_count_unchanged_values(): void {
		$writer = new OptionWriter( [ 'separator' => '-' ], [ 'separator' => '-' ] );

		$this->assertFalse( $writer->set( 'separator', '-', 'sep' ) );
		$this->assertSame( 0, $writer->count() );
	}

	public function test_redirect_writer_adds_once_and_skips_known_sources(): void {
		$this->options['lw_seo_redirects'] = [
			[
				'source' => '/existing',
				'regex'  => false,
			],
		];
		$writer                            = new RedirectWriter();

		$this->assertSame( RedirectWriter::PRESENT, $writer->add( 'existing/', 'https://example.test/x', 301, false ) );
		$this->assertSame( RedirectWriter::ADDED, $writer->add( 'old-page', 'https://example.test/new', 302, false ) );
		$this->assertSame( RedirectWriter::PRESENT, $writer->add( '/old-page', 'https://example.test/new', 302, false ) );
		$this->assertSame( RedirectWriter::ADDED, $writer->add( 'gone', '', 410, false ) );
		$this->assertSame( RedirectWriter::INVALID, $writer->add( 'no-target', '', 301, false ) );
		$this->assertSame( RedirectWriter::INVALID, $writer->add( '/x', '/y', 308, false ) );
		$this->assertSame( RedirectWriter::INVALID, $writer->add( '^/(broken', '/y', 301, true ) );

		$this->assertCount( 3, $this->options['lw_seo_redirects'] );
		$this->assertSame( '/old-page', $this->options['lw_seo_redirects'][1]['source'] );
	}

	public function test_redirect_writer_dry_run_counts_without_writing(): void {
		$writer = new RedirectWriter( true );

		$this->assertSame( RedirectWriter::ADDED, $writer->add( '/a', '/b', 301, false ) );
		$this->assertSame( RedirectWriter::PRESENT, $writer->add( '/a/', '/b', 301, false ) );
		$this->assertArrayNotHasKey( 'lw_seo_redirects', $this->options );
	}

	public function test_separator_accepts_supported_characters_and_entities(): void {
		$this->assertSame( '-', Separator::normalize( '&#45;' ) );
		$this->assertSame( '»', Separator::normalize( '&raquo;' ) );
		$this->assertSame( '|', Separator::normalize( ' | ' ) );
		$this->assertNull( Separator::normalize( '~' ) );
		$this->assertNull( Separator::normalize( '' ) );
		$this->assertNull( Separator::normalize( [ '-' ] ) );
	}

	public function test_variable_log_reports_removed_variables(): void {
		$log = new VariableLog();
		$this->assertNull( $log->warning( 'SEOPress' ) );

		$log->removed( '%%wc_price%%' );
		$log->removed( '%%wc_price%%' );
		$warning = $log->warning( 'SEOPress' );

		$this->assertSame( [ '%%wc_price%%' => 2 ], $log->all() );
		$this->assertStringContainsString( '%%wc_price%% (2)', (string) $warning['message'] );
	}
}
