<?php
/**
 * QueryArgs unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Content;

use Brain\Monkey\Filters;
use LightweightPlugins\SEO\Content\QueryArgs;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class QueryArgsTest extends MonkeyTestCase {

	public function test_returns_the_filtered_arguments(): void {
		Filters\expectApplied( 'lw_seo_query_args' )->once()->with( [ 'post_type' => 'page' ], 'sitemap_posts' )->andReturn(
			[
				'post_type' => 'page',
				'lang'      => '',
			]
		);

		$this->assertSame(
			[
				'post_type' => 'page',
				'lang'      => '',
			],
			QueryArgs::filter( [ 'post_type' => 'page' ], QueryArgs::SITEMAP_POSTS )
		);
	}

	public function test_keeps_the_arguments_when_a_filter_returns_a_non_array(): void {
		Filters\expectApplied( 'lw_seo_query_args' )->andReturn( 'broken' );

		$this->assertSame( [ 'post_type' => 'page' ], QueryArgs::filter( [ 'post_type' => 'page' ], QueryArgs::LLMS_POSTS ) );
	}
}
