<?php
/**
 * OfferValidity unit tests.
 *
 * Search Console reported "Missing field validFrom (in offers)" for the
 * merchant listings: the offers had an end date but no start date.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use LightweightPlugins\SEO\Integrations\WooCommerce\OfferValidity;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\OfferValidity
 */
final class OfferValidityTest extends MonkeyTestCase {

	private const CREATED = '2026-03-10T08:15:00+01:00';
	private const SALE    = '2026-10-01T00:00:00+02:00';

	/**
	 * @param array<string, mixed> $props Product props.
	 */
	private function product( array $props = [] ): \WC_Product {
		return new \WC_Product( 7, 0, 0.0, $props + [ 'date_created' => new \WC_DateTime( self::CREATED ) ] );
	}

	public function test_a_regular_price_is_valid_from_the_publish_date(): void {
		$offer = OfferValidity::filter( [ '@type' => 'Offer' ], $this->product() );

		$this->assertSame( self::CREATED, $offer['validFrom'] );
	}

	public function test_a_sale_price_is_valid_from_the_sale_start(): void {
		$offer = OfferValidity::filter(
			[ '@type' => 'Offer' ],
			$this->product( [ 'on_sale' => true, 'date_on_sale_from' => new \WC_DateTime( self::SALE ) ] )
		);

		$this->assertSame( self::SALE, $offer['validFrom'] );
	}

	public function test_a_sale_without_a_start_date_uses_the_publish_date(): void {
		$offer = OfferValidity::filter( [ '@type' => 'Offer' ], $this->product( [ 'on_sale' => true ] ) );

		$this->assertSame( self::CREATED, $offer['validFrom'] );
	}

	public function test_an_existing_valid_from_is_kept(): void {
		$offer = OfferValidity::filter( [ 'validFrom' => '2020-01-01' ], $this->product() );

		$this->assertSame( '2020-01-01', $offer['validFrom'] );
	}

	public function test_no_date_leaves_the_offer_unchanged(): void {
		$offer = [ '@type' => 'Offer' ];

		$this->assertSame( $offer, OfferValidity::filter( $offer, new \WC_Product( 7 ) ) );
	}

	public function test_anything_but_an_offer_array_and_a_product_is_left_alone(): void {
		$this->assertSame( 'x', OfferValidity::filter( 'x', $this->product() ) );
		$this->assertSame( [ '@type' => 'Offer' ], OfferValidity::filter( [ '@type' => 'Offer' ], null ) );
	}
}
