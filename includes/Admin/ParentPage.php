<?php
/**
 * LW Plugins Parent Page.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin;

/**
 * Handles the LW Plugins parent menu page.
 */
final class ParentPage {

	/**
	 * Parent menu slug.
	 */
	public const SLUG = 'lw-plugins';

	/**
	 * Get all LW plugins registry.
	 *
	 * @return array<string, array{name: string, description: string, icon: string, icon_color: string, constant: string, settings_page: string, github: string}>
	 */
	public static function get_plugins_registry(): array {
		return [
			'lw-seo'              => [
				'name'          => 'LW SEO',
				'description'   => __( 'Essential SEO features without the bloat. Meta tags, sitemaps, schema, and more.', 'lw-seo' ),
				'icon'          => 'dashicons-search',
				'icon_color'    => '#2271b1',
				'constant'      => 'LW_SEO_VERSION',
				'settings_page' => 'lw-seo',
				'github'        => 'https://github.com/lwplugins/lw-seo',
			],
			'lw-disable-commands' => [
				'name'          => 'LW Disable Commands',
				'description'   => __( 'Disable admin commands.', 'lw-seo' ),
				'icon'          => 'dashicons-editor-code',
				'icon_color'    => '#d63638',
				'constant'      => 'LW_DISABLE_COMMANDS_VERSION',
				'settings_page' => 'lw-disable-commands',
				'github'        => 'https://github.com/lwplugins/lw-disable-commands',
			],
			'lw-disable-comments' => [
				'name'          => 'LW Disable Comments',
				'description'   => __( 'Disable comments completely.', 'lw-seo' ),
				'icon'          => 'dashicons-admin-comments',
				'icon_color'    => '#d63638',
				'constant'      => 'LW_DISABLE_COMMENTS_VERSION',
				'settings_page' => 'lw-disable-comments',
				'github'        => 'https://github.com/lwplugins/lw-disable-comments',
			],
		];
	}

	/**
	 * Register the parent menu if not exists.
	 *
	 * @return void
	 */
	public static function maybe_register(): void {
		global $admin_page_hooks;

		if ( ! empty( $admin_page_hooks[ self::SLUG ] ) ) {
			return;
		}

		add_menu_page(
			__( 'LW Plugins', 'lw-seo' ),
			__( 'LW Plugins', 'lw-seo' ),
			'manage_options',
			self::SLUG,
			[ self::class, 'render' ],
			'dashicons-superhero-alt',
			80
		);
	}

	/**
	 * Render the parent page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap lw-plugins-overview">
			<h1><?php esc_html_e( 'LW Plugins', 'lw-seo' ); ?></h1>
			<p><?php esc_html_e( 'Lightweight plugins for WordPress - minimal footprint, maximum impact.', 'lw-seo' ); ?></p>

			<div class="lw-plugins-cards" style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
				<?php self::render_all_plugin_cards(); ?>

				<?php
				/**
				 * Add additional plugin cards to the LW Plugins overview page.
				 *
				 * @since 1.0.3
				 */
				do_action( 'lw_plugins_overview_cards' );
				?>
			</div>

			<div class="lw-plugins-footer" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccd0d4;">
				<p>
					<a href="https://github.com/lwplugins" target="_blank">GitHub</a> |
					<a href="https://lwplugins.com" target="_blank">Website</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render all plugin cards from registry.
	 *
	 * @return void
	 */
	private static function render_all_plugin_cards(): void {
		$plugins = self::get_plugins_registry();

		foreach ( $plugins as $slug => $plugin ) {
			self::render_plugin_card( $slug, $plugin );
		}
	}

	/**
	 * Render a single plugin card.
	 *
	 * @param string $slug   Plugin slug.
	 * @param array  $plugin Plugin data.
	 * @return void
	 */
	private static function render_plugin_card( string $slug, array $plugin ): void {
		$is_active = defined( $plugin['constant'] );
		?>
		<div class="lw-plugin-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; width: 300px;">
			<h2 style="margin-top: 0;">
				<span class="dashicons <?php echo esc_attr( $plugin['icon'] ); ?>" style="color: <?php echo esc_attr( $plugin['icon_color'] ); ?>;"></span>
				<?php echo esc_html( $plugin['name'] ); ?>
				<?php if ( $is_active ) : ?>
					<span style="display: inline-block; background: #00a32a; color: #fff; font-size: 11px; padding: 2px 6px; border-radius: 3px; margin-left: 8px; vertical-align: middle;"><?php esc_html_e( 'Active', 'lw-seo' ); ?></span>
				<?php endif; ?>
			</h2>
			<p><?php echo esc_html( $plugin['description'] ); ?></p>
			<p>
				<?php if ( $is_active ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $plugin['settings_page'] ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Settings', 'lw-seo' ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( $plugin['github'] ); ?>" class="button" target="_blank">
						<?php esc_html_e( 'Get Plugin', 'lw-seo' ); ?>
					</a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}
}
