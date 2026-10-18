<?php
/**
 * SeoService::set_options() unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\SiteManager;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\SiteManager\SeoAbilities;
use LightweightPlugins\SEO\Integrations\SiteManager\SeoService;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Integrations\SiteManager\SeoService
 * @covers \LightweightPlugins\SEO\Integrations\SiteManager\SeoAbilities
 */
final class SeoServiceOptionsTest extends MonkeyTestCase {

	/**
	 * Option value written by update_option(), null until a write.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $written = null;

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		Functions\stubTranslationFunctions();
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ): string => is_string( $v ) ? trim( strip_tags( $v ) ) : '' );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( $v ): string => trim( strip_tags( (string) $v ) ) );
		Functions\when( 'esc_url_raw' )->alias( static fn( $v ): string => (string) $v );
		Functions\when( 'sanitize_key' )->alias( static fn( $v ): string => (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ) );
		Functions\when( 'absint' )->alias( static fn( $v ): int => abs( (int) $v ) );
		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ): array => array_merge( $defaults, (array) $args ) );
		Functions\when( 'get_option' )->alias( fn(): array => $this->written ?? [ 'sitemap_enabled' => true ] );
		Functions\when( 'update_option' )->alias(
			function ( string $name, array $value ): bool {
				$this->written = $value;
				return true;
			}
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_set_options_saves_only_the_submitted_keys_sanitized(): void {
		$result = SeoService::set_options( [ 'options' => [ 'separator' => '<b>|</b>' ] ] );

		$this->assertIsArray( $result );
		$this->assertSame( '|', $this->written['separator'] );
		$this->assertTrue( $this->written['sitemap_enabled'] );
		$this->assertSame( [ 'separator' ], $result['updated'] );
		$this->assertSame( '|', $result['options']['separator'] );
	}

	public function test_set_options_skips_unknown_keys(): void {
		$result = SeoService::set_options(
			[
				'options' => [
					'separator'   => '|',
					'not_an_lw_x' => 'y',
				],
			]
		);

		$this->assertIsArray( $result );
		$this->assertSame( [ 'not_an_lw_x' ], $result['skipped'] );
		$this->assertArrayNotHasKey( 'not_an_lw_x', $this->written );
	}

	/**
	 * @dataProvider provide_inputs_without_known_keys
	 *
	 * @param array<string, mixed> $input Ability input.
	 */
	public function test_set_options_rejects_input_without_known_keys( array $input ): void {
		$result = SeoService::set_options( $input );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertNull( $this->written );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function provide_inputs_without_known_keys(): array {
		return [
			'no options'     => [ [] ],
			'empty options'  => [ [ 'options' => [] ] ],
			'only unknown'   => [ [ 'options' => [ 'nope' => 1 ] ] ],
			'not an object'  => [ [ 'options' => 'separator=|' ] ],
		];
	}

	public function test_set_options_ability_requires_manage_options_and_is_a_write(): void {
		$registered = [];
		Functions\when( 'wp_register_ability' )->alias(
			static function ( string $name, array $args ) use ( &$registered ): void {
				$registered[ $name ] = $args;
			}
		);
		$permissions = new class() {
			public function callback( string $check ): string {
				return $check;
			}
		};

		SeoAbilities::register( $permissions );

		$this->assertSame( 'can_manage_options', $registered['lw-seo/set-options']['permission_callback'] );
		$this->assertFalse( $registered['lw-seo/set-options']['meta']['annotations']['readonly'] );
	}
}
