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
final class Parent_Page {

	/**
	 * Parent menu slug.
	 */
	public const SLUG = 'lw-plugins';

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
				<?php self::render_seo_card(); ?>

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
	 * Render SEO plugin card.
	 *
	 * @return void
	 */
	private static function render_seo_card(): void {
		?>
		<div class="lw-plugin-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; width: 300px;">
			<h2 style="margin-top: 0;">
				<span class="dashicons dashicons-search" style="color: #2271b1;"></span>
				<?php esc_html_e( 'LW SEO', 'lw-seo' ); ?>
			</h2>
			<p><?php esc_html_e( 'Essential SEO features without the bloat. Meta tags, sitemaps, schema, and more.', 'lw-seo' ); ?></p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lw-seo' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Settings', 'lw-seo' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
