<?php
/**
 * Canonical redirect guard for virtual endpoints.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Stops WordPress from 301-redirecting the plugin's virtual endpoints
 * (/sitemap.xml, /llms.txt, /robots.txt, /{post}/md) to a trailing-slash
 * variant before the endpoint handler on `template_redirect` can answer.
 */
final class CanonicalGuard {

	/**
	 * Query vars that must be non-empty to mark a virtual request.
	 */
	private const VALUE_VARS = [ 'lw_sitemap', 'lw_llms_txt', 'lw_robots_txt' ];

	/**
	 * Rewrite endpoints: present (possibly as empty string) when matched.
	 */
	private const ENDPOINT_VARS = [ 'md', 'markdown' ];

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'redirect_canonical', [ $this, 'filter' ] );
	}

	/**
	 * Disable the canonical redirect for virtual endpoint requests.
	 *
	 * @param string|false $redirect_url Canonical URL WordPress wants to redirect to.
	 * @return string|false
	 */
	public function filter( $redirect_url ) {
		$query_vars = $GLOBALS['wp_query']->query_vars ?? null;

		if ( is_array( $query_vars ) && self::is_virtual_request( $query_vars ) ) {
			return false;
		}

		return $redirect_url;
	}

	/**
	 * Whether the parsed query targets one of the plugin's virtual endpoints.
	 *
	 * @param array<string, mixed> $query_vars Parsed query vars.
	 * @return bool
	 */
	public static function is_virtual_request( array $query_vars ): bool {
		foreach ( self::VALUE_VARS as $var ) {
			if ( ! empty( $query_vars[ $var ] ) ) {
				return true;
			}
		}

		foreach ( self::ENDPOINT_VARS as $var ) {
			if ( isset( $query_vars[ $var ] ) ) {
				return true;
			}
		}

		return false;
	}
}
