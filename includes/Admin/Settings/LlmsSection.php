<?php
/**
 * Llms.txt settings block.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

use LightweightPlugins\SEO\Content\PostTypes;
use LightweightPlugins\SEO\LlmsTxt\SectionCollector;
use LightweightPlugins\SEO\Options;

/**
 * Renders the llms.txt block of the AI / LLM tab.
 */
final class LlmsSection {

	use FieldRendererTrait;
	use TypeTogglesTrait;

	/**
	 * Render the block.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h3><?php esc_html_e( 'llms.txt File', 'lw-seo' ); ?></h3>
		<table class="form-table">
			<?php
			$this->render_general_rows();
			$this->render_content_rows();
			$this->render_extra_rows();
			?>
		</table>
		<?php
	}

	/**
	 * Enable, summary and intro rows.
	 *
	 * @return void
	 */
	private function render_general_rows(): void {
		$llms_url = home_url( '/llms.txt' );
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'llms.txt', 'lw-seo' ); ?></th>
			<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'llms_txt_enabled',
						'label' => __( 'Enable llms.txt for AI crawlers', 'lw-seo' ),
					]
				);
				?>
				<p class="description">
					<?php
					printf(
						/* translators: %1$s: llms.txt URL, %2$s: llmstxt.org link */
						esc_html__( 'Your llms.txt: %1$s — %2$s', 'lw-seo' ),
						'<a href="' . esc_url( $llms_url ) . '" target="_blank">' . esc_html( $llms_url ) . '</a>',
						'<a href="https://llmstxt.org/" target="_blank" rel="noopener">' . esc_html__( 'Learn more', 'lw-seo' ) . '</a>'
					);
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="llms_txt_summary"><?php esc_html_e( 'Summary', 'lw-seo' ); ?></label></th>
			<td>
				<?php
				$this->render_text_field(
					[
						'name'        => 'llms_txt_summary',
						'description' => __( 'One sentence shown as the summary line. Empty = the site tagline.', 'lw-seo' ),
					]
				);
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="llms_txt_intro"><?php esc_html_e( 'Introduction', 'lw-seo' ); ?></label></th>
			<td>
				<?php
				$this->render_textarea_field(
					[
						'name'        => 'llms_txt_intro',
						'description' => __( 'Optional Markdown text shown before the page lists. Do not use headings.', 'lw-seo' ),
					]
				);
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Content type, limit and link-target rows.
	 *
	 * @return void
	 */
	private function render_content_rows(): void {
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Content types', 'lw-seo' ); ?></th>
			<td>
				<?php $this->render_type_toggles( 'llms_txt_post_types', PostTypes::post_types(), true ); ?>
				<p class="description"><?php esc_html_e( 'Each type becomes its own section. Noindex content and content with AI Input set to "No" are left out.', 'lw-seo' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="llms_txt_max_items"><?php esc_html_e( 'Items per section', 'lw-seo' ); ?></label></th>
			<td>
				<?php
				printf(
					'<input type="number" id="llms_txt_max_items" name="%s[llms_txt_max_items]" value="%d" min="1" max="500" class="small-text" />',
					esc_attr( Options::OPTION_NAME ),
					(int) SectionCollector::limit( Options::get( 'llms_txt_max_items' ) )
				);
				?>
				<p class="description"><?php esc_html_e( 'Pages are listed in menu order, other types newest first.', 'lw-seo' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Markdown links', 'lw-seo' ); ?></th>
			<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'llms_txt_markdown_links',
						'label' => __( 'Link to the Markdown version of each page (/md) instead of the HTML page', 'lw-seo' ),
					]
				);
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Optional links and llms-full.txt rows.
	 *
	 * @return void
	 */
	private function render_extra_rows(): void {
		?>
		<tr>
			<th scope="row"><label for="llms_txt_optional_links"><?php esc_html_e( 'Extra links', 'lw-seo' ); ?></label></th>
			<td>
				<?php
				$this->render_textarea_field(
					[
						'name'        => 'llms_txt_optional_links',
						'description' => __( 'One per line: Title | https://url | optional description. Listed under "Optional".', 'lw-seo' ),
					]
				);
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'llms-full.txt', 'lw-seo' ); ?></th>
			<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'llms_full_txt_enabled',
						'label' => __( 'Also serve /llms-full.txt with the full Markdown content (max 1 MB)', 'lw-seo' ),
					]
				);
				?>
			</td>
		</tr>
		<?php
	}
}
