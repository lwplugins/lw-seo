<?php
/**
 * Bricks dynamic tag conversion unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Compat;

use LightweightPlugins\SEO\Compat\BricksTags;
use PHPUnit\Framework\TestCase;

final class BricksTagsTest extends TestCase {

	public function test_plain_text_passes_through_both_ways(): void {
		$this->assertSame( 'Öko reklámajándék | Brand', BricksTags::to_lw( 'Öko reklámajándék | Brand' ) );
		$this->assertSame( 'Öko reklámajándék | Brand', BricksTags::to_bricks( 'Öko reklámajándék | Brand' ) );
	}

	public function test_known_bricks_tags_become_lw_variables(): void {
		$this->assertSame( '%%title%% - %%sitename%%', BricksTags::to_lw( '{post_title} - {site_title}' ) );
		$this->assertSame( '%%excerpt%% %%sitedesc%% %%author%% %%id%%', BricksTags::to_lw( '{post_excerpt} {site_tagline} {author_name} {post_id}' ) );
	}

	public function test_known_lw_variables_become_bricks_tags(): void {
		$this->assertSame( '{post_title} - {site_title}', BricksTags::to_bricks( '%%title%% - %%sitename%%' ) );
		$this->assertSame( '{post_date} {post_modified} {current_date}', BricksTags::to_bricks( '%%date%% %%modified%% %%currentdate%%' ) );
	}

	public function test_unknown_or_filtered_bricks_tag_is_not_convertible(): void {
		$this->assertNull( BricksTags::to_lw( '{acf_subtitle} | Brand' ) );
		$this->assertNull( BricksTags::to_lw( '{post_title:10} | Brand' ) );
	}

	public function test_lw_variable_without_bricks_equivalent_is_not_convertible(): void {
		$this->assertNull( BricksTags::to_bricks( '%%title%% %%sep%% %%sitename%%' ) );
	}

	public function test_braces_that_are_not_tags_are_kept(): void {
		$this->assertSame( 'Set {A, B} offer', BricksTags::to_lw( 'Set {A, B} offer' ) );
	}
}
