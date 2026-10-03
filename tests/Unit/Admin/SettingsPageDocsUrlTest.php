<?php
/**
 * SettingsPage docs link unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Admin\SettingsPage;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Admin\SettingsPage::docs_url
 */
final class SettingsPageDocsUrlTest extends MonkeyTestCase {

	public function test_hungarian_admin_gets_the_hu_page(): void {
		Functions\when( 'get_user_locale' )->justReturn( 'hu_HU' );

		$this->assertSame( 'https://docs.lwplugins.com/hu/plugins/lw-seo', SettingsPage::docs_url() );
	}

	public function test_english_admin_gets_the_en_page(): void {
		Functions\when( 'get_user_locale' )->justReturn( 'en_US' );

		$this->assertSame( 'https://docs.lwplugins.com/en/plugins/lw-seo', SettingsPage::docs_url() );
	}

	public function test_other_locales_fall_back_to_en(): void {
		Functions\when( 'get_user_locale' )->justReturn( 'de_DE' );

		$this->assertSame( 'https://docs.lwplugins.com/en/plugins/lw-seo', SettingsPage::docs_url() );
	}
}
