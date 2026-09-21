<?php
/**
 * Custom Markdown override field.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin;

/**
 * The per-post / per-term custom Markdown override. It is served verbatim at
 * the /md endpoint, so, like raw HTML, only users with unfiltered_html may
 * set it. This class holds that rule and the field's two editor renderings.
 */
final class MarkdownOverrideField {

	/**
	 * Meta key (without prefix).
	 */
	public const KEY = 'markdown_content';

	/**
	 * Whether the current user may write an SEO meta field. Every field
	 * except the override is unaffected; the override needs unfiltered_html.
	 * Save paths skip a field entirely when this is false, so the stored
	 * value is left untouched (neither overwritten nor deleted).
	 *
	 * @param string $field Meta key (without prefix).
	 * @return bool
	 */
	public static function may_set( string $field ): bool {
		return self::KEY !== $field || current_user_can( 'unfiltered_html' );
	}

	/**
	 * Post meta box section; read-only for users who may not set it.
	 *
	 * @param string $value Stored override.
	 * @return void
	 */
	public static function render_post_section( string $value ): void {
		?>
			<!-- Markdown Content (Collapsible) -->
			<details class="lw-seo-section lw-seo-section--markdown">
				<summary class="lw-seo-section__title">
					<?php esc_html_e( 'Markdown Content', 'lw-seo' ); ?>
				</summary>
				<div class="lw-seo-section__content">
					<div class="lw-seo-field">
						<label for="lw_seo_markdown_content" class="lw-seo-label">
							<?php esc_html_e( 'Custom Markdown', 'lw-seo' ); ?>
						</label>
						<textarea
							id="lw_seo_markdown_content"
							name="lw_seo_markdown_content"
							class="lw-seo-input"
							rows="12"
							style="font-family: monospace; font-size: 13px;"
							placeholder="<?php esc_attr_e( '# Title...', 'lw-seo' ); ?>"
							<?php disabled( ! self::may_set( self::KEY ) ); ?>
						><?php echo esc_textarea( $value ); ?></textarea>
						<p class="lw-seo-description">
							<?php esc_html_e( 'If filled, this markdown is served at the /md endpoint instead of the auto-generated content. Ideal for Elementor, Divi, or other page builder pages.', 'lw-seo' ); ?>
						</p>
						<?php self::render_lock_note( 'lw-seo-description' ); ?>
					</div>
				</div>
			</details>
		<?php
	}

	/**
	 * Term edit screen row; read-only for users who may not set it.
	 *
	 * @param string $value Stored override.
	 * @return void
	 */
	public static function render_term_row( string $value ): void {
		?>
		<tr class="form-field">
			<th scope="row"><label for="lw_seo_markdown_content"><?php esc_html_e( 'Markdown Content', 'lw-seo' ); ?></label></th>
			<td>
				<textarea id="lw_seo_markdown_content" name="lw_seo_markdown_content" rows="10" class="large-text"
					style="font-family: monospace; font-size: 13px;"
					placeholder="<?php esc_attr_e( '# Title...', 'lw-seo' ); ?>"
					<?php disabled( ! self::may_set( self::KEY ) ); ?>
				><?php echo esc_textarea( $value ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'If filled, this markdown is served at the /md endpoint instead of the auto-generated content.', 'lw-seo' ); ?>
				</p>
				<?php self::render_lock_note( 'description' ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Note shown under a read-only override field.
	 *
	 * @param string $css_class Paragraph class.
	 * @return void
	 */
	private static function render_lock_note( string $css_class ): void {
		if ( self::may_set( self::KEY ) ) {
			return;
		}

		printf(
			'<p class="%s">%s</p>',
			esc_attr( $css_class ),
			esc_html__( 'Only users allowed to post unfiltered HTML can edit the Markdown override.', 'lw-seo' )
		);
	}
}
