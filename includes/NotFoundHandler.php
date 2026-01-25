<?php
/**
 * 404 Not Found Handler class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Handles 404 error redirects.
 */
final class NotFoundHandler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Only run if 404 redirect is enabled.
		if ( ! Options::get( 'redirect_404_to_home', false ) ) {
			return;
		}

		add_action( 'template_redirect', [ $this, 'maybe_redirect_404' ], 5 );
	}

	/**
	 * Redirect 404 pages to homepage.
	 *
	 * @return void
	 */
	public function maybe_redirect_404(): void {
		if ( ! is_404() ) {
			return;
		}

		// Don't redirect in admin.
		if ( is_admin() ) {
			return;
		}

		wp_safe_redirect( home_url( '/' ), 302, 'LW SEO' );
		exit;
	}
}
