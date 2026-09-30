<?php
/**
 * LW Site Manager Integration.
 *
 * Registers SEO abilities with LW Site Manager.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\SiteManager;

use LightweightPlugins\SEO\Integrations\IntegrationInterface;

/**
 * Hooks into LW Site Manager to register SEO abilities.
 */
final class Integration implements IntegrationInterface {

	/**
	 * Integration ID.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'site-manager';
	}

	/**
	 * LW Site Manager is active. Its registration actions run on init
	 * (wp_abilities_api_*_init), after the Loader.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return defined( 'LW_SITE_MANAGER_VERSION' );
	}

	/**
	 * Hook into LW Site Manager's registration actions.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'lw_site_manager_register_categories', [ self::class, 'register_category' ] );
		add_action( 'lw_site_manager_register_abilities', [ self::class, 'register_abilities' ] );
	}

	/**
	 * Register the SEO ability category.
	 *
	 * @return void
	 */
	public static function register_category(): void {
		wp_register_ability_category(
			'seo',
			[
				'label'       => __( 'SEO', 'lw-seo' ),
				'description' => __( 'Search engine optimization abilities', 'lw-seo' ),
			]
		);
	}

	/**
	 * Register SEO abilities.
	 *
	 * @param object $permissions Permission manager from Site Manager.
	 * @return void
	 */
	public static function register_abilities( object $permissions ): void {
		SeoAbilities::register( $permissions );
	}
}
