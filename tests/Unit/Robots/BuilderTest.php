<?php
/**
 * robots.txt Builder unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Robots;

use LightweightPlugins\SEO\Robots\Builder;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class BuilderTest extends MonkeyTestCase {

	private const CORE = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n";

	/**
	 * @param array<string, mixed> $overrides Context overrides.
	 * @return array{public: bool, sitemap: string, llms: string, signal: string, blocked: array<int, string>}
	 */
	private static function context( array $overrides = [] ): array {
		return array_merge(
			[
				'public'  => true,
				'sitemap' => '',
				'llms'    => '',
				'signal'  => '',
				'blocked' => [],
			],
			$overrides
		);
	}

	public function test_leaves_core_output_alone_when_nothing_to_add(): void {
		$this->assertSame( self::CORE, Builder::build( self::CORE, self::context() ) );
	}

	public function test_full_output(): void {
		$expected = implode( "\n", Builder::POLICY_COMMENT ) . "\n\n"
			. "User-agent: *\nContent-Signal: search=yes, ai-train=no\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\n"
			. "# LW SEO\nSitemap: https://x.test/sitemap.xml\n\n"
			. "User-agent: GPTBot\nDisallow: /\n\n"
			. "User-agent: ClaudeBot\nDisallow: /\n\n"
			. "# llms.txt: https://x.test/llms.txt\n";

		$result = Builder::build(
			self::CORE,
			self::context(
				[
					'sitemap' => 'https://x.test/sitemap.xml',
					'llms'    => 'https://x.test/llms.txt',
					'signal'  => 'search=yes, ai-train=no',
					'blocked' => [ 'GPTBot', 'ClaudeBot' ],
				]
			)
		);

		$this->assertSame( $expected, $result );
	}

	public function test_sitemap_is_skipped_on_non_public_sites_and_when_already_present(): void {
		$private = Builder::build( self::CORE, self::context( [ 'public' => false, 'sitemap' => 'https://x.test/sitemap.xml' ] ) );
		$present = Builder::build( self::CORE . "\nSitemap: https://x.test/sitemap.xml\n", self::context( [ 'sitemap' => 'https://x.test/sitemap.xml' ] ) );

		$this->assertStringNotContainsString( 'Sitemap:', $private );
		$this->assertSame( 1, substr_count( $present, 'Sitemap:' ) );
	}

	public function test_signal_goes_into_the_star_group_even_after_other_groups(): void {
		$output = "User-agent: Googlebot\nDisallow: /tmp/\n\nUser-agent: *\nDisallow: /wp-admin/\n";

		$this->assertSame(
			"User-agent: Googlebot\nDisallow: /tmp/\n\nUser-agent: *\nContent-Signal: ai-train=no\nDisallow: /wp-admin/\n",
			Builder::add_signal( $output, 'ai-train=no' )
		);
	}

	public function test_signal_creates_a_star_group_when_missing(): void {
		$this->assertSame(
			"User-agent: *\nContent-Signal: ai-train=no\nAllow: /\n",
			Builder::add_signal( '', 'ai-train=no' )
		);
	}

	public function test_build_normalizes_crlf_before_inserting_signal_into_the_original_group(): void {
		$crlf_core = "User-agent: *\r\nDisallow: /wp-admin/\r\nAllow: /wp-admin/admin-ajax.php\r\n";

		$result = Builder::build( $crlf_core, self::context( [ 'signal' => 'ai-train=no' ] ) );

		$this->assertSame( 1, substr_count( $result, 'User-agent: *' ), 'a second, synthesized star group must not appear' );
		$this->assertStringContainsString( "User-agent: *\nContent-Signal: ai-train=no\nDisallow: /wp-admin/\n", $result );
	}

	/**
	 * @dataProvider provide_crlf_star_groups
	 */
	public function test_signal_matches_the_star_group_regardless_of_line_ending_or_spacing( string $output, string $expected ): void {
		$this->assertSame( $expected, Builder::add_signal( $output, 'ai-train=no' ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function provide_crlf_star_groups(): array {
		return [
			'CRLF, standard spacing' => [
				"User-agent: *\r\nDisallow: /wp-admin/\r\n",
				"User-agent: *\nContent-Signal: ai-train=no\nDisallow: /wp-admin/\n",
			],
			'CRLF, no space after colon' => [
				"User-agent:*\r\nDisallow: /wp-admin/\r\n",
				"User-agent:*\nContent-Signal: ai-train=no\nDisallow: /wp-admin/\n",
			],
			'CRLF, mixed case'      => [
				"User-Agent: *\r\nDisallow: /wp-admin/\r\n",
				"User-Agent: *\nContent-Signal: ai-train=no\nDisallow: /wp-admin/\n",
			],
		];
	}
}
