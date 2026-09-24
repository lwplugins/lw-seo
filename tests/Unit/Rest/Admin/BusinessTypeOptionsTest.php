<?php
/**
 * BusinessTypeOptions unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Rest\Admin;

use LightweightPlugins\SEO\Rest\Admin\BusinessTypeOptions;
use PHPUnit\Framework\TestCase;

final class BusinessTypeOptionsTest extends TestCase {

	private const GROUPED = [
		'General' => [ 'LocalBusiness' => 'Local Business' ],
		'Food'    => [ 'Restaurant' => 'Restaurant' ],
	];

	private const ALL = [
		'LocalBusiness' => 'Local Business',
		'Restaurant'    => 'Restaurant',
		'Winery'        => 'Winery',
	];

	public function test_builds_groups_with_value_label_options(): void {
		$groups = BusinessTypeOptions::build( self::GROUPED, self::ALL, 'Restaurant', 'Other' );

		$this->assertSame(
			[
				[
					'group'   => 'General',
					'options' => [ [ 'value' => 'LocalBusiness', 'label' => 'Local Business' ] ],
				],
				[
					'group'   => 'Food',
					'options' => [ [ 'value' => 'Restaurant', 'label' => 'Restaurant' ] ],
				],
			],
			$groups
		);
	}

	public function test_appends_an_ungrouped_stored_type_to_other(): void {
		$groups = BusinessTypeOptions::build( self::GROUPED, self::ALL, 'Winery', 'Other' );

		$this->assertSame(
			[
				'group'   => 'Other',
				'options' => [ [ 'value' => 'Winery', 'label' => 'Winery' ] ],
			],
			end( $groups )
		);
	}

	public function test_keeps_an_unknown_stored_type_with_its_raw_value(): void {
		$groups = BusinessTypeOptions::build( self::GROUPED, self::ALL, 'CustomType', 'Other' );

		$this->assertSame( [ [ 'value' => 'CustomType', 'label' => 'CustomType' ] ], end( $groups )['options'] );
	}

	public function test_adds_no_other_group_for_an_empty_stored_type(): void {
		$groups = BusinessTypeOptions::build( self::GROUPED, self::ALL, '', 'Other' );

		$this->assertCount( 2, $groups );
	}
}
