<?php
/**
 * ContentSignals unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\ContentSignals;
use LightweightPlugins\SEO\Options;

final class ContentSignalsTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_unset_signals_are_omitted(): void {
		$this->stub_options( [ 'content_signals_ai_train' => 'no' ] );

		$this->assertSame( [ 'ai-train' => 'no' ], ContentSignals::global_signals() );
	}

	public function test_post_override_wins_over_global(): void {
		$this->stub_options( [ 'content_signals_search' => 'yes', 'content_signals_ai_input' => 'yes' ] );
		Functions\when( 'get_post_meta' )->alias( static fn( int $id, string $key ): string => '_lw_seo_ai_input' === $key ? 'no' : '' );

		$signals = ContentSignals::resolve( new \WP_Post( [ 'ID' => 3 ] ) );

		$this->assertSame( [ 'search' => 'yes', 'ai-input' => 'no' ], $signals );
	}

	public function test_format_header_uses_canonical_order(): void {
		$this->assertSame( 'search=yes, ai-input=no, ai-train=no', ContentSignals::format_header( [ 'ai-train' => 'no', 'search' => 'yes', 'ai-input' => 'no' ] ) );
	}

	public function test_headers_are_empty_when_nothing_is_set(): void {
		$this->assertSame( [], ContentSignals::headers( [] ) );
	}

	public function test_headers_send_standard_name_and_legacy_alias(): void {
		$this->assertSame(
			[ 'Content-Signal' => 'ai-train=no', 'X-Content-Signals' => 'ai-train=no' ],
			ContentSignals::headers( [ 'ai-train' => 'no' ] )
		);
	}
}
