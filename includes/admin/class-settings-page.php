<?php
/**
 * Settings Page class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin;

use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Replace_Vars;

/**
 * Handles the plugin settings page.
 */
final class Settings_Page {

    /**
     * Settings page slug.
     */
    private const PAGE_SLUG = 'lw-seo';

    /**
     * Settings group.
     */
    private const SETTINGS_GROUP = 'lw_seo_settings';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Add menu page.
     *
     * @return void
     */
    public function add_menu_page(): void {
        add_options_page(
            __( 'LW SEO Settings', 'lw-seo' ),
            __( 'LW SEO', 'lw-seo' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_page' ]
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page.
     * @return void
     */
    public function enqueue_assets( string $hook ): void {
        if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'lw-seo-settings',
            LW_SEO_URL . 'assets/css/settings.css',
            [],
            LW_SEO_VERSION
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

        // General section.
        $this->add_general_section();

        // Content Types section.
        $this->add_content_section();

        // Social section.
        $this->add_social_section();

        // Sitemap section.
        $this->add_sitemap_section();

        // Advanced section.
        $this->add_advanced_section();
    }

    /**
     * Add general settings section.
     *
     * @return void
     */
    private function add_general_section(): void {
        add_settings_section(
            'lw_seo_general',
            __( 'General', 'lw-seo' ),
            function () {
                echo '<p>' . esc_html__( 'Basic SEO settings for your site.', 'lw-seo' ) . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_field(
            'separator',
            __( 'Title Separator', 'lw-seo' ),
            [ $this, 'render_separator_field' ],
            self::PAGE_SLUG,
            'lw_seo_general'
        );

        add_settings_field(
            'title_home',
            __( 'Homepage Title', 'lw-seo' ),
            [ $this, 'render_text_field' ],
            self::PAGE_SLUG,
            'lw_seo_general',
            [
                'name'        => 'title_home',
                'description' => __( 'Available variables:', 'lw-seo' ) . ' %%sitename%%, %%sitedesc%%, %%sep%%',
            ]
        );

        add_settings_field(
            'desc_home',
            __( 'Homepage Description', 'lw-seo' ),
            [ $this, 'render_textarea_field' ],
            self::PAGE_SLUG,
            'lw_seo_general',
            [
                'name'        => 'desc_home',
                'description' => __( 'Leave empty to use the site tagline.', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'knowledge_type',
            __( 'Site represents', 'lw-seo' ),
            [ $this, 'render_select_field' ],
            self::PAGE_SLUG,
            'lw_seo_general',
            [
                'name'    => 'knowledge_type',
                'options' => [
                    'organization' => __( 'Organization', 'lw-seo' ),
                    'person'       => __( 'Person', 'lw-seo' ),
                ],
            ]
        );

        add_settings_field(
            'knowledge_name',
            __( 'Organization/Person Name', 'lw-seo' ),
            [ $this, 'render_text_field' ],
            self::PAGE_SLUG,
            'lw_seo_general',
            [
                'name'        => 'knowledge_name',
                'description' => __( 'Used in Schema.org markup.', 'lw-seo' ),
            ]
        );
    }

    /**
     * Add content types section.
     *
     * @return void
     */
    private function add_content_section(): void {
        add_settings_section(
            'lw_seo_content',
            __( 'Content Types', 'lw-seo' ),
            function () {
                echo '<p>' . esc_html__( 'Title templates for different content types.', 'lw-seo' ) . '</p>';
                echo '<p class="description">' . esc_html__( 'Variables:', 'lw-seo' ) . ' %%title%%, %%sitename%%, %%sep%%, %%category%%, %%author%%, %%date%%</p>';
            },
            self::PAGE_SLUG
        );

        // Posts.
        add_settings_field(
            'title_post',
            __( 'Posts Title', 'lw-seo' ),
            [ $this, 'render_text_field' ],
            self::PAGE_SLUG,
            'lw_seo_content',
            [ 'name' => 'title_post' ]
        );

        add_settings_field(
            'noindex_post',
            __( 'Posts Default', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_content',
            [
                'name'  => 'noindex_post',
                'label' => __( 'Set posts to noindex by default', 'lw-seo' ),
            ]
        );

        // Pages.
        add_settings_field(
            'title_page',
            __( 'Pages Title', 'lw-seo' ),
            [ $this, 'render_text_field' ],
            self::PAGE_SLUG,
            'lw_seo_content',
            [ 'name' => 'title_page' ]
        );

        // Categories.
        add_settings_field(
            'title_category',
            __( 'Categories Title', 'lw-seo' ),
            [ $this, 'render_text_field' ],
            self::PAGE_SLUG,
            'lw_seo_content',
            [ 'name' => 'title_category' ]
        );

        add_settings_field(
            'noindex_category',
            __( 'Categories Default', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_content',
            [
                'name'  => 'noindex_category',
                'label' => __( 'Set categories to noindex', 'lw-seo' ),
            ]
        );

        // Tags.
        add_settings_field(
            'noindex_post_tag',
            __( 'Tags Default', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_content',
            [
                'name'  => 'noindex_post_tag',
                'label' => __( 'Set tags to noindex', 'lw-seo' ),
            ]
        );

        // Archives.
        add_settings_field(
            'noindex_author',
            __( 'Author Archives', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_content',
            [
                'name'  => 'noindex_author',
                'label' => __( 'Set author archives to noindex', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'noindex_date',
            __( 'Date Archives', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_content',
            [
                'name'  => 'noindex_date',
                'label' => __( 'Set date archives to noindex', 'lw-seo' ),
            ]
        );
    }

    /**
     * Add social section.
     *
     * @return void
     */
    private function add_social_section(): void {
        add_settings_section(
            'lw_seo_social',
            __( 'Social', 'lw-seo' ),
            function () {
                echo '<p>' . esc_html__( 'Social media settings for Open Graph and Twitter Cards.', 'lw-seo' ) . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_field(
            'opengraph_enabled',
            __( 'Open Graph', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_social',
            [
                'name'  => 'opengraph_enabled',
                'label' => __( 'Enable Open Graph meta tags', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'twitter_enabled',
            __( 'Twitter Cards', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_social',
            [
                'name'  => 'twitter_enabled',
                'label' => __( 'Enable Twitter Card meta tags', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'twitter_card_type',
            __( 'Default Card Type', 'lw-seo' ),
            [ $this, 'render_select_field' ],
            self::PAGE_SLUG,
            'lw_seo_social',
            [
                'name'    => 'twitter_card_type',
                'options' => [
                    'summary_large_image' => __( 'Summary with large image', 'lw-seo' ),
                    'summary'             => __( 'Summary', 'lw-seo' ),
                ],
            ]
        );

        // Social profiles.
        $profiles = [
            'social_facebook'  => __( 'Facebook URL', 'lw-seo' ),
            'social_twitter'   => __( 'Twitter/X URL', 'lw-seo' ),
            'social_instagram' => __( 'Instagram URL', 'lw-seo' ),
            'social_linkedin'  => __( 'LinkedIn URL', 'lw-seo' ),
            'social_youtube'   => __( 'YouTube URL', 'lw-seo' ),
        ];

        foreach ( $profiles as $name => $label ) {
            add_settings_field(
                $name,
                $label,
                [ $this, 'render_url_field' ],
                self::PAGE_SLUG,
                'lw_seo_social',
                [ 'name' => $name ]
            );
        }
    }

    /**
     * Add sitemap section.
     *
     * @return void
     */
    private function add_sitemap_section(): void {
        add_settings_section(
            'lw_seo_sitemap',
            __( 'XML Sitemap', 'lw-seo' ),
            function () {
                $sitemap_url = home_url( '/sitemap.xml' );
                echo '<p>' . esc_html__( 'XML sitemap settings.', 'lw-seo' ) . '</p>';
                echo '<p>' . sprintf(
                    /* translators: %s: sitemap URL */
                    esc_html__( 'Your sitemap: %s', 'lw-seo' ),
                    '<a href="' . esc_url( $sitemap_url ) . '" target="_blank">' . esc_html( $sitemap_url ) . '</a>'
                ) . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_field(
            'sitemap_enabled',
            __( 'XML Sitemap', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_sitemap',
            [
                'name'  => 'sitemap_enabled',
                'label' => __( 'Enable XML sitemap', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'sitemap_posts',
            __( 'Include Posts', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_sitemap',
            [
                'name'  => 'sitemap_posts',
                'label' => __( 'Include posts in sitemap', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'sitemap_pages',
            __( 'Include Pages', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_sitemap',
            [
                'name'  => 'sitemap_pages',
                'label' => __( 'Include pages in sitemap', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'sitemap_categories',
            __( 'Include Categories', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_sitemap',
            [
                'name'  => 'sitemap_categories',
                'label' => __( 'Include categories in sitemap', 'lw-seo' ),
            ]
        );
    }

    /**
     * Add advanced section.
     *
     * @return void
     */
    private function add_advanced_section(): void {
        add_settings_section(
            'lw_seo_advanced',
            __( 'Advanced', 'lw-seo' ),
            function () {
                echo '<p>' . esc_html__( 'Advanced settings and cleanup options.', 'lw-seo' ) . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_field(
            'breadcrumbs_enabled',
            __( 'Breadcrumbs', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_advanced',
            [
                'name'  => 'breadcrumbs_enabled',
                'label' => __( 'Enable breadcrumbs (use [lw_breadcrumbs] shortcode)', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'schema_enabled',
            __( 'Schema.org', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_advanced',
            [
                'name'  => 'schema_enabled',
                'label' => __( 'Enable Schema.org JSON-LD output', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'robots_txt_enabled',
            __( 'robots.txt', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_advanced',
            [
                'name'  => 'robots_txt_enabled',
                'label' => __( 'Add sitemap URL to robots.txt', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'llms_txt_enabled',
            __( 'llms.txt', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_advanced',
            [
                'name'  => 'llms_txt_enabled',
                'label' => __( 'Enable llms.txt for AI crawlers', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'remove_shortlinks',
            __( 'Cleanup', 'lw-seo' ),
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_advanced',
            [
                'name'  => 'remove_shortlinks',
                'label' => __( 'Remove shortlinks from head', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'remove_rsd',
            '',
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_advanced',
            [
                'name'  => 'remove_rsd',
                'label' => __( 'Remove RSD link from head', 'lw-seo' ),
            ]
        );

        add_settings_field(
            'remove_wlw',
            '',
            [ $this, 'render_checkbox_field' ],
            self::PAGE_SLUG,
            'lw_seo_advanced',
            [
                'name'  => 'remove_wlw',
                'label' => __( 'Remove Windows Live Writer link from head', 'lw-seo' ),
            ]
        );
    }

    /**
     * Render text field.
     *
     * @param array<string, mixed> $args Field arguments.
     * @return void
     */
    public function render_text_field( array $args ): void {
        $name  = $args['name'];
        $value = Options::get( $name );
        $desc  = $args['description'] ?? '';

        printf(
            '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" />',
            esc_attr( $name ),
            esc_attr( Options::OPTION_NAME ),
            esc_attr( $value )
        );

        if ( $desc ) {
            printf( '<p class="description">%s</p>', esc_html( $desc ) );
        }
    }

    /**
     * Render textarea field.
     *
     * @param array<string, mixed> $args Field arguments.
     * @return void
     */
    public function render_textarea_field( array $args ): void {
        $name  = $args['name'];
        $value = Options::get( $name );
        $desc  = $args['description'] ?? '';

        printf(
            '<textarea id="%1$s" name="%2$s[%1$s]" rows="3" class="large-text">%3$s</textarea>',
            esc_attr( $name ),
            esc_attr( Options::OPTION_NAME ),
            esc_textarea( $value )
        );

        if ( $desc ) {
            printf( '<p class="description">%s</p>', esc_html( $desc ) );
        }
    }

    /**
     * Render URL field.
     *
     * @param array<string, mixed> $args Field arguments.
     * @return void
     */
    public function render_url_field( array $args ): void {
        $name  = $args['name'];
        $value = Options::get( $name );

        printf(
            '<input type="url" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" placeholder="https://" />',
            esc_attr( $name ),
            esc_attr( Options::OPTION_NAME ),
            esc_url( $value )
        );
    }

    /**
     * Render checkbox field.
     *
     * @param array<string, mixed> $args Field arguments.
     * @return void
     */
    public function render_checkbox_field( array $args ): void {
        $name  = $args['name'];
        $label = $args['label'] ?? '';
        $value = Options::get( $name );

        printf(
            '<label><input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1" %3$s /> %4$s</label>',
            esc_attr( $name ),
            esc_attr( Options::OPTION_NAME ),
            checked( $value, true, false ),
            esc_html( $label )
        );
    }

    /**
     * Render select field.
     *
     * @param array<string, mixed> $args Field arguments.
     * @return void
     */
    public function render_select_field( array $args ): void {
        $name    = $args['name'];
        $options = $args['options'] ?? [];
        $value   = Options::get( $name );

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">',
            esc_attr( $name ),
            esc_attr( Options::OPTION_NAME )
        );

        foreach ( $options as $key => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $key ),
                selected( $value, $key, false ),
                esc_html( $label )
            );
        }

        echo '</select>';
    }

    /**
     * Render separator field.
     *
     * @return void
     */
    public function render_separator_field(): void {
        $value      = Options::get( 'separator' );
        $separators = Options::get_separators();

        printf(
            '<select id="separator" name="%s[separator]">',
            esc_attr( Options::OPTION_NAME )
        );

        foreach ( $separators as $sep => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $sep ),
                selected( $value, $sep, false ),
                esc_html( $label )
            );
        }

        echo '</select>';
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

        foreach ( $defaults as $key => $default ) {
            if ( is_bool( $default ) ) {
                $sanitized[ $key ] = ! empty( $input[ $key ] );
            } elseif ( str_starts_with( $key, 'social_' ) ) {
                $sanitized[ $key ] = isset( $input[ $key ] ) ? esc_url_raw( $input[ $key ] ) : '';
            } else {
                $sanitized[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $default;
            }
        }

        return $sanitized;
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap lw-seo-settings">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( self::SETTINGS_GROUP );
                do_settings_sections( self::PAGE_SLUG );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
