<?php
/**
 * Polylang integration.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\Polylang;

use LightweightPlugins\SEO\Integrations\IntegrationInterface;

/**
 * Polylang limits every query to the current language, and the XML
 * sitemaps and llms.txt are requested in the default one; they list the
 * content of every language instead.
 */
final class Integration implements IntegrationInterface {

	/**
	 * Integration ID.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'polylang';
	}

	/**
	 * Polylang is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return function_exists( 'pll_current_language' );
	}

	/**
	 * Register the Polylang hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'lw_seo_query_args', [ $this, 'all_languages' ] );
	}

	/**
	 * Query the content of every language.
	 *
	 * @param mixed $args Query arguments.
	 * @return mixed
	 */
	public function all_languages( $args ) {
		if ( is_array( $args ) ) {
			$args['lang'] = '';
		}

		return $args;
	}
}
