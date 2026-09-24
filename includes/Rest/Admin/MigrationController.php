<?php
/**
 * Migration REST controller.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Rest\Admin;

use LightweightPlugins\SEO\Migration\MigratorInterface;
use LightweightPlugins\SEO\Migration\RankMath\Migrator as RankMathMigrator;
use LightweightPlugins\SEO\Migration\Yoast\Migrator as YoastMigrator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * GET (detect) and POST (run) lw-seo/v1/admin/migration/<provider>.
 */
final class MigrationController {

	/**
	 * Provider slug => migrator class.
	 */
	private const PROVIDERS = [
		'rankmath' => RankMathMigrator::class,
		'yoast'    => YoastMigrator::class,
	];

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			Routes::NAMESPACE,
			'/admin/migration/(?P<provider>[a-z0-9_-]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'detect' ],
					'permission_callback' => [ Routes::class, 'can_manage' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'run' ],
					'permission_callback' => [ Routes::class, 'can_manage' ],
				],
			]
		);
	}

	/**
	 * Detect what the source plugin left in the database.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function detect( WP_REST_Request $request ) {
		$migrator = $this->migrator( $request, false );

		return $migrator instanceof WP_Error ? $migrator : new WP_REST_Response( $migrator->detect() );
	}

	/**
	 * Run (or preview with dry_run) the migration.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function run( WP_REST_Request $request ) {
		$migrator = $this->migrator( $request, rest_sanitize_boolean( $request->get_param( 'dry_run' ) ?? false ) );

		return $migrator instanceof WP_Error ? $migrator : new WP_REST_Response( $migrator->run() );
	}

	/**
	 * Migrator for the requested provider.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param bool            $dry_run Simulate without writing.
	 * @return MigratorInterface|WP_Error
	 */
	private function migrator( WP_REST_Request $request, bool $dry_run ) {
		$provider = sanitize_key( (string) $request->get_param( 'provider' ) );

		if ( ! isset( self::PROVIDERS[ $provider ] ) ) {
			return new WP_Error( 'lw_seo_migration_invalid_provider', __( 'Unknown migration source.', 'lw-seo' ), [ 'status' => 400 ] );
		}

		$class = self::PROVIDERS[ $provider ];

		return new $class( $dry_run );
	}
}
