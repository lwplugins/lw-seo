<?php
/**
 * AI/LLM Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

use LightweightPlugins\SEO\Admin\Data\AI_Crawlers;
use LightweightPlugins\SEO\Options;

/**
 * Handles the AI/LLM settings tab.
 */
final class Tab_AI implements Tab_Interface {

	use Field_Renderer;

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'ai';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'AI / LLM', 'lw-seo' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-superhero';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'AI / LLM Settings', 'lw-seo' ); ?></h2>

		<div class="lw-seo-section-description">
			<p><?php esc_html_e( 'Control how AI crawlers interact with your site.', 'lw-seo' ); ?></p>
		</div>

		<?php
		$this->render_llms_section();
		$this->render_crawlers_section();
	}

	/**
	 * Render llms.txt section.
	 *
	 * @return void
	 */
	private function render_llms_section(): void {
		$llms_url = home_url( '/llms.txt' );
		?>
		<h3><?php esc_html_e( 'llms.txt File', 'lw-seo' ); ?></h3>
		<table class="form-table">
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
		</table>
		<?php
	}

	/**
	 * Render AI crawlers section.
	 *
	 * @return void
	 */
	private function render_crawlers_section(): void {
		?>
		<h3><?php esc_html_e( 'AI Crawler Access', 'lw-seo' ); ?></h3>
		<p class="description" style="margin-bottom: 15px;">
			<?php esc_html_e( 'Block specific AI crawlers from accessing your site via robots.txt. Checked = Blocked.', 'lw-seo' ); ?>
		</p>

		<div class="lw-seo-crawler-grid">
			<?php
			$crawlers = AI_Crawlers::get_all();
			foreach ( $crawlers as $key => $crawler ) :
				$is_blocked = Options::get( 'block_' . $key );
				?>
				<div class="lw-seo-crawler-card <?php echo $is_blocked ? 'blocked' : ''; ?>">
					<input
						type="checkbox"
						id="block_<?php echo esc_attr( $key ); ?>"
						name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[block_<?php echo esc_attr( $key ); ?>]"
						value="1"
						<?php checked( $is_blocked, true ); ?>
					/>
					<div class="lw-seo-crawler-info">
						<div class="lw-seo-crawler-name"><?php echo esc_html( $crawler['name'] ); ?></div>
						<div class="lw-seo-crawler-company"><?php echo esc_html( $crawler['company'] ); ?></div>
						<div class="lw-seo-crawler-agent"><?php echo esc_html( $crawler['agent'] ); ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="lw-seo-info-box" style="margin-top: 20px;">
			<span class="dashicons dashicons-info"></span>
			<p>
				<?php esc_html_e( 'Blocking crawlers adds Disallow rules to your robots.txt. Note: AI companies may not always respect these rules.', 'lw-seo' ); ?>
			</p>
		</div>
		<?php
	}
}
