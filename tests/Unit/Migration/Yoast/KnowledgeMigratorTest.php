<?php
/**
 * Tests for Yoast KnowledgeMigrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\Yoast;

use LightweightPlugins\SEO\Migration\Yoast\KnowledgeMigrator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\Yoast\KnowledgeMigrator
 */
final class KnowledgeMigratorTest extends TestCase {

	/**
	 * A 'company' identity maps to the 'organization' knowledge type.
	 *
	 * @return void
	 */
	public function test_maps_company_to_organization(): void {
		$lw      = [];
		$details = [];

		$count = ( new KnowledgeMigrator() )->migrate(
			[
				'company_or_person' => 'company',
				'company_name'      => 'Acme',
			],
			$lw,
			$details
		);

		$this->assertSame( 'organization', $lw['knowledge_type'] );
		$this->assertSame( 'Acme', $lw['knowledge_name'] );
		$this->assertSame( 2, $count );
	}

	/**
	 * A 'person' identity maps to the 'person' knowledge type and falls back to
	 * the person name when no company name is present.
	 *
	 * @return void
	 */
	public function test_maps_person_and_uses_person_name(): void {
		$lw      = [];
		$details = [];

		( new KnowledgeMigrator() )->migrate(
			[
				'company_or_person' => 'person',
				'person_name'       => 'Jane Roe',
			],
			$lw,
			$details
		);

		$this->assertSame( 'person', $lw['knowledge_type'] );
		$this->assertSame( 'Jane Roe', $lw['knowledge_name'] );
	}

	/**
	 * Already-filled LW options are never overwritten.
	 *
	 * @return void
	 */
	public function test_does_not_overwrite_existing_values(): void {
		$lw      = [ 'knowledge_type' => 'person' ];
		$details = [];

		$count = ( new KnowledgeMigrator() )->migrate(
			[ 'company_or_person' => 'company' ],
			$lw,
			$details
		);

		$this->assertSame( 'person', $lw['knowledge_type'] );
		$this->assertSame( 0, $count );
	}
}
