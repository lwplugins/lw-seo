<?php
/**
 * LW Site Manager Integration.
 *
 * Registers SEO abilities when LW Site Manager is active.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\SiteManager;

/**
 * Hooks into LW Site Manager to register SEO abilities.
 */
final class Integration {

	/**
	 * Initialize hooks. Safe to call even if Site Manager is not active.
	 *
	 * @return void
	 */
	public static function init(): void {
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
