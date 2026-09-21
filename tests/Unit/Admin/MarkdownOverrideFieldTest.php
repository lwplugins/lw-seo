<?php
/**
 * MarkdownOverrideField unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Admin\MarkdownOverrideField;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class MarkdownOverrideFieldTest extends MonkeyTestCase {

	/**
	 * The override is served verbatim, so it needs unfiltered_html.
	 */
	public function test_override_may_be_set_with_unfiltered_html(): void {
		Functions\expect( 'current_user_can' )->once()->with( 'unfiltered_html' )->andReturn( true );

		$this->assertTrue( MarkdownOverrideField::may_set( 'markdown_content' ) );
	}

	/**
	 * Without unfiltered_html the save paths must skip the override.
	 */
	public function test_override_may_not_be_set_without_unfiltered_html(): void {
		Functions\expect( 'current_user_can' )->once()->with( 'unfiltered_html' )->andReturn( false );

		$this->assertFalse( MarkdownOverrideField::may_set( 'markdown_content' ) );
	}

	/**
	 * Every other SEO field keeps its existing rule (edit_post / edit_term).
	 */
	public function test_other_fields_are_unaffected(): void {
		Functions\expect( 'current_user_can' )->never();

		$this->assertTrue( MarkdownOverrideField::may_set( 'title' ) );
	}
}
