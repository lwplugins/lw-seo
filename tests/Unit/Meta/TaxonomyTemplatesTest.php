<?php
/**
 * TaxonomyTemplates unit tests.
 *
 * Issue #26: only categories and tags had a title template and a noindex
 * setting, so WooCommerce product categories, tags and attributes kept the
 * WordPress title, and the settings screen dropped any value set for them.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Meta;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Admin\SettingsSanitizer;
use LightweightPlugins\SEO\Meta\TaxonomyTemplates;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Meta\TaxonomyTemplates
 */
final class TaxonomyTemplatesTest extends MonkeyTestCase {

	protected function tearDown(): void {
		TaxonomyTemplates::set( [] );
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_categories_and_tags_keep_their_own_defaults(): void {
		TaxonomyTemplates::set( [ 'category', 'post_tag', 'product_cat' ] );

		$this->assertSame( [ 'product_cat' ], TaxonomyTemplates::taxonomies() );
	}

	public function test_every_other_public_taxonomy_gets_a_title_template_and_a_noindex_default(): void {
		TaxonomyTemplates::set( [ 'product_cat', 'pa_gyarto' ] );

		$this->assertSame(
			[
				'title_product_cat'   => '%%term_title%% %%sep%% %%sitename%%',
				'noindex_product_cat' => false,
				'title_pa_gyarto'     => '%%term_title%% %%sep%% %%sitename%%',
				'noindex_pa_gyarto'   => false,
			],
			TaxonomyTemplates::defaults()
		);
	}

	public function test_the_taxonomy_defaults_are_option_defaults(): void {
		TaxonomyTemplates::set( [ 'product_cat' ] );

		$defaults = Options::get_defaults();

		$this->assertSame( '%%term_title%% %%sep%% %%sitename%%', $defaults['title_product_cat'] );
		$this->assertFalse( $defaults['noindex_product_cat'] );
	}

	public function test_a_saved_taxonomy_template_survives_the_settings_sanitizer(): void {
		Functions\when( 'sanitize_text_field' )->alias( static fn( $value ) => trim( (string) $value ) );
		TaxonomyTemplates::set( [ 'pa_gyarto' ] );

		$sanitized = SettingsSanitizer::sanitize(
			[ 'title_pa_gyarto' => 'Gyártó: %%term_title%% %%sep%% %%sitename%%' ],
			Options::get_defaults()
		);

		$this->assertSame( 'Gyártó: %%term_title%% %%sep%% %%sitename%%', $sanitized['title_pa_gyarto'] );
	}

	public function test_load_reads_the_public_taxonomies(): void {
		Functions\when( 'get_taxonomies' )->justReturn(
			[
				'category'    => (object) [ 'labels' => (object) [ 'name' => 'Categories' ] ],
				'product_tag' => (object) [ 'labels' => (object) [ 'name' => 'Product tags' ] ],
			]
		);
		Functions\when( 'is_taxonomy_viewable' )->justReturn( true );

		TaxonomyTemplates::load();

		$this->assertSame( [ 'product_tag' ], TaxonomyTemplates::taxonomies() );
	}
}
