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
 *
 * The homepage redirect is the last resort for a 404, so it runs after
 * every redirect core makes for one on template_redirect: the renamed-slug
 * 301 (wp_old_slug_redirect) and the guessed permalink (redirect_canonical)
 * at priority 10, and the /admin, /login shortcuts
 * (wp_redirect_admin_locations) at 1000. The Redirects module's own rules
 * still run first, at priority 1.
 */
final class NotFoundHandler {

	/**
	 * Hook priority on template_redirect: after core's priority-1000 redirect.
	 */
	private const PRIORITY = 1001;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Only run if 404 redirect is enabled.
		if ( ! Options::get( 'redirect_404_to_home', false ) ) {
			return;
		}

		add_action( 'template_redirect', [ $this, 'maybe_redirect_404' ], self::PRIORITY );
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
