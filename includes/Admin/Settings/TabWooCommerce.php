<?php
/**
 * WooCommerce Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

/**
 * Handles the WooCommerce settings tab.
 */
final class TabWooCommerce implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'woocommerce';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'WooCommerce', 'lw-seo' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-cart';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'WooCommerce SEO', 'lw-seo' ); ?></h2>

		<div class="lw-seo-section-description">
			<p><?php esc_html_e( 'Optimize your WooCommerce products for search engines and social media.', 'lw-seo' ); ?></p>
		</div>

		<?php
		$this->render_general_section();
		$this->render_schema_section();
		$this->render_sitemap_section();
	}

	/**
	 * Render general settings section.
	 *
	 * @return void
	 */
	private function render_general_section(): void {
		?>
		<h3><?php esc_html_e( 'General', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'WooCommerce SEO', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'    => 'woo_enabled',
							'label'   => __( 'Enable WooCommerce SEO features', 'lw-seo' ),
							'default' => true,
						]
					);
					?>
					<p class="description">
						<?php esc_html_e( 'Adds product-specific OpenGraph tags, Schema.org Product markup, and more.', 'lw-seo' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="title_product"><?php esc_html_e( 'Product Title', 'lw-seo' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'title_product',
							'placeholder' => '%%title%% %%sep%% %%sitename%%',
							'class'       => 'large-text',
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Noindex Products', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'noindex_product',
							'label' => __( 'Add noindex to product pages', 'lw-seo' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render schema settings section.
	 *
	 * @return void
	 */
	private function render_schema_section(): void {
		?>
		<h3><?php esc_html_e( 'Schema.org', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Product Schema', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'    => 'woo_schema_enabled',
							'label'   => __( 'Enable Product Schema.org markup', 'lw-seo' ),
							'default' => true,
						]
					);
					?>
					<p class="description">
						<?php esc_html_e( 'Adds structured data for products including price, availability, and reviews.', 'lw-seo' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Include Reviews', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'    => 'woo_schema_reviews',
							'label'   => __( 'Include product reviews in Schema', 'lw-seo' ),
							'default' => true,
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render sitemap settings section.
	 *
	 * @return void
	 */
	private function render_sitemap_section(): void {
		?>
		<h3><?php esc_html_e( 'Sitemap', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Products in Sitemap', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'    => 'sitemap_products',
							'label'   => __( 'Include products in XML sitemap', 'lw-seo' ),
							'default' => true,
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Product Categories', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'    => 'sitemap_product_cat',
							'label'   => __( 'Include product categories in XML sitemap', 'lw-seo' ),
							'default' => true,
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Product Tags', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'    => 'sitemap_product_tag',
							'label'   => __( 'Include product tags in XML sitemap', 'lw-seo' ),
							'default' => false,
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}
