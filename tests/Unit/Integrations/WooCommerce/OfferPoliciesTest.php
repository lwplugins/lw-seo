<?php
/**
 * OfferPolicies unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\WooCommerce\OfferPolicies;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\OfferPolicies
 */
final class OfferPoliciesTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'get_woocommerce_currency' )->justReturn( 'HUF' );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * @param array<string, mixed> $saved Saved options.
	 * @return array<string, mixed>
	 */
	private function apply( array $saved, array $offer = [ '@type' => 'Offer' ] ): array {
		$this->stub_options( $saved );

		return OfferPolicies::apply( $offer );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function shipping( array $extra = [] ): array {
		return $extra + [ 'woo_shipping_enabled' => true, 'woo_shipping_countries' => 'HU', 'woo_shipping_rate' => '1490' ];
	}

	public function test_flat_rate(): void {
		$detail = $this->apply( $this->shipping() )['shippingDetails'][0];

		$this->assertSame( 'OfferShippingDetails', $detail['@type'] );
		$this->assertSame( [ '@type' => 'MonetaryAmount', 'value' => 1490.0, 'currency' => 'HUF' ], $detail['shippingRate'] );
		$this->assertSame( [ '@type' => 'DefinedRegion', 'addressCountry' => 'HU' ], $detail['shippingDestination'] );
		$this->assertSame( 'ShippingDeliveryTime', $detail['deliveryTime']['@type'] );
		$this->assertSame( [ '@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY' ], $detail['deliveryTime']['handlingTime'] );
		$this->assertSame( [ '@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 5, 'unitCode' => 'DAY' ], $detail['deliveryTime']['transitTime'] );
	}

	public function test_free_rate_value_zero(): void {
		$detail = $this->apply( $this->shipping( [ 'woo_shipping_rate_type' => 'free', 'woo_shipping_rate' => '' ] ) )['shippingDetails'][0];

		$this->assertSame( 0, $detail['shippingRate']['value'] );
	}

	public function test_one_detail_per_country(): void {
		$details = $this->apply( $this->shipping( [ 'woo_shipping_countries' => 'HU,DE,AT' ] ) )['shippingDetails'];

		$this->assertSame( [ 'HU', 'DE', 'AT' ], array_column( array_column( $details, 'shippingDestination' ), 'addressCountry' ) );
	}

	public function test_swaps_min_max(): void {
		$time = $this->apply( $this->shipping( [ 'woo_transit_min' => 7, 'woo_transit_max' => 2 ] ) )['shippingDetails'][0]['deliveryTime']['transitTime'];

		$this->assertSame( 2, $time['minValue'] );
		$this->assertSame( 7, $time['maxValue'] );
	}

	public function test_not_emitted_without_country(): void {
		$this->assertArrayNotHasKey( 'shippingDetails', $this->apply( $this->shipping( [ 'woo_shipping_countries' => '' ] ) ) );
		$this->assertArrayNotHasKey( 'hasMerchantReturnPolicy', $this->apply( [ 'woo_returns_enabled' => true ] ) );
		$this->assertArrayNotHasKey( 'shippingDetails', $this->apply( $this->shipping( [ 'woo_shipping_rate' => '' ] ) ) );
		$this->assertSame( [ '@type' => 'Offer' ], $this->apply( [] ) );
	}

	public function test_finite_window_has_days(): void {
		$policy = $this->apply( [ 'woo_returns_enabled' => true, 'woo_return_country' => 'HU' ] )['hasMerchantReturnPolicy'];

		$this->assertSame(
			[
				'@type'                => 'MerchantReturnPolicy',
				'applicableCountry'    => 'HU',
				'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
				'merchantReturnDays'   => 14,
				'returnMethod'         => 'https://schema.org/ReturnByMail',
				'returnFees'           => 'https://schema.org/FreeReturn',
			],
			$policy
		);
	}

	public function test_not_permitted_omits_method_and_fees(): void {
		$policy = $this->apply( [ 'woo_returns_enabled' => true, 'woo_return_country' => 'HU', 'woo_return_category' => 'not_permitted' ] )['hasMerchantReturnPolicy'];

		$this->assertSame( 'https://schema.org/MerchantReturnNotPermitted', $policy['returnPolicyCategory'] );
		$this->assertArrayNotHasKey( 'returnMethod', $policy );
		$this->assertArrayNotHasKey( 'returnFees', $policy );
		$this->assertArrayNotHasKey( 'merchantReturnDays', $policy );
	}

	public function test_unlimited_has_no_days_but_method(): void {
		$policy = $this->apply( [ 'woo_returns_enabled' => true, 'woo_return_country' => 'HU', 'woo_return_category' => 'unlimited', 'woo_return_method' => 'in_store', 'woo_return_fees' => 'customer_pays' ] )['hasMerchantReturnPolicy'];

		$this->assertArrayNotHasKey( 'merchantReturnDays', $policy );
		$this->assertSame( 'https://schema.org/ReturnInStore', $policy['returnMethod'] );
		$this->assertSame( 'https://schema.org/ReturnFeesCustomerResponsibility', $policy['returnFees'] );
	}

	public function test_never_overwrites_existing_keys(): void {
		$own    = [ [ '@type' => 'OfferShippingDetails', 'note' => 'mine' ] ];
		$result = $this->apply( $this->shipping( [ 'woo_returns_enabled' => true, 'woo_return_country' => 'HU' ] ), [ '@type' => 'Offer', 'shippingDetails' => $own ] );

		$this->assertSame( $own, $result['shippingDetails'] );
		$this->assertArrayHasKey( 'hasMerchantReturnPolicy', $result );
	}

	public function test_applies_to_aggregate_offer(): void {
		$result = $this->apply( $this->shipping(), [ '@type' => 'AggregateOffer', 'lowPrice' => '1' ] );

		$this->assertSame( 'AggregateOffer', $result['@type'] );
		$this->assertCount( 1, $result['shippingDetails'] );
	}

	public function test_filter_ignores_non_arrays(): void {
		$this->assertSame( 'x', OfferPolicies::filter( 'x' ) );
	}
}
