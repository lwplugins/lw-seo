<?php
/**
 * Redirect URL tester REST controller.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Rest\Admin;

use LightweightPlugins\SEO\Redirects\Matcher;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * POST lw-seo/v1/admin/redirects-test: which redirect a URL would hit.
 *
 * A route of its own: the id route under /admin/redirects would capture it.
 */
final class RedirectTestController {

	/**
	 * Register the route.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			Routes::NAMESPACE,
			'/admin/redirects-test',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'test_url' ],
				'permission_callback' => [ Routes::class, 'can_manage' ],
			]
		);
	}

	/**
	 * Explain how a URL resolves.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function test_url( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( Matcher::explain( sanitize_text_field( (string) $request->get_param( 'url' ) ) ) );
	}
}
