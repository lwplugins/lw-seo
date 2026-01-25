<?php
/**
 * Content Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

/**
 * Handles the Content Types settings tab.
 */
final class Tab_Content implements Tab_Interface {

	use Field_Renderer;

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'content';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Content', 'lw-seo' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-admin-post';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Content Types', 'lw-seo' ); ?></h2>

		<div class="lw-seo-section-description">
			<p><?php esc_html_e( 'Title templates and indexing settings for different content types.', 'lw-seo' ); ?></p>
			<p><code>%%title%%, %%sitename%%, %%sep%%, %%category%%, %%author%%, %%date%%, %%term_title%%</code></p>
		</div>

		<?php
		$this->render_posts_section();
		$this->render_pages_section();
		$this->render_taxonomies_section();
		$this->render_archives_section();
	}

	/**
	 * Render posts section.
	 *
	 * @return void
	 */
	private function render_posts_section(): void {
		?>
		<h3><?php esc_html_e( 'Posts', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="title_post"><?php esc_html_e( 'Title Template', 'lw-seo' ); ?></label>
				</th>
				<td><?php $this->render_text_field( [ 'name' => 'title_post' ] ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Index', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'noindex_post',
							'label' => __( 'Set posts to noindex by default', 'lw-seo' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render pages section.
	 *
	 * @return void
	 */
	private function render_pages_section(): void {
		?>
		<h3><?php esc_html_e( 'Pages', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="title_page"><?php esc_html_e( 'Title Template', 'lw-seo' ); ?></label>
				</th>
				<td><?php $this->render_text_field( [ 'name' => 'title_page' ] ); ?></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render taxonomies section.
	 *
	 * @return void
	 */
	private function render_taxonomies_section(): void {
		?>
		<h3><?php esc_html_e( 'Taxonomies', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="title_category"><?php esc_html_e( 'Categories Title', 'lw-seo' ); ?></label>
				</th>
				<td><?php $this->render_text_field( [ 'name' => 'title_category' ] ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Categories Index', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'noindex_category',
							'label' => __( 'Set categories to noindex', 'lw-seo' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Tags Index', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'noindex_post_tag',
							'label' => __( 'Set tags to noindex', 'lw-seo' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render archives section.
	 *
	 * @return void
	 */
	private function render_archives_section(): void {
		?>
		<h3><?php esc_html_e( 'Archives', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Author Archives', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'noindex_author',
							'label' => __( 'Set author archives to noindex', 'lw-seo' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Date Archives', 'lw-seo' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'noindex_date',
							'label' => __( 'Set date archives to noindex', 'lw-seo' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}
