<?php
/**
 * Crawler policy unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Crawlers;

use LightweightPlugins\SEO\Crawlers\Policy;
use LightweightPlugins\SEO\Crawlers\Registry;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class PolicyTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{name: string, company: string, agent: string, purposes: array<int, string>}>
	 */
	private static function crawlers(): array {
		return [
			'gptbot'       => [ 'name' => 'GPTBot', 'company' => 'OpenAI', 'agent' => 'GPTBot', 'purposes' => [ Registry::TRAINING ] ],
			'oai_search'   => [ 'name' => 'OAI-SearchBot', 'company' => 'OpenAI', 'agent' => 'OAI-SearchBot', 'purposes' => [ Registry::SEARCH ] ],
			'chatgpt_user' => [ 'name' => 'ChatGPT-User', 'company' => 'OpenAI', 'agent' => 'ChatGPT-User', 'purposes' => [ Registry::USER ] ],
			'dual'         => [ 'name' => 'Dual', 'company' => 'X', 'agent' => 'Dual', 'purposes' => [ Registry::SEARCH, Registry::TRAINING ] ],
		];
	}

	/**
	 * @return array<string, array{0: array<string, bool>, 1: array<int, string>}>
	 */
	public static function policy_provider(): array {
		return [
			'nothing blocked'        => [ [], [] ],
			'single crawler'         => [ [ 'block_oai_search' => true ], [ 'OAI-SearchBot' ] ],
			'training purpose'       => [ [ 'block_purpose_training' => true ], [ 'GPTBot', 'Dual' ] ],
			'user purpose'           => [ [ 'block_purpose_user' => true ], [ 'ChatGPT-User' ] ],
			'purpose plus single'    => [ [ 'block_purpose_training' => true, 'block_gptbot' => true ], [ 'GPTBot', 'Dual' ] ],
		];
	}

	/**
	 * @dataProvider policy_provider
	 *
	 * @param array<string, bool> $options  Options.
	 * @param array<int, string>  $expected Blocked agents.
	 */
	public function test_blocked_agents( array $options, array $expected ): void {
		$this->assertSame( $expected, Policy::blocked_agents( self::crawlers(), $options ) );
	}
}
