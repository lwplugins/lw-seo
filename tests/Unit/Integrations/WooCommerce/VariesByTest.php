<?php
/**
 * ProductGroup variesBy mapping unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\WooCommerce\VariesBy;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\VariesBy
 */
final class VariesByTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'sanitize_title' )->alias(
			static fn( string $title ) => trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( strtr( $title, [ 'í' => 'i', 'é' => 'e', 'Í' => 'I', 'É' => 'E' ] ) ) ), '-' )
		);
	}

	public function test_maps_hungarian_and_english_slugs(): void {
		$this->assertSame(
			[
				'https://schema.org/color',
				'https://schema.org/size',
				'https://schema.org/material',
				'https://schema.org/pattern',
				'https://schema.org/suggestedAge',
				'https://schema.org/suggestedGender',
			],
			VariesBy::for_attributes( [ 'pa_szin', 'attribute_pa_colour', 'Méret', 'pa_anyag', 'minta', 'pa_age', 'nem', 'pa_weight' ] )
		);
		$this->assertSame( [ 'https://schema.org/color' ], VariesBy::for_attributes( [ 'Szín' ] ) );
		$this->assertSame( 'size', VariesBy::property( 'attribute_pa_meret' ) );
		$this->assertNull( VariesBy::property( 'pa_weight' ) );
	}

	public function test_filter(): void {
		Filters\expectApplied( 'lw_seo_woo_varies_by_map' )->andReturnUsing(
			static fn( array $map ) => [ 'Suly' => 'weight' ] + $map
		);

		$this->assertSame(
			[ 'https://schema.org/weight', 'https://schema.org/color' ],
			VariesBy::for_attributes( [ 'pa_suly', 'pa_color' ] )
		);
	}
}
