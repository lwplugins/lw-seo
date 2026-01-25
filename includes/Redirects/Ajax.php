<?php
/**
 * Redirect AJAX Handler class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Redirects;

/**
 * Handles AJAX requests for redirect management.
 */
final class Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_lw_seo_redirect_add', [ $this, 'add_redirect' ] );
		add_action( 'wp_ajax_lw_seo_redirect_update', [ $this, 'update_redirect' ] );
		add_action( 'wp_ajax_lw_seo_redirect_delete', [ $this, 'delete_redirect' ] );
		add_action( 'wp_ajax_lw_seo_redirect_get', [ $this, 'get_redirect' ] );
		add_action( 'wp_ajax_lw_seo_redirect_export', [ $this, 'export_redirects' ] );
		add_action( 'wp_ajax_lw_seo_redirect_import', [ $this, 'import_redirects' ] );
	}

	/**
	 * Verify AJAX nonce and capability.
	 *
	 * @return bool
	 */
	private function verify_request(): bool {
		if ( ! check_ajax_referer( 'lw_seo_redirects', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'lw-seo' ) ] );
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'lw-seo' ) ] );
			return false;
		}

		return true;
	}

	/**
	 * Add a new redirect.
	 *
	 * @return void
	 */
	public function add_redirect(): void {
		if ( ! $this->verify_request() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$destination = isset( $_POST['destination'] ) ? sanitize_text_field( wp_unslash( $_POST['destination'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$type = isset( $_POST['type'] ) ? (int) $_POST['type'] : 301;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$regex = isset( $_POST['regex'] ) && 'true' === $_POST['regex'];

		if ( empty( $source ) ) {
			wp_send_json_error( [ 'message' => __( 'Source URL is required.', 'lw-seo' ) ] );
			return;
		}

		// Validate regex pattern if enabled.
		if ( $regex ) {
			$pattern = '@' . str_replace( '@', '\\@', $source ) . '@i';
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( @preg_match( $pattern, '' ) === false ) {
				wp_send_json_error( [ 'message' => __( 'Invalid regex pattern.', 'lw-seo' ) ] );
				return;
			}
		}

		$id = Manager::add( $source, $destination, $type, $regex );

		if ( false === $id ) {
			wp_send_json_error( [ 'message' => __( 'Failed to add redirect.', 'lw-seo' ) ] );
			return;
		}

		wp_send_json_success(
			[
				'message'  => __( 'Redirect added successfully.', 'lw-seo' ),
				'id'       => $id,
				'redirect' => Manager::get( $id ),
			]
		);
	}

	/**
	 * Update an existing redirect.
	 *
	 * @return void
	 */
	public function update_redirect(): void {
		if ( ! $this->verify_request() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$id          = isset( $_POST['id'] ) ? (int) $_POST['id'] : -1;
		$source      = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$destination = isset( $_POST['destination'] ) ? sanitize_text_field( wp_unslash( $_POST['destination'] ) ) : '';
		$type        = isset( $_POST['type'] ) ? (int) $_POST['type'] : 301;
		$regex       = isset( $_POST['regex'] ) && 'true' === $_POST['regex'];
		// phpcs:enable

		if ( $id < 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid redirect ID.', 'lw-seo' ) ] );
			return;
		}

		if ( empty( $source ) ) {
			wp_send_json_error( [ 'message' => __( 'Source URL is required.', 'lw-seo' ) ] );
			return;
		}

		// Validate regex pattern if enabled.
		if ( $regex ) {
			$pattern = '@' . str_replace( '@', '\\@', $source ) . '@i';
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( @preg_match( $pattern, '' ) === false ) {
				wp_send_json_error( [ 'message' => __( 'Invalid regex pattern.', 'lw-seo' ) ] );
				return;
			}
		}

		$success = Manager::update( $id, $source, $destination, $type, $regex );

		if ( ! $success ) {
			wp_send_json_error( [ 'message' => __( 'Failed to update redirect.', 'lw-seo' ) ] );
			return;
		}

		wp_send_json_success(
			[
				'message'  => __( 'Redirect updated successfully.', 'lw-seo' ),
				'redirect' => Manager::get( $id ),
			]
		);
	}

	/**
	 * Delete a redirect.
	 *
	 * @return void
	 */
	public function delete_redirect(): void {
		if ( ! $this->verify_request() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : -1;

		if ( $id < 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid redirect ID.', 'lw-seo' ) ] );
			return;
		}

		$success = Manager::delete( $id );

		if ( ! $success ) {
			wp_send_json_error( [ 'message' => __( 'Failed to delete redirect.', 'lw-seo' ) ] );
			return;
		}

		wp_send_json_success( [ 'message' => __( 'Redirect deleted successfully.', 'lw-seo' ) ] );
	}

	/**
	 * Get a single redirect.
	 *
	 * @return void
	 */
	public function get_redirect(): void {
		if ( ! $this->verify_request() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : -1;

		if ( $id < 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid redirect ID.', 'lw-seo' ) ] );
			return;
		}

		$redirect = Manager::get( $id );

		if ( ! $redirect ) {
			wp_send_json_error( [ 'message' => __( 'Redirect not found.', 'lw-seo' ) ] );
			return;
		}

		wp_send_json_success( [ 'redirect' => $redirect ] );
	}

	/**
	 * Export redirects as CSV.
	 *
	 * @return void
	 */
	public function export_redirects(): void {
		if ( ! $this->verify_request() ) {
			return;
		}

		$csv = Manager::export_csv();

		wp_send_json_success(
			[
				'csv'      => $csv,
				'filename' => 'lw-seo-redirects-' . gmdate( 'Y-m-d' ) . '.csv',
			]
		);
	}

	/**
	 * Import redirects from CSV.
	 *
	 * @return void
	 */
	public function import_redirects(): void {
		if ( ! $this->verify_request() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$csv = isset( $_POST['csv'] ) ? sanitize_textarea_field( wp_unslash( $_POST['csv'] ) ) : '';

		if ( empty( $csv ) ) {
			wp_send_json_error( [ 'message' => __( 'No CSV data provided.', 'lw-seo' ) ] );
			return;
		}

		$result = Manager::import_csv( $csv );

		wp_send_json_success(
			[
				'message'  => sprintf(
					/* translators: 1: number of imported redirects, 2: number of skipped redirects */
					__( 'Imported %1$d redirects, skipped %2$d.', 'lw-seo' ),
					$result['imported'],
					$result['skipped']
				),
				'imported' => $result['imported'],
				'skipped'  => $result['skipped'],
				'errors'   => $result['errors'],
			]
		);
	}
}
