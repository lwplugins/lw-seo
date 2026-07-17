<?php
/**
 * Migration AJAX Handler class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration;

use LightweightPlugins\SEO\Migration\RankMath\Migrator as RankMathMigrator;
use LightweightPlugins\SEO\Migration\Yoast\Migrator as YoastMigrator;

/**
 * Handles AJAX requests for SEO data migration.
 */
final class Ajax {

	/**
	 * Nonce action name.
	 */
	private const NONCE_ACTION = 'lw_seo_migration';

	/**
	 * Provider slug → Migrator class map.
	 */
	private const PROVIDERS = [
		'rankmath' => RankMathMigrator::class,
		'yoast'    => YoastMigrator::class,
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_lw_seo_migration_detect', [ $this, 'detect' ] );
		add_action( 'wp_ajax_lw_seo_migration_run', [ $this, 'run' ] );
	}

	/**
	 * Resolve the requested provider slug, defaulting to RankMath.
	 *
	 * @return string A key of self::PROVIDERS.
	 */
	private function resolve_provider(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'rankmath';
		return isset( self::PROVIDERS[ $provider ] ) ? $provider : 'rankmath';
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

		$class    = self::PROVIDERS[ $this->resolve_provider() ];
		$migrator = new $class();
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
		$class    = self::PROVIDERS[ $this->resolve_provider() ];
		$migrator = new $class( $dry_run );
		$result   = $migrator->run();

		wp_send_json_success( $result );
	}
}
