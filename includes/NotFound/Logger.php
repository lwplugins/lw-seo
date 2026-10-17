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
	 * Seconds a URL is not written again.
	 */
	private const THROTTLE = 60;

	/**
	 * Transient counting writes since the last inline prune.
	 */
	private const COUNTER = 'lw_seo_404_writes';

	/**
	 * Writes between two inline prunes (bounds how far the cap can be exceeded).
	 */
	private const PRUNE_EVERY = 100;

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

		$key = 'lw_seo_404_t_' . md5( $path );
		if ( false !== get_transient( $key ) ) {
			return;
		}
		set_transient( $key, 1, self::THROTTLE );

		$user_agent = '';
		if ( Options::get( 'not_found_log_store_ua', false ) ) {
			$user_agent = $this->truncate( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), 255 );
		}

		$this->repository->record( $path, $this->referrer(), $user_agent );
		$this->maybe_prune();
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
		$uri  = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		$path = (string) strtok( $uri, '?#' );

		return $this->truncate( $path, 2048 );
	}

	/**
	 * Referrer URL without query string or fragment.
	 *
	 * @return string
	 */
	private function referrer(): string {
		$referrer = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' ) );
		if ( '' === $referrer ) {
			return '';
		}

		return $this->truncate( esc_url_raw( (string) strtok( $referrer, '?#' ) ), 2048 );
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

	/**
	 * Every PRUNE_EVERY writes, enforce the cap now instead of waiting for cron.
	 *
	 * @return void
	 */
	private function maybe_prune(): void {
		$writes = (int) get_transient( self::COUNTER ) + 1;

		if ( $writes < self::PRUNE_EVERY ) {
			set_transient( self::COUNTER, $writes, DAY_IN_SECONDS );
			return;
		}

		delete_transient( self::COUNTER );
		( new Pruner( $this->repository ) )->run();
	}
}
