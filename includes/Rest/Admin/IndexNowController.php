<?php
/**
 * IndexNow REST controller.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Rest\Admin;

use LightweightPlugins\SEO\IndexNow\Activity;
use LightweightPlugins\SEO\IndexNow\Client;
use LightweightPlugins\SEO\IndexNow\Key;
use LightweightPlugins\SEO\IndexNow\Log;
use LightweightPlugins\SEO\IndexNow\ManualUrls;
use LightweightPlugins\SEO\Options;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Manual submit and key rotation under lw-seo/v1/admin/indexnow.
 */
final class IndexNowController {

	/**
	 * Maximum URLs per manual request.
	 */
	public const MAX_URLS = 100;

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		foreach ( [
			'/admin/indexnow/submit'         => 'submit',
			'/admin/indexnow/regenerate-key' => 'regenerate_key',
		] as $route => $callback ) {
			register_rest_route(
				Routes::NAMESPACE,
				$route,
				[
					[
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => [ $this, $callback ],
						'permission_callback' => [ Routes::class, 'can_manage' ],
					],
				]
			);
		}
	}

	/**
	 * Send the given URLs now.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function submit( WP_REST_Request $request ) {
		if ( ! Options::get( 'indexnow_enabled' ) ) {
			return $this->disabled();
		}

		if ( ! Activity::is_active() ) {
			return new WP_Error( 'lw_seo_indexnow_inactive', Activity::message( Activity::reason() ), [ 'status' => 400 ] );
		}

		$raw = $request->get_param( 'urls' );
		$raw = is_array( $raw ) ? $raw : [];

		$split = ManualUrls::split( $raw );
		if ( array() !== $split['rejected'] ) {
			return new WP_Error(
				'lw_seo_indexnow_foreign_host',
				sprintf(
					/* translators: %s: first rejected URL. */
					__( 'Only addresses on this site can be submitted: %s', 'lw-seo' ),
					$split['rejected'][0]
				),
				[ 'status' => 400 ]
			);
		}

		if ( array() === $split['accepted'] ) {
			return new WP_Error( 'lw_seo_indexnow_no_urls', __( 'Enter at least one URL.', 'lw-seo' ), [ 'status' => 400 ] );
		}

		if ( count( $split['accepted'] ) > self::MAX_URLS ) {
			return new WP_Error(
				'lw_seo_indexnow_too_many',
				/* translators: %d: maximum number of URLs. */
				sprintf( __( 'Submit at most %d URLs at a time.', 'lw-seo' ), self::MAX_URLS ),
				[ 'status' => 400 ]
			);
		}

		$result = Client::submit( $split['accepted'], 'manual' );

		return new WP_REST_Response( $result + [ 'log' => self::log() ] );
	}

	/**
	 * Replace the key. The key file is served from a fixed rewrite pattern,
	 * so no rewrite flush is needed.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function regenerate_key() {
		if ( ! Options::get( 'indexnow_enabled' ) ) {
			return $this->disabled();
		}

		Key::regenerate();

		return new WP_REST_Response( [ 'key_url' => Key::location() ] );
	}

	/**
	 * IndexNow state for the settings screen. The key is created as soon as
	 * the feature is on, so the key URL shows right after the first save.
	 *
	 * `paused` explains why a switched-on IndexNow sends nothing ('' otherwise).
	 *
	 * @return array{key_url: string, paused: string, log: array<int, array<string, mixed>>}
	 */
	public static function meta(): array {
		$reason = Activity::reason();

		if ( Activity::REASON_DISABLED !== $reason ) {
			Key::ensure();
		}

		return [
			'key_url' => '' === Key::get() ? '' : Key::location(),
			'paused'  => Activity::REASON_DISABLED === $reason ? '' : Activity::message( $reason ),
			'log'     => self::log(),
		];
	}

	/**
	 * Log for the UI: newest first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function log(): array {
		return array_slice( array_reverse( Log::all() ), 0, Log::LIMIT );
	}

	/**
	 * Error for requests while the feature is off.
	 *
	 * @return WP_Error
	 */
	private function disabled(): WP_Error {
		return new WP_Error( 'lw_seo_indexnow_disabled', __( 'Turn on IndexNow first.', 'lw-seo' ), [ 'status' => 400 ] );
	}
}
