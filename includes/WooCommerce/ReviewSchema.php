<?php
/**
 * WooCommerce product review schema.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\WooCommerce;

use LightweightPlugins\SEO\Options;

/**
 * Builds a Product's `review` list and its `aggregateRating` from the same
 * data, so reviews are never printed without their aggregate.
 */
final class ReviewSchema {

	/**
	 * Most recent reviews to include.
	 */
	private const MAX_REVIEWS = 5;

	/**
	 * The review properties of a Product schema.
	 *
	 * @param \WC_Product $product The product.
	 * @return array<string, mixed> `aggregateRating` and `review`, each only when there is one.
	 */
	public function properties( \WC_Product $product ): array {
		$properties = [];
		$reviews    = $this->reviews( $product );
		$rating     = $this->aggregate_rating( $product, ! empty( $reviews ) );

		if ( $rating ) {
			$properties['aggregateRating'] = $rating;
		}

		if ( ! empty( $reviews ) ) {
			$properties['review'] = $reviews;
		}

		return $properties;
	}

	/**
	 * The AggregateRating node.
	 *
	 * Comes from WooCommerce's cached rating (the _wc_rating_count and
	 * _wc_average_rating product meta). When that cache is stale (no count)
	 * while rated reviews are printed, the rating is recounted from the
	 * approved rated reviews, the same rows the review list comes from.
	 *
	 * @param \WC_Product $product     The product.
	 * @param bool        $has_reviews Whether rated reviews are printed.
	 * @return array<string, mixed>|null
	 */
	private function aggregate_rating( \WC_Product $product, bool $has_reviews ): ?array {
		$count   = $product->get_rating_count();
		$average = (float) $product->get_average_rating();

		if ( ( $count < 1 || $average <= 0.0 ) && $has_reviews ) {
			[ $count, $average ] = self::summarize( self::live_rating_counts( $product ) );
		}

		if ( $count < 1 || $average <= 0.0 ) {
			return null;
		}

		return [
			'@type'       => 'AggregateRating',
			'ratingValue' => $average,
			'ratingCount' => $count,
			'bestRating'  => 5,
			'worstRating' => 1,
		];
	}

	/**
	 * Uncached rating counts of the product's approved rated reviews.
	 *
	 * @param \WC_Product $product The product.
	 * @return array<int|string, int> Star rating => number of reviews.
	 */
	private static function live_rating_counts( \WC_Product $product ): array {
		if ( ! class_exists( 'WC_Comments' ) ) {
			return [];
		}

		return \WC_Comments::get_rating_counts_for_product( $product );
	}

	/**
	 * Rating count and average (two decimals, as WooCommerce stores it).
	 *
	 * @param array<int|string, int> $counts Star rating => number of reviews.
	 * @return array{0: int, 1: float}
	 */
	private static function summarize( array $counts ): array {
		$total = 0;
		$sum   = 0;

		foreach ( $counts as $rating => $number ) {
			$rating = (int) $rating;

			if ( $rating < 1 || $rating > 5 ) {
				continue;
			}

			$total += (int) $number;
			$sum   += $rating * (int) $number;
		}

		return [ $total, $total > 0 ? round( $sum / $total, 2 ) : 0.0 ];
	}

	/**
	 * Get the most recent approved reviews that carry a star rating.
	 *
	 * @param \WC_Product $product The product.
	 * @return array<array<string, mixed>>
	 */
	private function reviews( \WC_Product $product ): array {
		if ( ! Options::get( 'woo_schema_reviews', true ) ) {
			return [];
		}

		$reviews = [];

		$comments = get_comments(
			[
				'post_id' => $product->get_id(),
				'status'  => 'approve',
				'type'    => 'review',
				'number'  => self::MAX_REVIEWS,
			]
		);

		foreach ( $comments as $comment ) {
			$rating = get_comment_meta( (int) $comment->comment_ID, 'rating', true );

			if ( empty( $rating ) ) {
				continue;
			}

			$reviews[] = [
				'@type'         => 'Review',
				'author'        => [
					'@type' => 'Person',
					'name'  => $comment->comment_author,
				],
				'datePublished' => get_comment_date( 'c', $comment ),
				'reviewBody'    => $comment->comment_content,
				'reviewRating'  => [
					'@type'       => 'Rating',
					'ratingValue' => intval( $rating ),
					'bestRating'  => 5,
					'worstRating' => 1,
				],
			];
		}

		return $reviews;
	}
}
