<?php
/**
 * ObjectText unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Content;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Content\ObjectText;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Content\ObjectText
 */
final class ObjectTextTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->stub_options( [ 'separator' => '|' ] );
		Functions\when( 'get_bloginfo' )->alias( static fn( string $show ): string => 'name' === $show ? 'Site' : 'Tagline' );
		Functions\when( 'get_the_title' )->justReturn( 'My post' );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_post_field_variables_are_filled_in_with_the_post(): void {
		Functions\when( 'get_post_meta' )->justReturn( 'Custom %%title%% %%sep%% %%sitename%%' );

		$this->assertSame( 'Custom My post | Site', ObjectText::post( new \WP_Post( [ 'ID' => 7 ] ), 'title' ) );
	}

	public function test_term_field_variables_are_filled_in_with_the_term(): void {
		Functions\when( 'get_term_meta' )->justReturn( '%%term_title%% products %%sep%% %%sitename%%' );

		$this->assertSame( 'Coffee products | Site', ObjectText::term( new \WP_Term( [ 'term_id' => 5, 'name' => 'Coffee' ] ), 'description' ) );
	}

	public function test_home_description_variables_are_filled_in(): void {
		$this->stub_options( [ 'desc_home' => '%%sitename%% %%sep%% %%sitedesc%%' ] );

		$this->assertSame( 'Site - Tagline', ObjectText::home_description() );
	}

	/**
	 * @dataProvider literal_provider
	 *
	 * @param string $text Text without LW SEO variables.
	 */
	public function test_text_without_variables_is_returned_unchanged( string $text ): void {
		$this->assertSame( $text, ObjectText::resolve( $text, new \WP_Post( [ 'ID' => 7 ] ) ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function literal_provider(): array {
		return [
			'empty'              => [ '' ],
			'plain text'         => [ 'Plain SEO title' ],
			'double spaces kept' => [ '  Spaced   title ' ],
			'percent signs'      => [ '50%% off, 100% sure' ],
			'upper-case token'   => [ 'Not %%A_VAR%% here' ],
		];
	}

	public function test_unknown_variables_are_removed_like_in_templates(): void {
		$this->assertSame( 'Shop My post', ObjectText::resolve( 'Shop %%unknown_var%% %%title%%', new \WP_Post( [ 'ID' => 7 ] ) ) );
	}

	public function test_non_string_meta_counts_as_empty(): void {
		Functions\when( 'get_post_meta' )->justReturn( [ 'x' ] );

		$this->assertSame( '', ObjectText::post( new \WP_Post( [ 'ID' => 7 ] ), 'title' ) );
	}
}
