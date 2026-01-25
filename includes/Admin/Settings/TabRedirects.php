<?php
/**
 * Redirects Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Redirects\Manager;

/**
 * Handles the Redirects settings tab.
 */
final class TabRedirects implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'redirects';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Redirects', 'lw-seo' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-randomize';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		$redirects = Manager::get_all();
		?>
		<h2><?php esc_html_e( 'Redirect Manager', 'lw-seo' ); ?></h2>

		<div class="lw-seo-section-description">
			<p><?php esc_html_e( 'Create and manage URL redirects to prevent 404 errors and preserve SEO value.', 'lw-seo' ); ?></p>
		</div>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Redirects', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'redirects_enabled',
							'label' => __( 'Process redirect rules', 'lw-seo' ),
						]
					);
					?>
				</td>
			</tr>
		</table>

		<hr>

		<h3><?php esc_html_e( 'Add New Redirect', 'lw-seo' ); ?></h3>

		<div class="lw-seo-redirect-form" id="lw-seo-redirect-form">
			<input type="hidden" id="lw-redirect-edit-id" value="">

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="lw-redirect-source"><?php esc_html_e( 'Source URL', 'lw-seo' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="lw-redirect-source"
							class="regular-text code"
							placeholder="/old-page/"
						>
						<p class="description">
							<?php esc_html_e( 'The old URL path that should redirect (e.g., /old-page/).', 'lw-seo' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="lw-redirect-destination"><?php esc_html_e( 'Destination URL', 'lw-seo' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="lw-redirect-destination"
							class="regular-text code"
							placeholder="/new-page/ or https://example.com/page/"
						>
						<p class="description">
							<?php esc_html_e( 'The new URL to redirect to. Leave empty for 410/451 types.', 'lw-seo' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="lw-redirect-type"><?php esc_html_e( 'Redirect Type', 'lw-seo' ); ?></label>
					</th>
					<td>
						<select id="lw-redirect-type">
							<?php foreach ( Manager::TYPES as $code => $label ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>">
									<?php echo esc_html( $code . ' - ' . $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Regex', 'lw-seo' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="lw-redirect-regex" value="1">
							<?php esc_html_e( 'Source is a regular expression', 'lw-seo' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Advanced: Use regex patterns like ^/category/(.*)$ with $1 in destination.', 'lw-seo' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<button type="button" id="lw-redirect-save" class="button button-primary">
							<?php esc_html_e( 'Add Redirect', 'lw-seo' ); ?>
						</button>
						<button type="button" id="lw-redirect-cancel" class="button" style="display:none;">
							<?php esc_html_e( 'Cancel', 'lw-seo' ); ?>
						</button>
						<span id="lw-redirect-message" class="lw-seo-message"></span>
					</td>
				</tr>
			</table>
		</div>

		<hr>

		<h3>
			<?php esc_html_e( 'Existing Redirects', 'lw-seo' ); ?>
			<span class="count">(<?php echo esc_html( (string) count( $redirects ) ); ?>)</span>
		</h3>

		<?php if ( empty( $redirects ) ) : ?>
			<p class="description"><?php esc_html_e( 'No redirects configured yet.', 'lw-seo' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped" id="lw-seo-redirects-table">
				<thead>
					<tr>
						<th class="column-source"><?php esc_html_e( 'Source', 'lw-seo' ); ?></th>
						<th class="column-destination"><?php esc_html_e( 'Destination', 'lw-seo' ); ?></th>
						<th class="column-type"><?php esc_html_e( 'Type', 'lw-seo' ); ?></th>
						<th class="column-hits"><?php esc_html_e( 'Hits', 'lw-seo' ); ?></th>
						<th class="column-actions"><?php esc_html_e( 'Actions', 'lw-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $redirects as $id => $redirect ) : ?>
						<tr data-id="<?php echo esc_attr( (string) $id ); ?>">
							<td class="column-source">
								<code><?php echo esc_html( $redirect['source'] ); ?></code>
								<?php if ( $redirect['regex'] ) : ?>
									<span class="lw-seo-badge"><?php esc_html_e( 'Regex', 'lw-seo' ); ?></span>
								<?php endif; ?>
							</td>
							<td class="column-destination">
								<?php if ( in_array( $redirect['type'], [ 410, 451 ], true ) ) : ?>
									<em><?php esc_html_e( 'N/A', 'lw-seo' ); ?></em>
								<?php else : ?>
									<code><?php echo esc_html( $redirect['destination'] ); ?></code>
								<?php endif; ?>
							</td>
							<td class="column-type">
								<span class="lw-seo-type-<?php echo esc_attr( (string) $redirect['type'] ); ?>">
									<?php echo esc_html( (string) $redirect['type'] ); ?>
								</span>
							</td>
							<td class="column-hits">
								<?php echo esc_html( (string) $redirect['hits'] ); ?>
								<?php if ( ! empty( $redirect['last_accessed'] ) ) : ?>
									<br>
									<small><?php echo esc_html( $redirect['last_accessed'] ); ?></small>
								<?php endif; ?>
							</td>
							<td class="column-actions">
								<button type="button" class="button button-small lw-redirect-edit" data-id="<?php echo esc_attr( (string) $id ); ?>">
									<?php esc_html_e( 'Edit', 'lw-seo' ); ?>
								</button>
								<button type="button" class="button button-small button-link-delete lw-redirect-delete" data-id="<?php echo esc_attr( (string) $id ); ?>">
									<?php esc_html_e( 'Delete', 'lw-seo' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<hr>

		<h3><?php esc_html_e( 'Import / Export', 'lw-seo' ); ?></h3>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Export', 'lw-seo' ); ?></th>
				<td>
					<button type="button" id="lw-redirect-export" class="button">
						<?php esc_html_e( 'Download CSV', 'lw-seo' ); ?>
					</button>
					<p class="description">
						<?php esc_html_e( 'Export all redirects as a CSV file.', 'lw-seo' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="lw-redirect-import-file"><?php esc_html_e( 'Import', 'lw-seo' ); ?></label>
				</th>
				<td>
					<input type="file" id="lw-redirect-import-file" accept=".csv">
					<button type="button" id="lw-redirect-import" class="button">
						<?php esc_html_e( 'Import CSV', 'lw-seo' ); ?>
					</button>
					<p class="description">
						<?php esc_html_e( 'Import redirects from a CSV file. Format: source,destination,type,regex', 'lw-seo' ); ?>
					</p>
					<div id="lw-redirect-import-result"></div>
				</td>
			</tr>
		</table>

		<?php
		// Output redirect data for JS.
		$this->output_redirect_data( $redirects );
	}

	/**
	 * Output redirect data as JSON for JavaScript.
	 *
	 * @param array $redirects Redirects array.
	 * @return void
	 */
	private function output_redirect_data( array $redirects ): void {
		?>
		<script>
			var lwSeoRedirects = <?php echo wp_json_encode( $redirects ); ?>;
			var lwSeoRedirectTypes = <?php echo wp_json_encode( Manager::TYPES ); ?>;
		</script>
		<?php
	}
}
