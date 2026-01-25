<?php
/**
 * Local SEO Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

use LightweightPlugins\SEO\Admin\Data\BusinessTypes;
use LightweightPlugins\SEO\Options;

/**
 * Handles the Local SEO settings tab.
 */
final class TabLocal implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Days of the week (lazy loaded to avoid early translation).
	 *
	 * @var array<string, string>|null
	 */
	private ?array $days = null;

	/**
	 * Get days of the week with translations.
	 *
	 * @return array<string, string>
	 */
	private function get_days(): array {
		if ( null === $this->days ) {
			$this->days = [
				'monday'    => __( 'Monday', 'lw-seo' ),
				'tuesday'   => __( 'Tuesday', 'lw-seo' ),
				'wednesday' => __( 'Wednesday', 'lw-seo' ),
				'thursday'  => __( 'Thursday', 'lw-seo' ),
				'friday'    => __( 'Friday', 'lw-seo' ),
				'saturday'  => __( 'Saturday', 'lw-seo' ),
				'sunday'    => __( 'Sunday', 'lw-seo' ),
			];
		}
		return $this->days;
	}

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'local';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Local SEO', 'lw-seo' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-location';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Local SEO', 'lw-seo' ); ?></h2>

		<div class="lw-seo-section-description">
			<p><?php esc_html_e( 'Add LocalBusiness structured data to help your business appear in local search results.', 'lw-seo' ); ?></p>
		</div>

		<?php
		$this->render_general_section();
		$this->render_address_section();
		$this->render_contact_section();
		$this->render_hours_section();
		$this->render_geo_section();
	}

	/**
	 * Render general section.
	 *
	 * @return void
	 */
	private function render_general_section(): void {
		?>
		<h3><?php esc_html_e( 'Business Information', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Local SEO', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'local_enabled',
							'label' => __( 'Enable LocalBusiness schema', 'lw-seo' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="local_business_type"><?php esc_html_e( 'Business Type', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php $this->render_business_type_field(); ?>
					<p class="description">
						<?php esc_html_e( 'Select the type that best describes your business.', 'lw-seo' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="local_business_name"><?php esc_html_e( 'Business Name', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'local_business_name',
							'placeholder' => get_bloginfo( 'name' ),
							'class'       => 'regular-text',
						]
					);
					?>
					<p class="description">
						<?php esc_html_e( 'Leave empty to use site name.', 'lw-seo' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="local_description"><?php esc_html_e( 'Business Description', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_textarea_field(
						[
							'name' => 'local_description',
							'rows' => 3,
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="local_price_range"><?php esc_html_e( 'Price Range', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_select_field(
						[
							'name'    => 'local_price_range',
							'options' => [
								''     => __( 'Not specified', 'lw-seo' ),
								'$'    => '$',
								'$$'   => '$$',
								'$$$'  => '$$$',
								'$$$$' => '$$$$',
							],
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render business type field.
	 *
	 * @return void
	 */
	private function render_business_type_field(): void {
		$current = Options::get( 'local_business_type', 'LocalBusiness' );
		$groups  = BusinessTypes::get_grouped();

		echo '<select name="' . esc_attr( Options::OPTION_NAME ) . '[local_business_type]" id="local_business_type">';

		foreach ( $groups as $group_label => $types ) {
			echo '<optgroup label="' . esc_attr( $group_label ) . '">';
			foreach ( $types as $value => $label ) {
				echo '<option value="' . esc_attr( $value ) . '"' . selected( $current, $value, false ) . '>';
				echo esc_html( $label );
				echo '</option>';
			}
			echo '</optgroup>';
		}

		echo '</select>';
	}

	/**
	 * Render address section.
	 *
	 * @return void
	 */
	private function render_address_section(): void {
		?>
		<h3><?php esc_html_e( 'Address', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="local_street"><?php esc_html_e( 'Street Address', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'  => 'local_street',
							'class' => 'regular-text',
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="local_street_2"><?php esc_html_e( 'Address Line 2', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'  => 'local_street_2',
							'class' => 'regular-text',
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="local_city"><?php esc_html_e( 'City', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'  => 'local_city',
							'class' => 'regular-text',
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="local_state"><?php esc_html_e( 'State / Region', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'  => 'local_state',
							'class' => 'regular-text',
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="local_zip"><?php esc_html_e( 'Postal Code', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'  => 'local_zip',
							'class' => 'small-text',
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="local_country"><?php esc_html_e( 'Country', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'local_country',
							'class'       => 'regular-text',
							'placeholder' => 'HU',
						]
					);
					?>
					<p class="description">
						<?php esc_html_e( 'Use ISO 3166-1 alpha-2 code (e.g., HU, US, DE).', 'lw-seo' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render contact section.
	 *
	 * @return void
	 */
	private function render_contact_section(): void {
		?>
		<h3><?php esc_html_e( 'Contact', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="local_phone"><?php esc_html_e( 'Phone', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'local_phone',
							'class'       => 'regular-text',
							'placeholder' => '+36 1 234 5678',
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="local_email"><?php esc_html_e( 'Email', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'local_email',
							'class'       => 'regular-text',
							'placeholder' => 'info@example.com',
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render opening hours section.
	 *
	 * @return void
	 */
	private function render_hours_section(): void {
		?>
		<h3><?php esc_html_e( 'Opening Hours', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Use Opening Hours', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'local_hours_enabled',
							'label' => __( 'Enable opening hours in schema', 'lw-seo' ),
						]
					);
					?>
				</td>
			</tr>
		</table>

		<table class="form-table lw-seo-hours-table">
			<?php foreach ( $this->get_days() as $day_key => $day_label ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $day_label ); ?></th>
					<td>
						<?php $this->render_day_hours( $day_key ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Render hours fields for a day.
	 *
	 * @param string $day Day key.
	 * @return void
	 */
	private function render_day_hours( string $day ): void {
		$closed_name = "local_hours_{$day}_closed";
		$open_name   = "local_hours_{$day}_open";
		$close_name  = "local_hours_{$day}_close";

		$is_closed = Options::get( $closed_name, false );
		$open_val  = Options::get( $open_name, '' );
		$close_val = Options::get( $close_name, '' );

		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( Options::OPTION_NAME . '[' . $closed_name . ']' ); ?>"
				value="1"
				<?php checked( $is_closed ); ?>
			/>
			<?php esc_html_e( 'Closed', 'lw-seo' ); ?>
		</label>
		&nbsp;&nbsp;

		<label>
			<?php esc_html_e( 'Open:', 'lw-seo' ); ?>
			<input
				type="time"
				name="<?php echo esc_attr( Options::OPTION_NAME . '[' . $open_name . ']' ); ?>"
				value="<?php echo esc_attr( $open_val ); ?>"
				class="small-text"
			/>
		</label>
		&nbsp;

		<label>
			<?php esc_html_e( 'Close:', 'lw-seo' ); ?>
			<input
				type="time"
				name="<?php echo esc_attr( Options::OPTION_NAME . '[' . $close_name . ']' ); ?>"
				value="<?php echo esc_attr( $close_val ); ?>"
				class="small-text"
			/>
		</label>
		<?php
	}

	/**
	 * Render geo coordinates section.
	 *
	 * @return void
	 */
	private function render_geo_section(): void {
		?>
		<h3><?php esc_html_e( 'Coordinates', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="local_lat"><?php esc_html_e( 'Latitude', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'local_lat',
							'class'       => 'small-text',
							'placeholder' => '47.4979',
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="local_lng"><?php esc_html_e( 'Longitude', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'local_lng',
							'class'       => 'small-text',
							'placeholder' => '19.0402',
						]
					);
					?>
				</td>
			</tr>
		</table>
		<p class="description">
			<?php
			printf(
				/* translators: %s: Google Maps URL */
				esc_html__( 'Find coordinates at %s - right-click location and select "What\'s here?".', 'lw-seo' ),
				'<a href="https://maps.google.com" target="_blank">Google Maps</a>'
			);
			?>
		</p>
		<?php
	}
}
