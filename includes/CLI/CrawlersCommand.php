<?php
/**
 * `wp lw-seo crawlers` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\Crawlers\Policy;
use LightweightPlugins\SEO\Crawlers\Registry;
use LightweightPlugins\SEO\Options;

/**
 * Inspect the AI crawler registry and its block state.
 */
final class CrawlersCommand {

	/**
	 * List known AI crawlers, whether each is blocked, and why. Blocking
	 * itself is not done here: use
	 * `wp lw-seo option set block_<key> on` for one crawler, or
	 * `wp lw-seo option set block_purpose_<purpose> on` for a whole
	 * purpose (training, search, user).
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format. Options: table, json, yaml, csv. Default: table.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function list( array $args, array $assoc_args ): void {
		$rows = self::rows( Registry::all(), Options::get_all() );

		\WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $rows, [ 'key', 'agent', 'company', 'purposes', 'blocked', 'reason' ] );
	}

	/**
	 * Build one display row per registered crawler. The "blocked" column
	 * comes from Crawlers\Policy — the same source robots.txt generation
	 * uses — so the two never disagree; "reason" adds the why.
	 *
	 * @param array<string, array{name: string, company: string, agent: string, purposes: array<int, string>}> $crawlers Crawler registry (Registry::all()).
	 * @param array<string, mixed>                                                                             $options  Plugin options (Options::get_all()).
	 * @return array<int, array{key: string, agent: string, company: string, purposes: string, blocked: string, reason: string}>
	 */
	public static function rows( array $crawlers, array $options ): array {
		$blocked_agents = Policy::blocked_agents( $crawlers, $options );
		$rows           = [];

		foreach ( $crawlers as $key => $crawler ) {
			$rows[] = [
				'key'      => (string) $key,
				'agent'    => $crawler['agent'],
				'company'  => $crawler['company'],
				'purposes' => implode( ', ', $crawler['purposes'] ),
				'blocked'  => in_array( $crawler['agent'], $blocked_agents, true ) ? 'yes' : 'no',
				'reason'   => self::reason( (string) $key, $crawler['purposes'], $options ),
			];
		}

		return $rows;
	}

	/**
	 * Why a crawler is blocked ('individual', 'purpose:<name>'), or '-'.
	 *
	 * @param string               $key      Crawler key.
	 * @param array<int, string>   $purposes Crawler purposes.
	 * @param array<string, mixed> $options  Plugin options.
	 * @return string
	 */
	private static function reason( string $key, array $purposes, array $options ): string {
		if ( ! empty( $options[ 'block_' . $key ] ) ) {
			return 'individual';
		}

		foreach ( $purposes as $purpose ) {
			if ( ! empty( $options[ 'block_purpose_' . $purpose ] ) ) {
				return 'purpose:' . $purpose;
			}
		}

		return '-';
	}
}
