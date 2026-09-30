<?php
/**
 * Bricks backfill unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\Bricks;

use LightweightPlugins\SEO\Integrations\Bricks\Backfill;
use PHPUnit\Framework\TestCase;

final class BackfillTest extends TestCase {

	public function test_fills_only_empty_lw_seo_fields(): void {
		$fill = Backfill::missing(
			[
				'documentTitle'   => 'Bricks title',
				'metaDescription' => 'Bricks description',
				'metaRobots'      => [ 'noindex' ],
			],
			[
				'title'       => 'Own LW title',
				'description' => '',
				'noindex'     => '',
			]
		);

		$this->assertSame(
			[
				'description' => 'Bricks description',
				'noindex'     => '1',
			],
			$fill
		);
	}

	public function test_never_switches_a_flag_off(): void {
		$this->assertSame( [], Backfill::missing( [], [ 'noindex' => '1' ] ) );
	}
}
