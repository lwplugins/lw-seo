<?php
/**
 * Migration Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

/**
 * Handles the Import/Migration settings tab.
 */
final class TabMigration implements TabInterface {

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'migration';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Import', 'lw-seo' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-migrate';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Import SEO Data', 'lw-seo' ); ?></h2>

		<div class="lw-seo-section-description">
			<p><?php esc_html_e( 'Import SEO data from other SEO plugins. Existing LW SEO data will not be overwritten.', 'lw-seo' ); ?></p>
		</div>

		<h3><?php esc_html_e( 'RankMath SEO', 'lw-seo' ); ?></h3>

		<div id="lw-migration-detect-area">
			<p class="description">
				<?php esc_html_e( 'Detect RankMath SEO data in your database for migration.', 'lw-seo' ); ?>
			</p>
			<p>
				<button type="button" id="lw-migration-detect" class="button">
					<?php esc_html_e( 'Detect Data', 'lw-seo' ); ?>
				</button>
				<span id="lw-migration-detect-spinner" class="spinner"></span>
			</p>
		</div>

		<div id="lw-migration-results" style="display:none;">
			<div id="lw-migration-detect-results" class="lw-seo-migration-results"></div>

			<div id="lw-migration-actions" style="display:none;">
				<p>
					<button type="button" id="lw-migration-preview" class="button">
						<?php esc_html_e( 'Preview Migration', 'lw-seo' ); ?>
					</button>
					<button type="button" id="lw-migration-run" class="button button-primary">
						<?php esc_html_e( 'Run Migration', 'lw-seo' ); ?>
					</button>
					<span id="lw-migration-run-spinner" class="spinner"></span>
				</p>
			</div>

			<div id="lw-migration-run-results" style="display:none;"></div>
		</div>
		<?php
	}
}
