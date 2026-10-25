<?php
/**
 * IndexNow key file endpoint.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\IndexNow;

/**
 * Serves /{key}.txt. The rewrite pattern matches any 32-hex name, so
 * regenerating the key needs no rewrite flush.
 */
final class KeyFile {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! Activity::is_active() ) {
			return;
		}

		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_action( 'template_redirect', [ $this, 'handle_request' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
	}

	/**
	 * Add the rewrite rule.
	 *
	 * @return void
	 */
	public function add_rewrite_rules(): void {
		add_rewrite_rule( '^([a-f0-9]{32})\.txt$', 'index.php?lw_indexnow_key=$matches[1]', 'top' );
	}

	/**
	 * Add query vars.
	 *
	 * @param array<string> $vars Query vars.
	 * @return array<string>
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'lw_indexnow_key';
		return $vars;
	}

	/**
	 * File body for a requested key, or null when it must not be served.
	 *
	 * @param string $requested Key taken from the URL.
	 * @return string|null
	 */
	public static function content_for( string $requested ): ?string {
		if ( ! Activity::is_active() ) {
			return null;
		}

		$key = Key::get();

		return '' !== $key && hash_equals( $key, $requested ) ? $key : null;
	}

	/**
	 * Serve the key file, or a 404 for any other key.
	 *
	 * @return void
	 */
	public function handle_request(): void {
		$requested = (string) get_query_var( 'lw_indexnow_key' );

		if ( '' === $requested ) {
			return;
		}

		$content = self::content_for( $requested );

		if ( null === $content ) {
			// The rewrite target is index.php, so without this WP would render the home page.
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}

		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex' );

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text key (32 hex chars) served with nosniff.
		exit;
	}
}
