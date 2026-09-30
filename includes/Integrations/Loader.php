<?php
/**
 * Integrations loader.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations;

/**
 * Registers the integrations whose plugin or theme is present. Runs on
 * after_setup_theme: the active theme (Bricks) and every plugin are loaded
 * by then, and nothing an integration hooks has run yet.
 *
 * @internal Loader::is_active() and the lw_seo_integration_enabled filter
 *           are not a public API yet.
 */
final class Loader {

	/**
	 * IDs of the integrations the last boot registered.
	 *
	 * @var array<string, true>
	 */
	private static array $active = [];

	/**
	 * Integrations to consider (null = the built-in ones).
	 *
	 * @var array<int, IntegrationInterface>|null
	 */
	private ?array $integrations;

	/**
	 * Constructor.
	 *
	 * @param array<int, IntegrationInterface>|null $integrations Integrations (null = the built-in ones).
	 */
	public function __construct( ?array $integrations = null ) {
		$this->integrations = $integrations;
	}

	/**
	 * Hook the boot.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'after_setup_theme', [ $this, 'boot' ], 1 );
	}

	/**
	 * Register every available, enabled integration.
	 *
	 * @return void
	 */
	public function boot(): void {
		self::$active = [];

		foreach ( $this->integrations ?? self::defaults() as $integration ) {
			$id = $integration->id();

			/**
			 * Whether LW SEO starts an integration whose plugin or theme is present.
			 *
			 * @internal Not a public API yet.
			 *
			 * @param bool   $enabled Default true.
			 * @param string $id      bricks, polylang, woocommerce or site-manager.
			 */
			if ( ! $integration->is_available() || ! apply_filters( 'lw_seo_integration_enabled', true, $id ) ) {
				continue;
			}

			$integration->register();
			self::$active[ $id ] = true;
		}
	}

	/**
	 * Whether an integration was registered (false before after_setup_theme).
	 *
	 * @param string $id Integration ID.
	 * @return bool
	 */
	public static function is_active( string $id ): bool {
		return isset( self::$active[ $id ] );
	}

	/**
	 * The built-in integrations.
	 *
	 * @return array<int, IntegrationInterface>
	 */
	private static function defaults(): array {
		return [];
	}
}
