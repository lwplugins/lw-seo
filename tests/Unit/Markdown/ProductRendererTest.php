<?php
/**
 * ProductRenderer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use Brain\Monkey\Functions;
use Bricks\Database;
use Bricks\Helpers;
use LightweightPlugins\SEO\Markdown\ProductRenderer;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class ProductRendererTest extends MonkeyTestCase {

	protected function tearDown(): void {
		Database::test_reset();
		parent::tearDown();
	}

	/**
	 * The product title is plain text in the heading.
	 */
	public function test_body_heading_escapes_title(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wc_get_product' )->justReturn( false );
		Functions\when( 'get_the_title' )->justReturn( '<b>Mug</b> [x](javascript:alert(1))' );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );

		$post = new \WP_Post(
			[
				'ID'           => 9,
				'post_excerpt' => '',
				'post_content' => '',
			]
		);

		$this->assertSame( "# Mug \\[x\\](javascript:alert(1))\n\n", ( new ProductRenderer( $post ) )->body() );
	}

	public function test_body_describes_a_bricks_product_with_its_bricks_content(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wc_get_product' )->justReturn( false );
		Functions\when( 'get_the_title' )->justReturn( 'Mug' );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );
		Helpers::$test_bricks_posts = [ 9 ];
		Database::$test_content[9]  = [ [ 'id' => 'abc123', 'settings' => [ 'text' => 'Built with Bricks' ] ] ];

		$post = new \WP_Post(
			[
				'ID'           => 9,
				'post_excerpt' => '',
				'post_content' => '',
			]
		);

		$this->assertSame( "# Mug\n\n## Description\n\nBuilt with Bricks\n\n", ( new ProductRenderer( $post ) )->body() );
	}

	/**
	 * @return array<string, array{0: string, 1: array<int, string>, 2: string}>
	 */
	public static function attribute_row_provider(): array {
		return [
			'plain values unchanged' => [ 'Color', [ 'Red', 'Blue' ], "| Color | Red, Blue |\n" ],
			'raw tag in a value'     => [ 'Color', [ '<img src=x onerror=alert(1)>' ], "| Color |  |\n" ],
			'decoded tag in a value' => [ 'Color', [ '&lt;img src=x onerror=alert(1)&gt;' ], "| Color | \\<img src=x onerror=alert(1)\\> |\n" ],
			'link syntax in a value' => [ 'Color', [ '[click](javascript:alert(1))' ], "| Color | \\[click\\](javascript:alert(1)) |\n" ],
			'autolink in the label'  => [ '&lt;javascript:alert(1)&gt;', [ 'x' ], "| \\<javascript:alert(1)\\> | x |\n" ],
			'pipe cannot add a cell' => [ 'a|b', [ 'c|d', 'e' ], "| a\\|b | c\\|d, e |\n" ],
		];
	}

	/**
	 * Attribute labels, option values and term names are shop-manager text:
	 * they become inert Markdown and can't break out of their table cell.
	 *
	 * @dataProvider attribute_row_provider
	 *
	 * @param string             $label    Attribute label.
	 * @param array<int, string> $values   Attribute values.
	 * @param string             $expected Table row.
	 */
	public function test_attribute_row_escapes_label_and_values( string $label, array $values, string $expected ): void {
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );

		$this->assertSame( $expected, ProductRenderer::attribute_row( $label, $values ) );
	}

	/**
	 * The add-to-cart text is plain text and its URL a filtered destination.
	 */
	public function test_cart_link_escapes_text_and_url(): void {
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );

		$this->assertSame(
			"**[Buy \\[x\\](javascript:alert(1))](https://x.test/p/?add-to-cart=9&a=%60%28%29)**\n\n",
			ProductRenderer::cart_link( 'Buy [x](javascript:alert(1))', 'https://x.test/p/?add-to-cart=9&a=`()' )
		);
	}
}
