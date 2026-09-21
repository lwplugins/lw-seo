<?php
/**
 * AI/LLM Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

/**
 * Handles the AI/LLM settings tab.
 */
final class TabAi implements TabInterface {

	use FieldRendererTrait;

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
		$this->render_content_signals_section();
		( new LlmsSection() )->render();
		( new CrawlerSection() )->render();
	}

	/**
	 * Render Content Signals section.
	 *
	 * @return void
	 */
	private function render_content_signals_section(): void {
		$choices = [
			''    => __( 'Not specified', 'lw-seo' ),
			'yes' => __( 'Allow', 'lw-seo' ),
			'no'  => __( 'Disallow', 'lw-seo' ),
		];
		$rows    = [
			'content_signals_search'   => __( 'Search', 'lw-seo' ),
			'content_signals_ai_input' => __( 'AI Input (RAG, grounding)', 'lw-seo' ),
			'content_signals_ai_train' => __( 'AI Training', 'lw-seo' ),
		];
		?>
		<h3><?php esc_html_e( 'Content Signals', 'lw-seo' ); ?></h3>
		<p class="description" style="margin-bottom: 10px;">
			<?php esc_html_e( 'Tell AI systems how they may use your content. Sent as a Content-Signal HTTP header, a meta tag and a robots.txt line. "Not specified" neither grants nor restricts that use.', 'lw-seo' ); ?>
		</p>
		<table class="form-table">
			<?php foreach ( $rows as $name => $label ) : ?>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
					<td>
						<?php
						$this->render_select_field(
							[
								'name'    => $name,
								'options' => $choices,
							]
						);
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}
}
