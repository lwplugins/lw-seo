<?php
/**
 * Tests for the All in One SEO settings importer, on real stored settings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\AIOSEO;

use LightweightPlugins\SEO\Migration\AIOSEO\OptionsMigrator;
use LightweightPlugins\SEO\Migration\AIOSEO\SettingsReader;
use LightweightPlugins\SEO\Migration\AIOSEO\SmartTagConverter;
use LightweightPlugins\SEO\Migration\Support\VariableLog;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\Migration\MigrationStoreTrait;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\AIOSEO\OptionsMigrator
 * @covers \LightweightPlugins\SEO\Migration\AIOSEO\SettingsReader
 * @covers \LightweightPlugins\SEO\Migration\AIOSEO\SocialSettings
 * @covers \LightweightPlugins\SEO\Migration\AIOSEO\SitemapSettings
 */
final class OptionsMigratorTest extends MonkeyTestCase {

	use MigrationStoreTrait;

	/**
	 * Fixture settings.
	 *
	 * @var array<string, mixed>
	 */
	private array $settings = [];

	protected function setUp(): void {
		parent::setUp();
		$this->stub_store();
		\Brain\Monkey\Functions\when( 'get_bloginfo' )->alias( static fn( $show ) => 'name' === $show ? 'Croco2' : 'Demo site' );
		$this->settings                        = $this->fixture( 'aioseo' )['options'];
		$this->options[ Options::OPTION_NAME ] = Options::get_defaults();
	}

	/**
	 * Reader over the fixture, stored as JSON like All in One SEO stores it.
	 *
	 * @return SettingsReader
	 */
	private function reader(): SettingsReader {
		return new SettingsReader(
			wp_json_encode_stub( $this->settings['aioseo_options'] ),
			wp_json_encode_stub( $this->settings['aioseo_options_dynamic'] ),
			array_merge( $this->settings['aioseo_options_localized'], $this->settings['aioseo_options_dynamic_localized'] )
		);
	}

	/**
	 * Run the importer.
	 *
	 * @param bool $dry_run Dry run.
	 * @return array{count: int, details: array<string>}
	 */
	private function run_import( bool $dry_run = false ): array {
		return ( new OptionsMigrator( $this->reader(), new SmartTagConverter( new VariableLog() ), $dry_run ) )->migrate();
	}

	/**
	 * Saved LW SEO options.
	 *
	 * @return array<string, mixed>
	 */
	private function lw(): array {
		return $this->options[ Options::OPTION_NAME ];
	}

	public function test_imports_separator_and_templates(): void {
		$this->run_import();

		$lw = $this->lw();
		$this->assertSame( '»', $lw['separator'] );
		// LW SEO shows the home description as saved: variables are filled in.
		$this->assertSame( 'AIOSEO home description » Croco2', $lw['desc_home'] );
		$this->assertSame( 'Search %%searchphrase%% %%sep%% %%sitename%%', $lw['title_search'] );
		$this->assertSame( '%%title%% %%sep%% %%sitename%%', $lw['title_post'] );
	}

	public function test_localized_template_wins_over_the_stored_one(): void {
		$this->settings['aioseo_options_dynamic_localized']['searchAppearance_postTypes_post_title'] = '#post_title only';

		$this->run_import();

		$this->assertSame( '%%title%% only', $this->lw()['title_post'] );
	}

	public function test_resolves_noindex_like_all_in_one_seo(): void {
		$this->run_import();

		$lw = $this->lw();
		$this->assertTrue( $lw['noindex_page'] );
		$this->assertTrue( $lw['noindex_author'] );
		$this->assertFalse( $lw['noindex_post'] );
		$this->assertFalse( $lw['noindex_date'] );
	}

	public function test_group_on_default_inherits_the_global_robots_setting(): void {
		$reader = new SettingsReader(
			[
				'searchAppearance' => [
					'advanced' => [
						'globalRobotsMeta' => [
							'default' => false,
							'noindex' => true,
						],
					],
					'archives' => [
						'date' => [
							'show'     => true,
							'advanced' => [ 'robotsMeta' => [ 'default' => true ] ],
						],
					],
				],
			],
			[ 'searchAppearance' => [ 'postTypes' => [ 'post' => [ 'show' => false ] ] ] ]
		);

		$this->assertTrue( $reader->noindex( 'searchAppearance.archives.date', false ) );
		$this->assertTrue( $reader->noindex( 'searchAppearance.postTypes.post' ) );
		$this->assertNull( $reader->noindex( 'searchAppearance.postTypes.page' ) );
	}

	public function test_imports_social_profiles_knowledge_graph_and_card_type(): void {
		$this->run_import();

		$lw = $this->lw();
		$this->assertSame( 'https://www.facebook.com/crocoaioseo', $lw['social_facebook'] );
		$this->assertSame( 'https://x.com/crocoaioseo', $lw['social_twitter'] );
		$this->assertSame( 'https://www.youtube.com/@crocoaioseo', $lw['social_youtube'] );
		$this->assertStringEndsWith( '.webp', $lw['default_og_image'] );
		$this->assertSame( 'summary', $lw['twitter_card_type'] );
		$this->assertSame( 'Croco Demo AIOSEO Kft.', $lw['knowledge_name'] );
		$this->assertStringEndsWith( '.webp', $lw['knowledge_logo'] );
	}

	public function test_imports_sitemap_inclusion(): void {
		$this->run_import();

		$lw = $this->lw();
		$this->assertTrue( $lw['sitemap_posts'] );
		$this->assertFalse( $lw['sitemap_products'] );
		$this->assertTrue( $lw['sitemap_tags'] );
	}

	public function test_customised_lw_seo_options_are_kept_and_second_run_counts_zero(): void {
		$this->options[ Options::OPTION_NAME ]['separator'] = '|';

		$this->run_import();

		$this->assertSame( '|', $this->lw()['separator'] );
		$this->assertSame( 0, $this->run_import()['count'] );
	}

	public function test_dry_run_saves_nothing_and_source_is_untouched(): void {
		$result = $this->run_import( true );
		$this->assertGreaterThan( 0, $result['count'] );
		$this->assertSame( [], $this->writes );

		$this->run_import();
		$this->assertSame( [ 'option:lw_seo_options' ], $this->writes );
	}

	public function test_missing_settings_import_nothing(): void {
		$result = ( new OptionsMigrator( new SettingsReader( '', '' ), new SmartTagConverter( new VariableLog() ) ) )->migrate();

		$this->assertSame( 0, $result['count'] );
		$this->assertSame( [], $this->writes );
	}
}

/**
 * JSON-encode a fixture tree the way All in One SEO stores it.
 *
 * @param mixed $value Tree.
 * @return string
 */
function wp_json_encode_stub( mixed $value ): string {
	return (string) json_encode( $value );
}
