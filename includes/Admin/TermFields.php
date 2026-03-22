<?php
/**
 * Term SEO Fields renderer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin;

use LightweightPlugins\SEO\Options;

/**
 * Renders SEO fields on taxonomy term edit screens.
 */
final class TermFields {

	/**
	 * Render all SEO fields for a term.
	 *
	 * @param \WP_Term $term Current term object.
	 * @return void
	 */
	public static function render( \WP_Term $term ): void {
		self::render_seo_section( $term );
		self::render_social_section( $term );
		self::render_signals_section( $term );
		self::render_markdown_section( $term );
	}

	/**
	 * Render SEO section (title, description, noindex).
	 *
	 * @param \WP_Term $term Term object.
	 * @return void
	 */
	private static function render_seo_section( \WP_Term $term ): void {
		$title       = Options::get_term_meta( $term->term_id, 'title' );
		$description = Options::get_term_meta( $term->term_id, 'description' );
		$noindex     = Options::get_term_meta( $term->term_id, 'noindex' );
		?>
		<tr class="form-field">
			<th scope="row" colspan="2">
				<h3 style="margin: 0; padding: 10px 0 0;">
					<?php esc_html_e( 'LW SEO', 'lw-seo' ); ?>
				</h3>
			</th>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="lw_seo_title"><?php esc_html_e( 'SEO Title', 'lw-seo' ); ?></label></th>
			<td>
				<input type="text" id="lw_seo_title" name="lw_seo_title" value="<?php echo esc_attr( $title ); ?>" class="large-text" />
				<p class="description"><?php esc_html_e( 'Leave empty to use the default title template.', 'lw-seo' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="lw_seo_description"><?php esc_html_e( 'Meta Description', 'lw-seo' ); ?></label></th>
			<td>
				<textarea id="lw_seo_description" name="lw_seo_description" rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Robots', 'lw-seo' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="lw_seo_noindex" value="1" <?php checked( $noindex, '1' ); ?> />
					<?php esc_html_e( 'noindex this archive', 'lw-seo' ); ?>
				</label>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render Social section (OG title, description, image).
	 *
	 * @param \WP_Term $term Term object.
	 * @return void
	 */
	private static function render_social_section( \WP_Term $term ): void {
		$og_title       = Options::get_term_meta( $term->term_id, 'og_title' );
		$og_description = Options::get_term_meta( $term->term_id, 'og_description' );
		$og_image       = Options::get_term_meta( $term->term_id, 'og_image' );
		?>
		<tr class="form-field">
			<th scope="row"><label for="lw_seo_og_title"><?php esc_html_e( 'Social Title', 'lw-seo' ); ?></label></th>
			<td>
				<input type="text" id="lw_seo_og_title" name="lw_seo_og_title" value="<?php echo esc_attr( $og_title ); ?>" class="large-text"
					placeholder="<?php esc_attr_e( 'Defaults to SEO title', 'lw-seo' ); ?>" />
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="lw_seo_og_description"><?php esc_html_e( 'Social Description', 'lw-seo' ); ?></label></th>
			<td>
				<textarea id="lw_seo_og_description" name="lw_seo_og_description" rows="2" class="large-text"
					placeholder="<?php esc_attr_e( 'Defaults to meta description', 'lw-seo' ); ?>"><?php echo esc_textarea( $og_description ); ?></textarea>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="lw_seo_og_image"><?php esc_html_e( 'Social Image', 'lw-seo' ); ?></label></th>
			<td>
				<input type="url" id="lw_seo_og_image" name="lw_seo_og_image" value="<?php echo esc_url( $og_image ); ?>" class="large-text"
					placeholder="https://" />
			</td>
		</tr>
		<?php
	}

	/**
	 * Render AI Content Signals section.
	 *
	 * @param \WP_Term $term Term object.
	 * @return void
	 */
	private static function render_signals_section( \WP_Term $term ): void {
		$ai_train = Options::get_term_meta( $term->term_id, 'ai_train' );
		$ai_input = Options::get_term_meta( $term->term_id, 'ai_input' );
		$search   = Options::get_term_meta( $term->term_id, 'search' );

		$options = [
			'default' => __( 'Default (use global setting)', 'lw-seo' ),
			'yes'     => __( 'Yes', 'lw-seo' ),
			'no'      => __( 'No', 'lw-seo' ),
		];

		$signals = [
			'ai_train' => [ __( 'AI Training', 'lw-seo' ), $ai_train ],
			'ai_input' => [ __( 'AI Input (RAG)', 'lw-seo' ), $ai_input ],
			'search'   => [ __( 'AI Search', 'lw-seo' ), $search ],
		];

		foreach ( $signals as $key => $data ) :
			?>
			<tr class="form-field">
				<th scope="row"><label for="lw_seo_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $data[0] ); ?></label></th>
				<td>
					<select id="lw_seo_<?php echo esc_attr( $key ); ?>" name="lw_seo_<?php echo esc_attr( $key ); ?>">
						<?php foreach ( $options as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $data[1] ? $data[1] : 'default', $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<?php
		endforeach;
	}

	/**
	 * Render Markdown Content section.
	 *
	 * @param \WP_Term $term Term object.
	 * @return void
	 */
	private static function render_markdown_section( \WP_Term $term ): void {
		$markdown = Options::get_term_meta( $term->term_id, 'markdown_content' );
		?>
		<tr class="form-field">
			<th scope="row"><label for="lw_seo_markdown_content"><?php esc_html_e( 'Markdown Content', 'lw-seo' ); ?></label></th>
			<td>
				<textarea id="lw_seo_markdown_content" name="lw_seo_markdown_content" rows="10" class="large-text"
					style="font-family: monospace; font-size: 13px;"
					placeholder="<?php esc_attr_e( '# Title...', 'lw-seo' ); ?>"
				><?php echo esc_textarea( $markdown ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'If filled, this markdown is served at the /md endpoint instead of the auto-generated content.', 'lw-seo' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}
}
