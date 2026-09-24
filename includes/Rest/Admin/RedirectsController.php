<?php
/**
 * Redirects REST controller.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Rest\Admin;

use LightweightPlugins\SEO\Redirects\Manager;
use LightweightPlugins\SEO\Redirects\Repository;
use LightweightPlugins\SEO\Redirects\Validator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * CRUD, CSV export and CSV import under lw-seo/v1/admin/redirects.
 */
final class RedirectsController {

	/**
	 * Route base.
	 */
	private const BASE = '/admin/redirects';

	/**
	 * Register the routes. The fixed export/import routes come before the
	 * id route.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->route(
			self::BASE,
			[
				WP_REST_Server::READABLE  => 'list_items',
				WP_REST_Server::CREATABLE => 'create_item',
			]
		);
		$this->route( self::BASE . '/export', [ WP_REST_Server::READABLE => 'export_items' ] );
		$this->route( self::BASE . '/import', [ WP_REST_Server::CREATABLE => 'import_items' ] );
		$this->route(
			self::BASE . '/(?P<id>[A-Za-z0-9_-]+)',
			[
				WP_REST_Server::EDITABLE  => 'update_item',
				WP_REST_Server::DELETABLE => 'delete_item',
			]
		);
	}

	/**
	 * Every redirect plus the type labels.
	 *
	 * @return WP_REST_Response
	 */
	public function list_items(): WP_REST_Response {
		return new WP_REST_Response(
			[
				'items' => Repository::all(),
				'types' => (object) Manager::TYPES,
			]
		);
	}

	/**
	 * Add a redirect.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( WP_REST_Request $request ) {
		$input = $this->input( $request );
		$error = Validator::error( ...$input );
		if ( null !== $error ) {
			return $this->invalid( $error );
		}

		$item = Repository::create( ...$input );
		if ( null === $item ) {
			return new WP_Error( 'lw_seo_redirect_failed', __( 'Failed to add redirect.', 'lw-seo' ), [ 'status' => 500 ] );
		}

		return new WP_REST_Response( $item, 201 );
	}

	/**
	 * Update a redirect. Succeeds when nothing changed.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( WP_REST_Request $request ) {
		$id = (string) $request->get_param( 'id' );
		if ( null === Repository::find( $id ) ) {
			return $this->not_found();
		}

		$input = $this->input( $request );
		$error = Validator::error( ...$input );
		if ( null !== $error ) {
			return $this->invalid( $error );
		}

		$item = Repository::update( $id, ...$input );

		return null === $item ? $this->not_found() : new WP_REST_Response( $item );
	}

	/**
	 * Delete a redirect.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( WP_REST_Request $request ) {
		if ( ! Repository::delete( (string) $request->get_param( 'id' ) ) ) {
			return $this->not_found();
		}

		return new WP_REST_Response( [ 'deleted' => true ] );
	}

	/**
	 * Every redirect as CSV.
	 *
	 * @return WP_REST_Response
	 */
	public function export_items(): WP_REST_Response {
		return new WP_REST_Response(
			[
				'csv'      => Manager::export_csv(),
				'filename' => 'lw-seo-redirects-' . gmdate( 'Y-m-d' ) . '.csv',
			]
		);
	}

	/**
	 * Import redirects from CSV text.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_items( WP_REST_Request $request ) {
		$csv = sanitize_textarea_field( (string) $request->get_param( 'csv' ) );
		if ( '' === trim( $csv ) ) {
			return $this->invalid( __( 'No CSV data provided.', 'lw-seo' ) );
		}

		$result = Manager::import_csv( $csv );

		return new WP_REST_Response(
			[
				'imported' => $result['imported'],
				'skipped'  => $result['skipped'],
				'errors'   => array_values( $result['errors'] ),
			]
		);
	}

	/**
	 * Sanitized redirect fields, in Validator/Repository argument order.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array{0: string, 1: string, 2: int, 3: bool}
	 */
	private function input( WP_REST_Request $request ): array {
		return [
			sanitize_text_field( (string) $request->get_param( 'source' ) ),
			sanitize_text_field( (string) $request->get_param( 'destination' ) ),
			Validator::normalize_type( (int) $request->get_param( 'type' ) ),
			rest_sanitize_boolean( $request->get_param( 'regex' ) ?? false ),
		];
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

	/**
	 * 400 validation error.
	 *
	 * @param string $message Translated message.
	 * @return WP_Error
	 */
	private function invalid( string $message ): WP_Error {
		return new WP_Error( 'lw_seo_redirect_invalid', $message, [ 'status' => 400 ] );
	}

	/**
	 * 404 unknown id.
	 *
	 * @return WP_Error
	 */
	private function not_found(): WP_Error {
		return new WP_Error( 'lw_seo_redirect_not_found', __( 'Redirect not found.', 'lw-seo' ), [ 'status' => 404 ] );
	}
}
