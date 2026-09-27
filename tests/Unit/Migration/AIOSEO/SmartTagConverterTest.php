<?php
/**
 * Tests for the All in One SEO smart tag converter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\AIOSEO;

use LightweightPlugins\SEO\Migration\AIOSEO\SmartTagConverter;
use LightweightPlugins\SEO\Migration\Support\VariableLog;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\AIOSEO\SmartTagConverter
 * @covers \LightweightPlugins\SEO\Migration\Support\TemplateTidy
 */
final class SmartTagConverterTest extends MonkeyTestCase {

	/**
	 * @dataProvider provide_templates
	 *
	 * @param string $source   All in One SEO template.
	 * @param string $expected LW SEO template.
	 */
	public function test_converts_smart_tags( string $source, string $expected ): void {
		$this->assertSame( $expected, ( new SmartTagConverter( new VariableLog() ) )->convert( $source ) );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function provide_templates(): array {
		return [
			'default post title'     => [ '#post_title #separator_sa #site_title', '%%title%% %%sep%% %%sitename%%' ],
			'home'                   => [ '#site_title #separator_sa #tagline', '%%sitename%% %%sep%% %%sitedesc%%' ],
			'taxonomy'               => [ '#taxonomy_title #separator_sa #site_title', '%%term_title%% %%sep%% %%sitename%%' ],
			'search'                 => [ 'Search #search_term #separator_sa #site_title', 'Search %%searchphrase%% %%sep%% %%sitename%%' ],
			'dates'                  => [ '#current_year #current_month #current_day #current_date', '%%currentyear%% %%currentmonth%% %%currentday%% %%currentdate%%' ],
			'case insensitive'       => [ '#Post_Title', '%%title%%' ],
			'longest tag wins'       => [ '#post_excerpt_only', '%%excerpt%%' ],
			'suffix tags removed'    => [ 'G #post_title #custom_field-color #tax_name-genre #separator_sa #site_title', 'G %%title%% %%sep%% %%sitename%%' ],
			'unsupported removed'    => [ '#post_title #post_year #separator_sa #site_title', '%%title%% %%sep%% %%sitename%%' ],
			'hashtags stay literal'  => [ 'Top #1 tips #wordpress', 'Top #1 tips #wordpress' ],
			'tag glued to a word'    => [ '#post_titles', '#post_titles' ],
			'dangling separator'     => [ '#author_link_alt #separator_sa #site_title', '%%sitename%%' ],
		];
	}

	public function test_records_removed_tags(): void {
		$log = new VariableLog();

		( new SmartTagConverter( $log ) )->convert( '#post_title #custom_field-color #author_first_name #author_first_name' );

		$this->assertSame(
			[
				'#custom_field-color' => 1,
				'#author_first_name'  => 2,
			],
			$log->all()
		);
	}
}
