<?php
/**
 * Settings Page class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin;

use LightweightPlugins\SEO\Admin\Settings\TabInterface;
use LightweightPlugins\SEO\Admin\Settings\TabGeneral;
use LightweightPlugins\SEO\Admin\Settings\TabContent;
use LightweightPlugins\SEO\Admin\Settings\TabSocial;
use LightweightPlugins\SEO\Admin\Settings\TabSitemap;
use LightweightPlugins\SEO\Admin\Settings\TabAi;
use LightweightPlugins\SEO\Admin\Settings\TabAdvanced;
use LightweightPlugins\SEO\Admin\Settings\TabWooCommerce;
use LightweightPlugins\SEO\Admin\Settings\TabLocal;
use LightweightPlugins\SEO\Admin\Settings\TabRedirects;
use LightweightPlugins\SEO\Admin\Settings\Tab404;
use LightweightPlugins\SEO\WooCommerce\WooCommerce;
use LightweightPlugins\SEO\Options;

/**
 * Handles the plugin settings page.
 */
final class SettingsPage {

	/**
	 * Settings page slug.
	 */
	public const SLUG = 'lw-seo';

	/**
	 * Settings group.
	 */
	private const SETTINGS_GROUP = 'lw_seo_settings';

	/**
	 * Registered tabs.
	 *
	 * @var array<TabInterface>
	 */
	private array $tabs = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_tabs();

		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register settings tabs.
	 *
	 * @return void
	 */
	private function register_tabs(): void {
		$this->tabs = [
			new TabGeneral(),
			new TabContent(),
			new TabSocial(),
			new TabSitemap(),
			new TabAi(),
			new TabLocal(),
			new TabRedirects(),
			new Tab404(),
			new TabAdvanced(),
		];

		// Add WooCommerce tab if WooCommerce is active.
		if ( WooCommerce::is_active() ) {
			// Insert before Local tab.
			array_splice( $this->tabs, 5, 0, [ new TabWooCommerce() ] );
		}
	}

	/**
	 * Add menu page.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		ParentPage::maybe_register();

		add_submenu_page(
			ParentPage::SLUG,
			__( 'SEO Settings', 'lw-seo' ),
			__( 'SEO', 'lw-seo' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		$valid_hooks = [
			'toplevel_page_' . ParentPage::SLUG,
			ParentPage::SLUG . '_page_' . self::SLUG,
		];

		if ( ! in_array( $hook, $valid_hooks, true ) ) {
			return;
		}

		// Enqueue WordPress media library.
		wp_enqueue_media();

		wp_enqueue_style(
			'lw-seo-settings',
			LW_SEO_URL . 'assets/css/settings.css',
			[],
			LW_SEO_VERSION
		);

		wp_enqueue_script(
			'lw-seo-settings',
			LW_SEO_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			LW_SEO_VERSION,
			true
		);

		// Redirects JavaScript.
		wp_enqueue_script(
			'lw-seo-redirects',
			LW_SEO_URL . 'assets/js/redirects.js',
			[],
			LW_SEO_VERSION,
			true
		);

		wp_localize_script(
			'lw-seo-redirects',
			'lwSeoRedirectsL10n',
			[
				'nonce'          => wp_create_nonce( 'lw_seo_redirects' ),
				'addButton'      => __( 'Add Redirect', 'lw-seo' ),
				'updateButton'   => __( 'Update Redirect', 'lw-seo' ),
				'editButton'     => __( 'Edit', 'lw-seo' ),
				'deleteButton'   => __( 'Delete', 'lw-seo' ),
				'confirmDelete'  => __( 'Are you sure you want to delete this redirect?', 'lw-seo' ),
				'sourceRequired' => __( 'Source URL is required.', 'lw-seo' ),
				'selectFile'     => __( 'Please select a CSV file.', 'lw-seo' ),
				'importErrors'   => __( 'Some rows could not be imported:', 'lw-seo' ),
				'notRequired'    => __( 'Not required for this type', 'lw-seo' ),
				'na'             => __( 'N/A', 'lw-seo' ),
				'regex'          => __( 'Regex', 'lw-seo' ),
			]
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			Options::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => Options::get_defaults(),
			]
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string, mixed> $input Input values.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( array $input ): array {
		$defaults  = Options::get_defaults();
		$sanitized = [];

		// URL fields that should be sanitized as URLs.
		$url_fields = [ 'social_', 'default_og_image', 'knowledge_logo' ];

		foreach ( $defaults as $key => $default ) {
			if ( is_bool( $default ) ) {
				$sanitized[ $key ] = ! empty( $input[ $key ] );
			} elseif ( $this->is_url_field( $key, $url_fields ) ) {
				$sanitized[ $key ] = isset( $input[ $key ] ) ? esc_url_raw( $input[ $key ] ) : '';
			} else {
				$sanitized[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $default;
			}
		}

		return $sanitized;
	}

	/**
	 * Check if a field key is a URL field.
	 *
	 * @param string        $key        Field key.
	 * @param array<string> $url_fields URL field patterns.
	 * @return bool
	 */
	private function is_url_field( string $key, array $url_fields ): bool {
		foreach ( $url_fields as $pattern ) {
			if ( str_starts_with( $key, $pattern ) || $key === $pattern ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( self::SETTINGS_GROUP ); ?>

				<div class="lw-seo-settings">
					<?php $this->render_tabs_nav(); ?>

					<div class="lw-seo-tab-content">
						<?php $this->render_tabs_content(); ?>
						<?php submit_button(); ?>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render tabs navigation.
	 *
	 * @return void
	 */
	private function render_tabs_nav(): void {
		?>
		<ul class="lw-seo-tabs">
			<?php foreach ( $this->tabs as $index => $tab ) : ?>
				<li>
					<a href="#<?php echo esc_attr( $tab->get_slug() ); ?>" <?php echo 0 === $index ? 'class="active"' : ''; ?>>
						<span class="dashicons <?php echo esc_attr( $tab->get_icon() ); ?>"></span>
						<?php echo esc_html( $tab->get_label() ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Render tabs content.
	 *
	 * @return void
	 */
	private function render_tabs_content(): void {
		foreach ( $this->tabs as $index => $tab ) {
			$active_class = 0 === $index ? ' active' : '';
			printf(
				'<div id="tab-%s" class="lw-seo-tab-panel%s">',
				esc_attr( $tab->get_slug() ),
				esc_attr( $active_class )
			);
			$tab->render();
			echo '</div>';
		}
	}
}
