<?php
/**
 * Sitemap Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

use LightweightPlugins\SEO\Content\PostTypes;
use LightweightPlugins\SEO\Sitemap\ProviderRegistry;

/**
 * Handles the XML Sitemap settings tab.
 */
final class TabSitemap implements TabInterface {

	use FieldRendererTrait;
	use TypeTogglesTrait;

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'sitemap';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Sitemap', 'lw-seo' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-networking';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		$sitemap_url = home_url( '/sitemap.xml' );
		?>
		<h2><?php esc_html_e( 'XML Sitemap', 'lw-seo' ); ?></h2>

		<div class="lw-seo-section-description">
			<p>
				<?php esc_html_e( 'Your sitemap:', 'lw-seo' ); ?>
				<a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank"><?php echo esc_html( $sitemap_url ); ?></a>
			</p>
		</div>

		<table class="form-table">
			<?php
			$this->render_checkbox_row( __( 'XML Sitemap', 'lw-seo' ), 'sitemap_enabled', __( 'Enable XML sitemap', 'lw-seo' ) );
			$this->render_content_rows();
			$this->render_taxonomy_rows();
			?>
		</table>
		<?php
	}

	/**
	 * Post type rows.
	 *
	 * @return void
	 */
	private function render_content_rows(): void {
		$this->render_checkbox_row( __( 'Include Posts', 'lw-seo' ), 'sitemap_posts', __( 'Include posts in sitemap', 'lw-seo' ) );
		$this->render_checkbox_row( __( 'Include Pages', 'lw-seo' ), 'sitemap_pages', __( 'Include pages in sitemap', 'lw-seo' ) );

		$custom = array_diff_key( PostTypes::post_types(), ProviderRegistry::POST_TYPE_TOGGLES );
		if ( [] === $custom ) {
			return;
		}
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Custom Post Types', 'lw-seo' ); ?></th>
			<td>
				<?php $this->render_type_toggles( 'sitemap_post_types', $custom, true ); ?>
				<p class="description"><?php esc_html_e( 'New custom post types are included automatically. Content set to noindex is always left out.', 'lw-seo' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Taxonomy rows.
	 *
	 * @return void
	 */
	private function render_taxonomy_rows(): void {
		$this->render_checkbox_row( __( 'Include Categories', 'lw-seo' ), 'sitemap_categories', __( 'Include categories in sitemap', 'lw-seo' ) );
		$this->render_checkbox_row( __( 'Include Tags', 'lw-seo' ), 'sitemap_tags', __( 'Include tags in sitemap', 'lw-seo' ) );

		$custom = array_diff_key( PostTypes::taxonomies(), ProviderRegistry::TAXONOMY_TOGGLES );
		if ( [] === $custom ) {
			return;
		}
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Custom Taxonomies', 'lw-seo' ); ?></th>
			<td>
				<?php $this->render_type_toggles( 'sitemap_taxonomies', $custom, false ); ?>
				<p class="description"><?php esc_html_e( 'Custom taxonomy archives are only listed when switched on here.', 'lw-seo' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * One checkbox row.
	 *
	 * @param string $heading Row heading.
	 * @param string $name    Option key.
	 * @param string $label   Checkbox label.
	 * @return void
	 */
	private function render_checkbox_row( string $heading, string $name, string $label ): void {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $heading ); ?></th>
			<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => $name,
						'label' => $label,
					]
				);
				?>
			</td>
		</tr>
		<?php
	}
}
