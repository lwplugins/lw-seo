<?php
/**
 * Migration AJAX Handler class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration;

use LightweightPlugins\SEO\Migration\RankMath\Migrator;

/**
 * Handles AJAX requests for SEO data migration.
 */
final class Ajax {

	/**
	 * Nonce action name.
	 */
	private const NONCE_ACTION = 'lw_seo_migration';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_lw_seo_migration_detect', [ $this, 'detect' ] );
		add_action( 'wp_ajax_lw_seo_migration_run', [ $this, 'run' ] );
	}

	/**
	 * Verify AJAX nonce and capability.
	 *
	 * @return bool
	 */
	private function verify_request(): bool {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
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
	 * Detect available migration data.
	 *
	 * @return void
	 */
	public function detect(): void {
		if ( ! $this->verify_request() ) {
			return;
		}

		$migrator = new Migrator();
		$result   = $migrator->detect();

		wp_send_json_success( $result );
	}

	/**
	 * Run migration.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( ! $this->verify_request() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$dry_run  = isset( $_POST['dry_run'] ) && 'true' === $_POST['dry_run'];
		$migrator = new Migrator( $dry_run );
		$result   = $migrator->run();

		wp_send_json_success( $result );
	}
}
