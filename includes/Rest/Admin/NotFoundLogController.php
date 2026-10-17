<?php
/**
 * 404 log REST controller.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Rest\Admin;

use LightweightPlugins\SEO\NotFound\LogRepository;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * List, delete one and clear under lw-seo/v1/admin/404-log.
 */
final class NotFoundLogController {

	/**
	 * Route base.
	 */
	private const BASE = '/admin/404-log';

	/**
	 * Log storage.
	 *
	 * @var LogRepository
	 */
	private LogRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param LogRepository|null $repository Log storage (default: the real table).
	 */
	public function __construct( ?LogRepository $repository = null ) {
		$this->repository = $repository ?? new LogRepository();
	}

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->route(
			self::BASE,
			[
				WP_REST_Server::READABLE  => 'list_items',
				WP_REST_Server::DELETABLE => 'clear',
			]
		);
		$this->route( self::BASE . '/(?P<id>\d+)', [ WP_REST_Server::DELETABLE => 'delete_item' ] );
	}

	/**
	 * One page of log rows.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function list_items( WP_REST_Request $request ): WP_REST_Response {
		$page     = max( 1, absint( $request->get_param( 'page' ) ) );
		$per_page = min( 100, max( 1, absint( $request->get_param( 'per_page' ) ?? 20 ) ) );

		return new WP_REST_Response(
			$this->repository->paginate(
				sanitize_text_field( (string) $request->get_param( 'search' ) ),
				sanitize_key( (string) $request->get_param( 'orderby' ) ),
				sanitize_key( (string) $request->get_param( 'order' ) ),
				$page,
				$per_page
			)
		);
	}

	/**
	 * Delete one row. Succeeds when it is already gone.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function delete_item( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response(
			[ 'deleted' => $this->repository->delete( [ absint( $request->get_param( 'id' ) ) ] ) ]
		);
	}

	/**
	 * Empty the log.
	 *
	 * @return WP_REST_Response
	 */
	public function clear(): WP_REST_Response {
		$this->repository->clear();

		return new WP_REST_Response( [ 'cleared' => true ] );
	}

	/**
	 * Register one route with its handlers.
	 *
	 * @param string                $route    Route.
	 * @param array<string, string> $handlers HTTP methods => method name on this controller.
	 * @return void
	 */
	private function route( string $route, array $handlers ): void {
		$endpoints = [];
		foreach ( $handlers as $methods => $callback ) {
			$endpoints[] = [
				'methods'             => $methods,
				'callback'            => [ $this, $callback ],
				'permission_callback' => [ Routes::class, 'can_manage' ],
			];
		}

		register_rest_route( Routes::NAMESPACE, $route, $endpoints );
	}
}
