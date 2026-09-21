<?php
/**
 * `wp lw-seo llms` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\LlmsTxt\Cache;
use LightweightPlugins\SEO\LlmsTxt\Generator;
use LightweightPlugins\SEO\LlmsTxt\SectionCollector;
use LightweightPlugins\SEO\Options;

/**
 * Preview, flush and inspect /llms.txt and /llms-full.txt.
 */
final class LlmsCommand {

	/**
	 * Preview what LW SEO would serve, without touching the cache. Useful
	 * on sites where another plugin shadows /llms.txt, since the URL
	 * itself may not reach LW SEO.
	 *
	 * ## OPTIONS
	 *
	 * [--full]
	 * : Preview llms-full.txt instead of llms.txt.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function preview( array $args, array $assoc_args ): void {
		$full = isset( $assoc_args['full'] );

		if ( $full && ! Options::get( 'llms_full_txt_enabled' ) ) {
			\WP_CLI::warning( 'llms-full.txt is disabled in LW SEO settings; previewing anyway.' );
		}

		$generator = new Generator();

		\WP_CLI::line( Cache::build_as_visitor( $full ? [ $generator, 'full' ] : [ $generator, 'index' ] ) );
	}

	/**
	 * Flush the cached llms.txt / llms-full.txt.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function flush( array $args, array $assoc_args ): void {
		Cache::flush();
		\WP_CLI::success( 'llms.txt cache flushed.' );
	}

	/**
	 * Show llms.txt status: enabled state, URLs, listed post types.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function info( array $args, array $assoc_args ): void {
		if ( ! Options::get( 'llms_txt_enabled' ) ) {
			\WP_CLI::warning( 'llms.txt is disabled in LW SEO settings.' );
			return;
		}

		\WP_CLI::line( 'llms.txt: ' . home_url( '/llms.txt' ) );
		\WP_CLI::line(
			'llms-full.txt: ' . ( Options::get( 'llms_full_txt_enabled' ) ? home_url( '/llms-full.txt' ) : 'disabled' )
		);

		$post_types = array_keys( SectionCollector::post_types() );
		\WP_CLI::line( 'Post types listed: ' . ( [] === $post_types ? '(none)' : implode( ', ', $post_types ) ) );
	}
}
