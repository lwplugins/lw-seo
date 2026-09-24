<?php
/**
 * MetaFields unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Editor;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Editor\MetaFields;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class MetaFieldsTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ): string => trim( (string) preg_replace( '/\s+/', ' ', strip_tags( (string) $v ) ) ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( $v ): string => trim( strip_tags( (string) $v ) ) );
		Functions\when( 'esc_url_raw' )->alias( static fn( $v ): string => str_starts_with( (string) $v, 'javascript:' ) ? '' : (string) $v );
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: mixed, 3: string}>
	 */
	public static function sanitize_provider(): array {
		return [
			'text strips tags and newlines' => [ 'title', MetaFields::POST['title'], "<b>Hi</b>\nthere", 'Hi there' ],
			'textarea keeps newlines'       => [ 'description', MetaFields::POST['description'], "Line 1\nLine 2", "Line 1\nLine 2" ],
			'url cleaned'                   => [ 'canonical', MetaFields::POST['canonical'], 'javascript:alert(1)', '' ],
			'url kept'                      => [ 'og_image', MetaFields::POST['og_image'], 'https://x.test/a.jpg', 'https://x.test/a.jpg' ],
			'flag true'                     => [ 'noindex', MetaFields::POST['noindex'], true, '1' ],
			'flag false deletes'            => [ 'noindex', MetaFields::POST['noindex'], false, '' ],
			'flag from checkbox'            => [ 'nofollow', MetaFields::POST['nofollow'], '1', '1' ],
			'signal kept'                   => [ 'ai_train', MetaFields::POST['ai_train'], 'NO', 'no' ],
			'signal default deletes'        => [ 'search', MetaFields::POST['search'], 'default', '' ],
			'array for text is empty'       => [ 'title', MetaFields::POST['title'], [ 'x' ], '' ],
			'bool for text is empty'        => [ 'og_title', MetaFields::POST['og_title'], true, '' ],
		];
	}

	/**
	 * @dataProvider sanitize_provider
	 *
	 * @param string $field    Field (for the case label only).
	 * @param string $kind     Field kind.
	 * @param mixed  $value    Submitted value.
	 * @param string $expected Stored value.
	 */
	public function test_sanitize_cleans_by_field_kind( string $field, string $kind, $value, string $expected ): void {
		$this->assertSame( $expected, MetaFields::sanitize( $kind, $value ), $field );
	}

	public function test_read_types_values_for_the_editor(): void {
		$stored = [
			'title'    => 'Custom',
			'noindex'  => '1',
			'og_image' => [ 'url' => 'https://x.test/og.jpg' ],
		];

		$values = MetaFields::read( MetaFields::TERM, static fn( string $field ) => $stored[ $field ] ?? '' );

		$this->assertSame(
			[
				'title'            => 'Custom',
				'description'      => '',
				'noindex'          => true,
				'og_title'         => '',
				'og_description'   => '',
				'og_image'         => 'https://x.test/og.jpg',
				'ai_train'         => '',
				'ai_input'         => '',
				'search'           => '',
				'markdown_content' => '',
			],
			$values
		);
	}
}
