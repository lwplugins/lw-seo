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

		<?php
		$this->render_provider_block(
			'rankmath',
			__( 'RankMath SEO', 'lw-seo' ),
			__( 'Detect RankMath SEO data in your database for migration.', 'lw-seo' )
		);
		$this->render_provider_block(
			'yoast',
			__( 'Yoast SEO', 'lw-seo' ),
			__( 'Detect Yoast SEO data in your database for migration.', 'lw-seo' )
		);
	}

	/**
	 * Render one detect/migrate block for a given provider.
	 *
	 * @param string $provider    Provider slug (rankmath|yoast).
	 * @param string $heading     Provider heading.
	 * @param string $detect_desc Detect description text.
	 * @return void
	 */
	private function render_provider_block( string $provider, string $heading, string $detect_desc ): void {
		?>
		<div class="lw-migration-provider" data-provider="<?php echo esc_attr( $provider ); ?>">
			<h3><?php echo esc_html( $heading ); ?></h3>

			<div class="lw-migration-detect-area">
				<p class="description"><?php echo esc_html( $detect_desc ); ?></p>
				<p>
					<button type="button" class="button lw-migration-detect">
						<?php esc_html_e( 'Detect Data', 'lw-seo' ); ?>
					</button>
					<span class="spinner lw-migration-detect-spinner"></span>
				</p>
			</div>

			<div class="lw-migration-results" style="display:none;">
				<div class="lw-migration-detect-results lw-seo-migration-results"></div>

				<div class="lw-migration-actions" style="display:none;">
					<p>
						<button type="button" class="button lw-migration-preview">
							<?php esc_html_e( 'Preview Migration', 'lw-seo' ); ?>
						</button>
						<button type="button" class="button button-primary lw-migration-run">
							<?php esc_html_e( 'Run Migration', 'lw-seo' ); ?>
						</button>
						<span class="spinner lw-migration-run-spinner"></span>
					</p>
				</div>

				<div class="lw-migration-run-results" style="display:none;"></div>
			</div>
		</div>
		<?php
	}
}
