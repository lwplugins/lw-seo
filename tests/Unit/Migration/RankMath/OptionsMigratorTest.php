<?php
/**
 * Rank Math settings importer: taxonomy and archive title templates.
 *
 * Issue #26: only the category and tag templates were imported, so a
 * WooCommerce shop lost its product category, tag and attribute templates
 * (e.g. the "Gyártó: " prefix of the brand pages) and the shop archive title.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\RankMath;

use LightweightPlugins\SEO\Meta\TaxonomyTemplates;
use LightweightPlugins\SEO\Migration\RankMath\OptionsMigrator;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\Migration\MigrationStoreTrait;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\RankMath\OptionsMigrator
 */
final class OptionsMigratorTest extends MonkeyTestCase {

	use MigrationStoreTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->stub_store();
		TaxonomyTemplates::set( [ 'product_cat', 'pa_gyarto' ] );

		$this->options['rank-math-options-titles'] = [
			'tax_product_cat_title'    => '%term% %page% %sep% %sitename%',
			'tax_product_cat_robots'   => [ 'noindex' ],
			'tax_pa_gyarto_title'      => 'Gyártó: %term% %page% %sep% %sitename%',
			'tax_unregistered_title'   => '%term% %sep% %sitename%',
			'pt_product_archive_title' => '%pt_plural% %page% %sep% %sitename%',
		];
	}

	protected function tearDown(): void {
		TaxonomyTemplates::set( [] );
		parent::tearDown();
	}

	/**
	 * Saved LW SEO options after the import.
	 *
	 * @return array<string, mixed>
	 */
	private function imported(): array {
		( new OptionsMigrator() )->migrate();

		return $this->options[ Options::OPTION_NAME ];
	}

	public function test_imports_the_title_template_of_every_public_taxonomy(): void {
		$lw = $this->imported();

		$this->assertSame( '%%term_title%% %%pagenumber%% %%sep%% %%sitename%%', $lw['title_product_cat'] );
		$this->assertSame( 'Gyártó: %%term_title%% %%pagenumber%% %%sep%% %%sitename%%', $lw['title_pa_gyarto'] );
	}

	public function test_imports_the_noindex_setting_of_a_taxonomy(): void {
		$this->assertTrue( $this->imported()['noindex_product_cat'] );
	}

	public function test_skips_taxonomies_that_are_not_registered(): void {
		$this->assertArrayNotHasKey( 'title_unregistered', $this->imported() );
	}

	public function test_imports_the_product_archive_title(): void {
		$this->assertSame( '%%title%% %%pagenumber%% %%sep%% %%sitename%%', $this->imported()['title_ptarchive_product'] );
	}

	public function test_overwrites_a_stored_default_but_keeps_a_customised_template(): void {
		$this->options[ Options::OPTION_NAME ] = [
			'title_product_cat' => TaxonomyTemplates::TITLE,
			'title_pa_gyarto'   => 'Saját: %%term_title%%',
		];

		$lw = $this->imported();

		$this->assertSame( '%%term_title%% %%pagenumber%% %%sep%% %%sitename%%', $lw['title_product_cat'] );
		$this->assertSame( 'Saját: %%term_title%%', $lw['title_pa_gyarto'] );
	}
}
