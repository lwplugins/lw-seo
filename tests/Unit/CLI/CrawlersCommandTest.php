<?php
/**
 * CrawlersCommand unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\CLI;

use LightweightPlugins\SEO\CLI\CrawlersCommand;
use LightweightPlugins\SEO\Crawlers\Registry;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class CrawlersCommandTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{name: string, company: string, agent: string, purposes: array<int, string>}>
	 */
	private static function crawlers(): array {
		return [
			'gptbot'       => [ 'name' => 'GPTBot', 'company' => 'OpenAI', 'agent' => 'GPTBot', 'purposes' => [ Registry::TRAINING ] ],
			'oai_search'   => [ 'name' => 'OAI-SearchBot', 'company' => 'OpenAI', 'agent' => 'OAI-SearchBot', 'purposes' => [ Registry::SEARCH ] ],
			'dual'         => [ 'name' => 'Dual', 'company' => 'X', 'agent' => 'Dual', 'purposes' => [ Registry::SEARCH, Registry::TRAINING ] ],
		];
	}

	public function test_row_for_unblocked_crawler(): void {
		$rows = CrawlersCommand::rows( self::crawlers(), [] );

		$this->assertSame(
			[
				'key'      => 'gptbot',
				'agent'    => 'GPTBot',
				'company'  => 'OpenAI',
				'purposes' => 'training',
				'blocked'  => 'no',
				'reason'   => '-',
			],
			$rows[0]
		);
	}

	public function test_row_for_individually_blocked_crawler(): void {
		$rows = CrawlersCommand::rows( self::crawlers(), [ 'block_oai_search' => true ] );

		$this->assertSame( 'yes', $rows[1]['blocked'] );
		$this->assertSame( 'individual', $rows[1]['reason'] );
	}

	public function test_row_for_purpose_blocked_crawler(): void {
		$rows = CrawlersCommand::rows( self::crawlers(), [ 'block_purpose_training' => true ] );

		$this->assertSame( 'yes', $rows[0]['blocked'] );
		$this->assertSame( 'purpose:training', $rows[0]['reason'] );
	}

	public function test_individual_block_takes_priority_over_purpose_reason(): void {
		$rows = CrawlersCommand::rows(
			self::crawlers(),
			[
				'block_purpose_training' => true,
				'block_gptbot'           => true,
			]
		);

		$this->assertSame( 'individual', $rows[0]['reason'] );
	}

	public function test_purposes_joined_for_multi_purpose_crawler(): void {
		$rows = CrawlersCommand::rows( self::crawlers(), [] );

		$this->assertSame( 'search, training', $rows[2]['purposes'] );
	}

	public function test_first_blocked_purpose_reported_for_multi_purpose_crawler(): void {
		$rows = CrawlersCommand::rows( self::crawlers(), [ 'block_purpose_training' => true ] );

		$this->assertSame( 'purpose:training', $rows[2]['reason'] );
	}
}
