<?php
/**
 * ReviewSchema unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\WooCommerce;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;
use LightweightPlugins\SEO\WooCommerce\ReviewSchema;

/**
 * @covers \LightweightPlugins\SEO\WooCommerce\ReviewSchema
 */
final class ReviewSchemaTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();

		$this->stub_options();
		Functions\when( 'get_comment_date' )->justReturn( '2026-09-01T10:00:00+00:00' );
		\WC_Comments::$test_rating_counts = [];
	}

	protected function tearDown(): void {
		\WC_Comments::$test_rating_counts = [];
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Make get_comments() return approved reviews with the given star ratings.
	 *
	 * @param array<int, int> $ratings Comment ID => star rating.
	 * @return void
	 */
	private function stub_reviews( array $ratings ): void {
		$comments = [];
		foreach ( array_keys( $ratings ) as $comment_id ) {
			$comments[] = new \WP_Comment(
				[
					'comment_ID'      => (string) $comment_id,
					'comment_author'  => 'Buyer ' . $comment_id,
					'comment_content' => 'Great',
				]
			);
		}

		Functions\when( 'get_comments' )->justReturn( $comments );
		Functions\when( 'get_comment_meta' )->alias( static fn( int $id ) => (string) ( $ratings[ $id ] ?? '' ) );
	}

	public function test_stale_rating_cache_is_recounted_from_the_rated_reviews(): void {
		$this->stub_reviews( [ 11 => 5, 12 => 4 ] );
		\WC_Comments::$test_rating_counts[42] = [
			'5' => 1,
			'4' => 1,
		];

		$properties = ( new ReviewSchema() )->properties( new \WC_Product( 42, 0, 0.0 ) );

		$this->assertCount( 2, $properties['review'] );
		$this->assertSame(
			[
				'@type'       => 'AggregateRating',
				'ratingValue' => 4.5,
				'ratingCount' => 2,
				'bestRating'  => 5,
				'worstRating' => 1,
			],
			$properties['aggregateRating'] ?? null
		);
	}

	public function test_cached_rating_is_used_when_woocommerce_has_it(): void {
		$this->stub_reviews( [ 11 => 5 ] );
		\WC_Comments::$test_rating_counts[42] = [ '1' => 9 ];

		$properties = ( new ReviewSchema() )->properties( new \WC_Product( 42, 3, 4.67 ) );

		$this->assertSame( 4.67, $properties['aggregateRating']['ratingValue'] );
		$this->assertSame( 3, $properties['aggregateRating']['ratingCount'] );
	}

	public function test_no_rating_and_no_reviews_without_rated_reviews(): void {
		$this->stub_reviews( [] );

		$this->assertSame( [], ( new ReviewSchema() )->properties( new \WC_Product( 42, 0, 0.0 ) ) );
	}

	public function test_unrated_reviews_are_left_out(): void {
		$this->stub_reviews( [ 11 => 0 ] );

		$this->assertSame( [], ( new ReviewSchema() )->properties( new \WC_Product( 42, 0, 0.0 ) ) );
	}
}
