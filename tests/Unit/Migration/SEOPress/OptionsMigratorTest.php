<?php
/**
 * Tests for the SEOPress settings importer, on real SEOPress settings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\SEOPress;

use LightweightPlugins\SEO\Migration\SEOPress\OptionsMigrator;
use LightweightPlugins\SEO\Migration\SEOPress\SocialSettings;
use LightweightPlugins\SEO\Migration\SEOPress\VariableConverter;
use LightweightPlugins\SEO\Migration\Support\VariableLog;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\Migration\MigrationStoreTrait;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\SEOPress\OptionsMigrator
 * @covers \LightweightPlugins\SEO\Migration\SEOPress\SocialSettings
 * @covers \LightweightPlugins\SEO\Migration\SEOPress\SitemapSettings
 */
final class OptionsMigratorTest extends MonkeyTestCase {

	use MigrationStoreTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->stub_store();
		\Brain\Monkey\Functions\when( 'get_bloginfo' )->alias( static fn( $show ) => 'name' === $show ? 'Croco2' : 'Demo site' );
		$this->options = $this->fixture( 'seopress' )['options'];

		// Like many sites: the whole LW SEO option array stored, defaults included.
		$this->options[ Options::OPTION_NAME ] = Options::get_defaults();
	}

	/**
	 * Run the importer.
	 *
	 * @param bool $dry_run Dry run.
	 * @return array{count: int, details: array<string>}
	 */
	private function run_import( bool $dry_run = false ): array {
		return ( new OptionsMigrator( new VariableConverter( new VariableLog() ), $dry_run ) )->migrate();
	}

	/**
	 * Saved LW SEO options.
	 *
	 * @return array<string, mixed>
	 */
	private function lw(): array {
		return $this->options[ Options::OPTION_NAME ];
	}

	public function test_imports_separator_and_title_templates(): void {
		$this->run_import();

		$lw = $this->lw();
		$this->assertSame( '|', $lw['separator'] );
		$this->assertSame( '%%sitename%%', $lw['title_home'] );
		// LW SEO shows the home description as saved: variables are filled in.
		$this->assertSame( 'Home description from SEOPress | Croco2', $lw['desc_home'] );
		$this->assertSame( '%%term_title%% %%pagenumber%% %%sep%% %%sitename%%', $lw['title_category'] );
		$this->assertSame( '%%title%% %%pagenumber%%', $lw['title_ptarchive_product'] );
		$this->assertSame( 'Search %%searchphrase%% %%sep%% %%sitename%%', $lw['title_search'] );
		$this->assertSame( 'Not found %%sep%% %%sitename%%', $lw['title_404'] );
	}

	public function test_imports_per_type_noindex(): void {
		$this->run_import();

		$lw = $this->lw();
		$this->assertTrue( $lw['noindex_page'] );
		$this->assertTrue( $lw['noindex_post_tag'] );
		$this->assertFalse( $lw['noindex_post'] );
		$this->assertFalse( $lw['noindex_author'] );
		$this->assertTrue( $lw['noindex_date'] );
	}

	public function test_imports_social_profiles_and_knowledge_graph(): void {
		$this->run_import();

		$lw = $this->lw();
		$this->assertSame( 'https://www.facebook.com/crocodemo', $lw['social_facebook'] );
		$this->assertSame( 'https://x.com/crocodemo', $lw['social_twitter'] );
		$this->assertSame( 'https://www.linkedin.com/company/crocodemo', $lw['social_linkedin'] );
		$this->assertStringEndsWith( '.webp', $lw['default_og_image'] );
		$this->assertSame( 'organization', $lw['knowledge_type'] );
		$this->assertSame( 'Croco Demo Kft.', $lw['knowledge_name'] );
		$this->assertSame( 'summary_large_image', $lw['twitter_card_type'] );
	}

	public function test_imports_sitemap_inclusion(): void {
		$this->run_import();

		$lw = $this->lw();
		$this->assertTrue( $lw['sitemap_posts'] );
		$this->assertTrue( $lw['sitemap_categories'] );
		$this->assertFalse( $lw['sitemap_product_cat'] );
	}

	public function test_customised_lw_seo_options_are_kept(): void {
		$this->options[ Options::OPTION_NAME ]['separator']      = '»';
		$this->options[ Options::OPTION_NAME ]['knowledge_name'] = 'My Company';

		$this->run_import();

		$this->assertSame( '»', $this->lw()['separator'] );
		$this->assertSame( 'My Company', $this->lw()['knowledge_name'] );
	}

	public function test_values_equal_to_the_current_ones_are_not_counted(): void {
		$result = $this->run_import();

		$this->assertNotContains( 'seopress_titles_single_titles[post][title] -> title_post', $result['details'] );
		$this->assertSame( 0, $this->run_import()['count'] );
	}

	public function test_dry_run_saves_nothing(): void {
		$result = $this->run_import( true );

		$this->assertGreaterThan( 0, $result['count'] );
		$this->assertSame( [], $this->writes );
	}

	public function test_leaves_seopress_settings_untouched(): void {
		$this->run_import();

		$this->assertSame( [ 'option:lw_seo_options' ], $this->writes );
	}

	/**
	 * @dataProvider provide_twitter_handles
	 *
	 * @param string|null $stored   Stored SEOPress value.
	 * @param string|null $expected LW SEO profile URL.
	 */
	public function test_twitter_handle_becomes_a_profile_url( ?string $stored, ?string $expected ): void {
		$this->assertSame( $expected, SocialSettings::twitter_url( $stored ) );
	}

	/**
	 * @return array<string, array{string|null, string|null}>
	 */
	public static function provide_twitter_handles(): array {
		return [
			'handle'      => [ '@crocodemo', 'https://x.com/crocodemo' ],
			'bare handle' => [ 'crocodemo', 'https://x.com/crocodemo' ],
			'url kept'    => [ 'https://twitter.com/crocodemo', 'https://twitter.com/crocodemo' ],
			'invalid'     => [ '@not a handle', null ],
			'empty'       => [ null, null ],
		];
	}
}
