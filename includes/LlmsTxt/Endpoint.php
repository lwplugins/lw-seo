<?php
/**
 * LLMS.txt endpoint.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\LlmsTxt;

use LightweightPlugins\SEO\Options;

/**
 * Serves /llms.txt and (opt-in) /llms-full.txt.
 *
 * @see https://llmstxt.org/
 */
final class Endpoint {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! Options::get( 'llms_txt_enabled' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_action( 'template_redirect', [ $this, 'handle_request' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );

		Cache::register_invalidation();
	}

	/**
	 * Add rewrite rules.
	 *
	 * @return void
	 */
	public function add_rewrite_rules(): void {
		add_rewrite_rule( '^llms\.txt$', 'index.php?lw_llms_txt=index', 'top' );

		if ( Options::get( 'llms_full_txt_enabled' ) ) {
			add_rewrite_rule( '^llms-full\.txt$', 'index.php?lw_llms_txt=full', 'top' );
		}
	}

	/**
	 * Add query vars.
	 *
	 * @param array<string> $vars Query vars.
	 * @return array<string>
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'lw_llms_txt';
		return $vars;
	}

	/**
	 * Serve the requested file.
	 *
	 * @return void
	 */
	public function handle_request(): void {
		$file = (string) get_query_var( 'lw_llms_txt' );

		if ( '' === $file ) {
			return;
		}

		$generator = new Generator();
		$is_full   = 'full' === $file && Options::get( 'llms_full_txt_enabled' );
		$content   = $is_full
			? Cache::remember( 'full', [ $generator, 'full' ] )
			: Cache::remember( 'index', [ $generator, 'index' ] );

		header( 'Content-Type: text/plain; charset=UTF-8' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex' );

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text llms.txt body served with nosniff.
		exit;
	}
}
