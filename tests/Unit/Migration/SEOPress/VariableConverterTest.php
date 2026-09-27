<?php
/**
 * Tests for the SEOPress variable converter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\SEOPress;

use LightweightPlugins\SEO\Migration\SEOPress\VariableConverter;
use LightweightPlugins\SEO\Migration\Support\VariableLog;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\SEOPress\VariableConverter
 * @covers \LightweightPlugins\SEO\Migration\Support\TemplateTidy
 */
final class VariableConverterTest extends MonkeyTestCase {

	/**
	 * @dataProvider provide_templates
	 *
	 * @param string $source   SEOPress template.
	 * @param string $expected LW SEO template.
	 */
	public function test_converts_seopress_variables( string $source, string $expected ): void {
		$this->assertSame( $expected, ( new VariableConverter( new VariableLog() ) )->convert( $source ) );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function provide_templates(): array {
		return [
			'default single title'      => [ '%%post_title%% %%sep%% %%sitetitle%%', '%%title%% %%sep%% %%sitename%%' ],
			'home'                      => [ '%%sitetitle%% %%sep%% %%tagline%%', '%%sitename%% %%sep%% %%sitedesc%%' ],
			'category with pagination'  => [ '%%_category_title%% %%current_pagination%% %%sep%% %%sitetitle%%', '%%term_title%% %%pagenumber%% %%sep%% %%sitename%%' ],
			'tag description'           => [ '%%tag_description%%', '%%term_description%%' ],
			'search'                    => [ 'Search %%search_keywords%% %%sep%% %%sitetitle%%', 'Search %%searchphrase%% %%sep%% %%sitename%%' ],
			'author archive'            => [ '%%post_author%% %%sep%% %%sitetitle%%', '%%author%% %%sep%% %%sitename%%' ],
			'aliases stay'              => [ '%%title%% %%excerpt%% %%date%%', '%%title%% %%excerpt%% %%date%%' ],
			'unsupported removed'       => [ 'Page C %%sep%% %%wc_single_price%% %%_cf_color%% %%sitetitle%%', 'Page C %%sep%% %%sitename%%' ],
			'dangling separator dropped' => [ '%%author_bio%% %%sep%% %%sitetitle%%', '%%sitename%%' ],
			'plain text'                => [ '  Just text ', 'Just text' ],
		];
	}

	public function test_records_removed_variables(): void {
		$log = new VariableLog();

		( new VariableConverter( $log ) )->convert( '%%wc_sku%% %%post_title%% %%wc_sku%% %%_ct_genre%%' );

		$this->assertSame(
			[
				'%%wc_sku%%'    => 2,
				'%%_ct_genre%%' => 1,
			],
			$log->all()
		);
	}

	public function test_non_string_becomes_empty(): void {
		$this->assertSame( '', ( new VariableConverter( new VariableLog() ) )->convert( [ 'x' ] ) );
	}
}
