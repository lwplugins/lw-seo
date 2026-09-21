<?php
/**
 * Crawler registry unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Crawlers;

use LightweightPlugins\SEO\Crawlers\Registry;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class RegistryTest extends MonkeyTestCase {

	public function test_every_crawler_has_a_valid_purpose_and_unique_agent(): void {
		$crawlers = Registry::builtin();
		$agents   = array_column( $crawlers, 'agent' );

		$this->assertSame( count( $agents ), count( array_unique( $agents ) ) );
		foreach ( $crawlers as $key => $crawler ) {
			$this->assertNotEmpty( $crawler['purposes'], $key );
			$this->assertSame( [], array_diff( $crawler['purposes'], [ Registry::TRAINING, Registry::SEARCH, Registry::USER ] ), $key );
		}
	}

	public function test_option_defaults_cover_every_crawler_and_purpose(): void {
		$defaults = Registry::option_defaults();

		foreach ( array_keys( Registry::builtin() ) as $key ) {
			$this->assertArrayHasKey( 'block_' . $key, $defaults );
		}
		$this->assertArrayHasKey( 'block_purpose_training', $defaults );
		$this->assertArrayHasKey( 'block_purpose_search', $defaults );
		$this->assertArrayHasKey( 'block_purpose_user', $defaults );
		$this->assertNotContains( true, $defaults );
	}

	public function test_retired_tokens_are_gone_and_claudebot_is_present(): void {
		$agents = array_column( Registry::builtin(), 'agent' );

		$this->assertContains( 'ClaudeBot', $agents );
		$this->assertNotContains( 'Claude-Web', $agents );
		$this->assertNotContains( 'cohere-ai', $agents );
	}
}
