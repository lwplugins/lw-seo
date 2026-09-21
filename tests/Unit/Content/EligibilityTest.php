<?php
/**
 * Eligibility unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Content;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Content\Eligibility;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class EligibilityTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->stub_options();
		Functions\when( 'is_post_type_viewable' )->alias( static fn( string $type ): bool => 'internal' !== $type );
		Functions\when( 'get_post_meta' )->justReturn( '' );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * @param array<string, mixed> $props Overrides.
	 */
	private function post( array $props = [] ): \WP_Post {
		return new \WP_Post(
			array_merge(
				[
					'ID'            => 7,
					'post_status'   => 'publish',
					'post_password' => '',
					'post_type'     => 'page',
				],
				$props
			)
		);
	}

	/**
	 * @return array<string, array{0: array<string, mixed>, 1: bool}>
	 */
	public static function post_provider(): array {
		return [
			'published page'         => [ [], true ],
			'draft'                  => [ [ 'post_status' => 'draft' ], false ],
			'private'                => [ [ 'post_status' => 'private' ], false ],
			'password protected'     => [ [ 'post_password' => 'secret' ], false ],
			'non-viewable post type' => [ [ 'post_type' => 'internal' ], false ],
		];
	}

	/**
	 * @dataProvider post_provider
	 *
	 * @param array<string, mixed> $props    Post overrides.
	 * @param bool                 $expected Expected eligibility.
	 */
	public function test_is_post_eligible_checks_status_password_and_type( array $props, bool $expected ): void {
		$this->assertSame( $expected, Eligibility::is_post_eligible( $this->post( $props ) ) );
	}

	public function test_type_level_noindex_makes_post_ineligible(): void {
		$this->stub_options( [ 'noindex_page' => true ] );

		$this->assertFalse( Eligibility::is_post_eligible( $this->post() ) );
	}

	public function test_post_level_noindex_makes_post_ineligible(): void {
		Functions\when( 'get_post_meta' )->alias( static fn( int $id, string $key ): string => '_lw_seo_noindex' === $key ? '1' : '' );

		$this->assertFalse( Eligibility::is_post_eligible( $this->post() ) );
	}

	public function test_filter_can_exclude_an_eligible_post(): void {
		Filters\expectApplied( 'lw_seo_post_is_eligible' )->once()->andReturn( false );

		$this->assertFalse( Eligibility::is_post_eligible( $this->post() ) );
	}

	public function test_filter_is_not_consulted_for_ineligible_posts(): void {
		Filters\expectApplied( 'lw_seo_post_is_eligible' )->never();

		$this->assertFalse( Eligibility::is_post_eligible( $this->post( [ 'post_status' => 'draft' ] ) ) );
	}

	public function test_custom_post_type_without_setting_is_indexable(): void {
		$this->assertTrue( Eligibility::is_type_indexable( 'case_study' ) );
	}
}
