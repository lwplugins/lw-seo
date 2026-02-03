<?php
/**
 * FAQ Block Registration and Rendering.
 *
 * @package LightweightPlugins\SEO\Blocks\FAQ
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Blocks\FAQ;

use LightweightPlugins\SEO\Options;

/**
 * FAQ Gutenberg Block.
 */
final class Block {

	/**
	 * Block name.
	 */
	public const NAME = 'lw-seo/faq';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Register the FAQ block.
	 *
	 * @return void
	 */
	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			LW_SEO_PATH . 'includes/Blocks/FAQ',
			[
				'render_callback' => [ $this, 'render_block' ],
			]
		);
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		wp_enqueue_script(
			'lw-seo-faq-block',
			LW_SEO_URL . 'assets/js/blocks/faq.js',
			[ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-block-editor' ],
			LW_SEO_VERSION,
			true
		);

		wp_enqueue_style(
			'lw-seo-faq-block-editor',
			LW_SEO_URL . 'assets/css/blocks/faq-editor.css',
			[ 'wp-edit-blocks' ],
			LW_SEO_VERSION
		);
	}

	/**
	 * Render the FAQ block on frontend.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render_block( array $attributes ): string {
		$questions = $attributes['questions'] ?? [];

		if ( empty( $questions ) ) {
			return '';
		}

		$title_tag    = $attributes['titleWrapper'] ?? 'h3';
		$allowed_tags = [ 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div' ];
		$title_tag    = in_array( $title_tag, $allowed_tags, true ) ? $title_tag : 'h3';

		// Enqueue frontend styles.
		wp_enqueue_style(
			'lw-seo-faq-block',
			LW_SEO_URL . 'assets/css/blocks/faq.css',
			[],
			LW_SEO_VERSION
		);

		ob_start();
		?>
		<div class="lw-seo-faq" itemscope itemtype="https://schema.org/FAQPage">
			<?php foreach ( $questions as $question ) : ?>
				<?php if ( ! empty( $question['visible'] ) ) : ?>
					<div class="lw-seo-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
						<<?php echo esc_html( $title_tag ); ?> class="lw-seo-faq-question" itemprop="name">
							<?php echo wp_kses_post( $question['title'] ?? '' ); ?>
						</<?php echo esc_html( $title_tag ); ?>>
						<div class="lw-seo-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
							<div itemprop="text">
								<?php echo wp_kses_post( $question['content'] ?? '' ); ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
