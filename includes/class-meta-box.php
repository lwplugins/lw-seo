<?php
/**
 * Meta Box class for post editor.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Handles the SEO meta box in post editor.
 */
final class Meta_Box {

	/**
	 * Meta box ID.
	 */
	private const META_BOX_ID = 'lw_seo_meta_box';

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'lw_seo_save_meta';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'lw_seo_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_meta' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Add meta box to post types.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		$post_types = $this->get_post_types();

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				self::META_BOX_ID,
				__( 'LW SEO', 'lw-seo' ),
				[ $this, 'render_meta_box' ],
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Get post types that should have the meta box.
	 *
	 * @return array<string>
	 */
	private function get_post_types(): array {
		$post_types = get_post_types( [ 'public' => true ], 'names' );

		// Remove attachment.
		unset( $post_types['attachment'] );

		/**
		 * Filter the post types that get the SEO meta box.
		 *
		 * @param array $post_types Array of post type names.
		 */
		return apply_filters( 'lw_seo_meta_box_post_types', array_values( $post_types ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, $this->get_post_types(), true ) ) {
			return;
		}

		wp_enqueue_style(
			'lw-seo-admin',
			LW_SEO_URL . 'assets/css/admin.css',
			[],
			LW_SEO_VERSION
		);

		wp_enqueue_script(
			'lw-seo-admin',
			LW_SEO_URL . 'assets/js/admin.js',
			[],
			LW_SEO_VERSION,
			true
		);

		wp_localize_script(
			'lw-seo-admin',
			'lwSeoAdmin',
			[
				'titleMaxLength' => 60,
				'descMaxLength'  => 160,
				'i18n'           => [
					'charactersRemaining' => __( 'characters remaining', 'lw-seo' ),
					'tooLong'             => __( 'Too long!', 'lw-seo' ),
				],
			]
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$title          = Options::get_post_meta( $post->ID, 'title' );
		$description    = Options::get_post_meta( $post->ID, 'description' );
		$noindex        = Options::get_post_meta( $post->ID, 'noindex' );
		$nofollow       = Options::get_post_meta( $post->ID, 'nofollow' );
		$canonical      = Options::get_post_meta( $post->ID, 'canonical' );
		$og_title       = Options::get_post_meta( $post->ID, 'og_title' );
		$og_description = Options::get_post_meta( $post->ID, 'og_description' );
		$og_image       = Options::get_post_meta( $post->ID, 'og_image' );

		?>
		<div class="lw-seo-meta-box">
			<!-- SEO Tab -->
			<div class="lw-seo-section lw-seo-section--seo">
				<div class="lw-seo-field">
					<label for="lw_seo_title" class="lw-seo-label">
						<?php esc_html_e( 'SEO Title', 'lw-seo' ); ?>
					</label>
					<input
						type="text"
						id="lw_seo_title"
						name="lw_seo_title"
						value="<?php echo esc_attr( $title ); ?>"
						class="lw-seo-input lw-seo-input--title"
						data-max-length="60"
						placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>"
					/>
					<span class="lw-seo-counter" data-for="lw_seo_title">
						<span class="lw-seo-counter__current">0</span>/60
					</span>
					<p class="lw-seo-description">
						<?php esc_html_e( 'Leave empty to use the default title template.', 'lw-seo' ); ?>
					</p>
				</div>

				<div class="lw-seo-field">
					<label for="lw_seo_description" class="lw-seo-label">
						<?php esc_html_e( 'Meta Description', 'lw-seo' ); ?>
					</label>
					<textarea
						id="lw_seo_description"
						name="lw_seo_description"
						class="lw-seo-input lw-seo-input--description"
						data-max-length="160"
						rows="3"
						placeholder="<?php esc_attr_e( 'Enter a meta description...', 'lw-seo' ); ?>"
					><?php echo esc_textarea( $description ); ?></textarea>
					<span class="lw-seo-counter" data-for="lw_seo_description">
						<span class="lw-seo-counter__current">0</span>/160
					</span>
				</div>

				<div class="lw-seo-field lw-seo-field--inline">
					<label class="lw-seo-checkbox">
						<input
							type="checkbox"
							name="lw_seo_noindex"
							value="1"
							<?php checked( $noindex, '1' ); ?>
						/>
						<?php esc_html_e( 'noindex', 'lw-seo' ); ?>
					</label>
					<label class="lw-seo-checkbox">
						<input
							type="checkbox"
							name="lw_seo_nofollow"
							value="1"
							<?php checked( $nofollow, '1' ); ?>
						/>
						<?php esc_html_e( 'nofollow', 'lw-seo' ); ?>
					</label>
				</div>
			</div>

			<!-- Social Tab (Collapsible) -->
			<details class="lw-seo-section lw-seo-section--social">
				<summary class="lw-seo-section__title">
					<?php esc_html_e( 'Social', 'lw-seo' ); ?>
				</summary>
				<div class="lw-seo-section__content">
					<div class="lw-seo-field">
						<label for="lw_seo_og_title" class="lw-seo-label">
							<?php esc_html_e( 'Social Title', 'lw-seo' ); ?>
						</label>
						<input
							type="text"
							id="lw_seo_og_title"
							name="lw_seo_og_title"
							value="<?php echo esc_attr( $og_title ); ?>"
							class="lw-seo-input"
							placeholder="<?php esc_attr_e( 'Defaults to SEO title', 'lw-seo' ); ?>"
						/>
					</div>

					<div class="lw-seo-field">
						<label for="lw_seo_og_description" class="lw-seo-label">
							<?php esc_html_e( 'Social Description', 'lw-seo' ); ?>
						</label>
						<textarea
							id="lw_seo_og_description"
							name="lw_seo_og_description"
							class="lw-seo-input"
							rows="2"
							placeholder="<?php esc_attr_e( 'Defaults to meta description', 'lw-seo' ); ?>"
						><?php echo esc_textarea( $og_description ); ?></textarea>
					</div>

					<div class="lw-seo-field">
						<label for="lw_seo_og_image" class="lw-seo-label">
							<?php esc_html_e( 'Social Image URL', 'lw-seo' ); ?>
						</label>
						<input
							type="url"
							id="lw_seo_og_image"
							name="lw_seo_og_image"
							value="<?php echo esc_url( $og_image ); ?>"
							class="lw-seo-input"
							placeholder="<?php esc_attr_e( 'Defaults to featured image', 'lw-seo' ); ?>"
						/>
					</div>
				</div>
			</details>

			<!-- Advanced Tab (Collapsible) -->
			<details class="lw-seo-section lw-seo-section--advanced">
				<summary class="lw-seo-section__title">
					<?php esc_html_e( 'Advanced', 'lw-seo' ); ?>
				</summary>
				<div class="lw-seo-section__content">
					<div class="lw-seo-field">
						<label for="lw_seo_canonical" class="lw-seo-label">
							<?php esc_html_e( 'Canonical URL', 'lw-seo' ); ?>
						</label>
						<input
							type="url"
							id="lw_seo_canonical"
							name="lw_seo_canonical"
							value="<?php echo esc_url( $canonical ); ?>"
							class="lw-seo-input"
							placeholder="<?php echo esc_url( get_permalink( $post ) ); ?>"
						/>
						<p class="lw-seo-description">
							<?php esc_html_e( 'Leave empty to use the default permalink.', 'lw-seo' ); ?>
						</p>
					</div>
				</div>
			</details>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if (
			! isset( $_POST[ self::NONCE_NAME ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check post type.
		if ( ! in_array( $post->post_type, $this->get_post_types(), true ) ) {
			return;
		}

		// Save fields.
		$fields = [
			'title'          => 'sanitize_text_field',
			'description'    => 'sanitize_textarea_field',
			'noindex'        => 'sanitize_text_field',
			'nofollow'       => 'sanitize_text_field',
			'canonical'      => 'esc_url_raw',
			'og_title'       => 'sanitize_text_field',
			'og_description' => 'sanitize_textarea_field',
			'og_image'       => 'esc_url_raw',
		];

		foreach ( $fields as $field => $sanitize_callback ) {
			$input_name = 'lw_seo_' . $field;
			$value      = '';

			if ( isset( $_POST[ $input_name ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via $sanitize_callback.
				$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $input_name ] ) );
			}

			Options::set_post_meta( $post_id, $field, $value );
		}
	}
}
