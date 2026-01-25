<?php
/**
 * 404 Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

/**
 * Handles the 404 settings tab.
 */
final class Tab404 implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return '404';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( '404', 'lw-seo' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-dismiss';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( '404 Error Handling', 'lw-seo' ); ?></h2>

		<div class="lw-seo-section-description">
			<p><?php esc_html_e( 'Configure how 404 (Not Found) errors are handled on your site.', 'lw-seo' ); ?></p>
		</div>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Redirect 404 to Homepage', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'redirect_404_to_home',
							'label' => __( 'Automatically redirect all 404 errors to the homepage', 'lw-seo' ),
						]
					);
					?>
					<p class="description">
						<?php esc_html_e( 'When enabled, visitors who land on a non-existent page will be redirected to your homepage with a 302 (temporary) redirect.', 'lw-seo' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}
}
