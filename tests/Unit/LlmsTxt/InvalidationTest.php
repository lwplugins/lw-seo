<?php
/**
 * llms.txt cache Invalidation unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\LlmsTxt;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\LlmsTxt\Invalidation;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class InvalidationTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * List posts and pages in llms.txt, leave the "product" type out.
	 */
	private function stub_listed_post_types(): void {
		$this->stub_options( [ 'llms_txt_post_types' => [ 'product' => false ] ] );
		Functions\when( 'get_post_types' )->justReturn(
			[
				'post'    => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Posts' ] ] ),
				'page'    => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Pages' ] ] ),
				'product' => new \WP_Post_Type( [ 'labels' => (object) [ 'name' => 'Products' ] ] ),
			]
		);
		Functions\when( 'is_post_type_viewable' )->justReturn( true );
		Functions\when( 'apply_filters' )->returnArg( 2 );
	}

	/**
	 * @return array<string, array{0: array<string, string>, 1: bool}>
	 */
	public static function saved_post_provider(): array {
		return [
			'published post of a listed type'   => [ [ 'post_type' => 'page', 'post_status' => 'publish' ], true ],
			'revision'                          => [ [ 'post_type' => 'revision', 'post_status' => 'inherit', 'post_name' => '12-revision-v1' ], false ],
			'autosave revision'                 => [ [ 'post_type' => 'revision', 'post_status' => 'inherit', 'post_name' => '12-autosave-v1' ], false ],
			'autosaved or saved draft'          => [ [ 'post_type' => 'post', 'post_status' => 'draft' ], false ],
			'auto-draft'                        => [ [ 'post_type' => 'post', 'post_status' => 'auto-draft' ], false ],
			'published post of unlisted type'   => [ [ 'post_type' => 'product', 'post_status' => 'publish' ], false ],
			'published post of non-public type' => [ [ 'post_type' => 'wp_navigation', 'post_status' => 'publish' ], false ],
		];
	}

	/**
	 * @dataProvider saved_post_provider
	 *
	 * @param array<string, string> $props    Post fields.
	 * @param bool                  $expected Whether the documents go stale.
	 */
	public function test_should_flush_for_post( array $props, bool $expected ): void {
		$this->stub_listed_post_types();

		$this->assertSame( $expected, Invalidation::should_flush_for_post( new \WP_Post( $props ) ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: string, 3: bool}>
	 */
	public static function transition_provider(): array {
		return [
			'scheduled post goes live'   => [ 'publish', 'future', 'post', true ],
			'published post unpublished' => [ 'draft', 'publish', 'post', true ],
			'published post trashed'     => [ 'trash', 'publish', 'page', true ],
			'published post updated'     => [ 'publish', 'publish', 'post', false ],
			'draft submitted for review' => [ 'pending', 'draft', 'post', false ],
			'unlisted type goes live'    => [ 'publish', 'draft', 'product', false ],
			'revision created'           => [ 'inherit', 'new', 'revision', false ],
		];
	}

	/**
	 * @dataProvider transition_provider
	 *
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @param string $post_type  Post type.
	 * @param bool   $expected   Whether the documents go stale.
	 */
	public function test_should_flush_for_transition( string $new_status, string $old_status, string $post_type, bool $expected ): void {
		$this->stub_listed_post_types();
		$post = new \WP_Post( [ 'post_type' => $post_type, 'post_status' => $new_status ] );

		$this->assertSame( $expected, Invalidation::should_flush_for_transition( $new_status, $old_status, $post ) );
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function meta_key_provider(): array {
		return [
			'SEO description'      => [ '_lw_seo_description', true ],
			'noindex flag'         => [ '_lw_seo_noindex', true ],
			'content signal'       => [ '_lw_seo_ai_input', true ],
			'edit lock'            => [ '_edit_lock', false ],
			'featured image'       => [ '_thumbnail_id', false ],
			'unprefixed lookalike' => [ 'lw_seo_description', false ],
		];
	}

	/**
	 * @dataProvider meta_key_provider
	 *
	 * @param string $meta_key Meta key.
	 * @param bool   $expected Whether it is one of the plugin's keys.
	 */
	public function test_is_seo_meta_key( string $meta_key, bool $expected ): void {
		$this->assertSame( $expected, Invalidation::is_seo_meta_key( $meta_key ) );
	}

	public function test_unlisted_public_type_still_announces_the_change(): void {
		$this->stub_listed_post_types();
		Actions\expectDone( 'lw_seo_content_changed' )->once();
		// The llms.txt cache is not touched: delete_transient is not stubbed.
		$post = new \WP_Post( [ 'post_type' => 'product', 'post_status' => 'publish' ] );

		Invalidation::flush_for_post( 5, $post );
		$this->addToAssertionCount( 1 ); // Brain Monkey verifies the expectation on teardown.
	}

	public function test_non_public_type_announces_nothing(): void {
		$this->stub_listed_post_types();
		Functions\when( 'is_post_type_viewable' )->justReturn( false );
		Actions\expectDone( 'lw_seo_content_changed' )->never();

		Invalidation::flush_for_post( 5, new \WP_Post( [ 'post_type' => 'wp_navigation', 'post_status' => 'publish' ] ) );
		$this->addToAssertionCount( 1 ); // Brain Monkey verifies the expectation on teardown.
	}

	public function test_unlisted_type_status_change_announces_the_change(): void {
		$this->stub_listed_post_types();
		Actions\expectDone( 'lw_seo_content_changed' )->once();

		Invalidation::flush_for_transition( 'publish', 'draft', new \WP_Post( [ 'post_type' => 'product' ] ) );
		$this->addToAssertionCount( 1 ); // Brain Monkey verifies the expectation on teardown.
	}

	public function test_listed_type_flushes_llms_cache_and_announces(): void {
		$this->stub_listed_post_types();
		Functions\expect( 'delete_transient' )->atLeast()->once();
		Actions\expectDone( 'lw_seo_content_changed' )->once();

		Invalidation::flush_for_post( 5, new \WP_Post( [ 'post_type' => 'page', 'post_status' => 'publish' ] ) );
		$this->addToAssertionCount( 1 ); // Brain Monkey verifies the expectation on teardown.
	}

	public function test_term_events_and_settings_announce_the_change(): void {
		$this->stub_options();
		Functions\when( 'delete_transient' )->justReturn( true );

		Invalidation::register();
		Invalidation::register_settings();

		foreach ( [ 'created_term', 'edited_term', 'delete_term', 'update_option_' . Options::OPTION_NAME ] as $hook ) {
			$this->assertNotFalse( has_action( $hook, [ Invalidation::class, 'flush' ] ), $hook );
		}
	}
}
