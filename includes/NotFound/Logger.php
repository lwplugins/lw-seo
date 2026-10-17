<?php
/**
 * 404 request logger.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\NotFound;

use LightweightPlugins\SEO\Options;

/**
 * Records front-end 404s (opt-in), with at most one write per URL per minute.
 */
final class Logger {

	/**
	 * Hook priority on template_redirect.
	 *
	 * Core's own 404 redirects sit at 10 (redirect_canonical) and 1000
	 * (wp_redirect_admin_locations). The plugin loads after core registered
	 * them, so at the same priority 1000 core's callback runs first and a
	 * 404 that core redirects away is never logged. The homepage fallback
	 * (NotFoundHandler) runs at 1001, after this.
	 */
	public const PRIORITY = 1000;

	/**
	 * Extensions that never count as a page 404 (assets, probes).
	 */
	private const SKIP_EXTENSIONS = [ 'php', 'env', 'ini', 'log', 'sql', 'bak', 'map', 'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'ttf' ];

	/**
	 * Cache group for the throttle (used only with a persistent object cache).
	 */
	private const CACHE_GROUP = 'lw_seo_404';

	/**
	 * Seconds a URL is not written again.
	 */
	public const THROTTLE = 60;

	/**
	 * Constructor.
	 *
	 * @param LogRepository $repository Log storage.
	 */
	public function __construct( private LogRepository $repository ) {}

	/**
	 * Register the template_redirect callback.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'template_redirect', [ $this, 'maybe_log' ], self::PRIORITY );
	}

	/**
	 * Log the current request when it is a countable 404.
	 *
	 * @return void
	 */
	public function maybe_log(): void {
		if ( ! Options::get( 'not_found_log_enabled', false ) || ! is_404() || $this->is_excluded_request() ) {
			return;
		}

		$path = $this->request_path();
		if ( '' === $path || in_array( strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ), $this->skip_extensions(), true ) ) {
			return;
		}

		// With a persistent object cache the throttle costs no DB access; without
		// one, LogRepository::record() skips a row written in the last THROTTLE
		// seconds (a transient per URL would create an options row per URL).
		if ( wp_using_ext_object_cache() && ! wp_cache_add( 'lw_seo_404_t_' . md5( $path ), 1, self::CACHE_GROUP, self::THROTTLE ) ) {
			return;
		}

		$user_agent = '';
		if ( Options::get( 'not_found_log_store_ua', false ) ) {
			$user_agent = $this->truncate( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), 255 );
		}

		$this->repository->record( $path, $this->referrer(), $user_agent, max( 1, absint( Options::get( 'not_found_log_max_rows', 1000 ) ) ) );
	}

	/**
	 * Requests that are never logged: HEAD, admin, AJAX, REST, cron.
	 *
	 * @return bool
	 */
	private function is_excluded_request(): bool {
		return 'HEAD' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) )
			|| is_admin()
			|| wp_doing_ajax()
			|| wp_doing_cron()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}

	/**
	 * Skipped extensions (filterable).
	 *
	 * @return array<int, string>
	 */
	private function skip_extensions(): array {
		return array_map( 'strtolower', (array) apply_filters( 'lw_seo_404_log_skip_extensions', self::SKIP_EXTENSIONS ) );
	}

	/**
	 * Requested path without its query string.
	 *
	 * @return string
	 */
	private function request_path(): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- clean() strips control characters and angle brackets; sanitize_text_field() would drop %XX octets.
		$uri  = $this->clean( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		$path = (string) strtok( $uri, '?#' );

		return $this->truncate( $path, 2048 );
	}

	/**
	 * Referrer URL without query string or fragment.
	 *
	 * @return string
	 */
	private function referrer(): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- clean() strips control characters and angle brackets; the URL is esc_url_raw()'d below.
		$referrer = $this->clean( wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' ) );
		if ( '' === $referrer ) {
			return '';
		}

		return $this->truncate( esc_url_raw( (string) strtok( $referrer, '?#' ) ), 2048 );
	}

	/**
	 * Drop control characters and angle brackets, keeping percent-encoding.
	 *
	 * The core sanitizer would strip %XX octets and merge distinct URLs;
	 * the value is escaped when displayed.
	 *
	 * @param string $value Raw header value.
	 * @return string
	 */
	private function clean( string $value ): string {
		return trim( (string) preg_replace( '/[\x00-\x1F\x7F<>]/', '', $value ) );
	}

	/**
	 * Byte-limit a string without cutting a multibyte character.
	 *
	 * @param string $value Text.
	 * @param int    $bytes Maximum length in bytes.
	 * @return string
	 */
	private function truncate( string $value, int $bytes ): string {
		return function_exists( 'mb_strcut' ) ? mb_strcut( $value, 0, $bytes, 'UTF-8' ) : substr( $value, 0, $bytes );
	}
}
