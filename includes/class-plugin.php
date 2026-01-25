<?php
/**
 * Main Plugin class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Main plugin class.
 */
final class Plugin {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    private function init_hooks(): void {
        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'wp_head', [ $this, 'output_meta_tags' ], 1 );

        if ( is_admin() ) {
            add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        }
    }

    /**
     * Load plugin textdomain.
     *
     * @return void
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'lw-seo',
            false,
            dirname( plugin_basename( LW_SEO_FILE ) ) . '/languages'
        );
    }

    /**
     * Output meta tags in head.
     *
     * @return void
     */
    public function output_meta_tags(): void {
        if ( is_singular() ) {
            $this->output_singular_meta();
        } elseif ( is_front_page() || is_home() ) {
            $this->output_home_meta();
        }
    }

    /**
     * Output meta tags for singular posts/pages.
     *
     * @return void
     */
    private function output_singular_meta(): void {
        $post = get_queried_object();

        if ( ! $post instanceof \WP_Post ) {
            return;
        }

        $title       = get_the_title( $post );
        $description = $this->get_meta_description( $post );
        $url         = get_permalink( $post );

        $this->render_meta_tags( $title, $description, $url );
    }

    /**
     * Output meta tags for home/front page.
     *
     * @return void
     */
    private function output_home_meta(): void {
        $title       = get_bloginfo( 'name' );
        $description = get_bloginfo( 'description' );
        $url         = home_url( '/' );

        $this->render_meta_tags( $title, $description, $url );
    }

    /**
     * Get meta description for a post.
     *
     * @param \WP_Post $post The post object.
     * @return string
     */
    private function get_meta_description( \WP_Post $post ): string {
        // Check for custom meta description.
        $description = get_post_meta( $post->ID, '_lw_seo_description', true );

        if ( empty( $description ) ) {
            // Use excerpt or truncated content.
            $description = $post->post_excerpt ?: wp_trim_words( $post->post_content, 30, '...' );
        }

        return wp_strip_all_tags( $description );
    }

    /**
     * Render meta tags.
     *
     * @param string $title       The page title.
     * @param string $description The meta description.
     * @param string $url         The canonical URL.
     * @return void
     */
    private function render_meta_tags( string $title, string $description, string $url ): void {
        if ( ! empty( $description ) ) {
            printf(
                '<meta name="description" content="%s" />' . "\n",
                esc_attr( $description )
            );
        }

        // Open Graph tags.
        printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
        printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $url ) );
        printf( '<meta property="og:type" content="%s" />' . "\n", is_singular() ? 'article' : 'website' );

        if ( ! empty( $description ) ) {
            printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
        }

        // Canonical URL.
        printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $url ) );
    }

    /**
     * Add admin menu.
     *
     * @return void
     */
    public function add_admin_menu(): void {
        add_options_page(
            __( 'LW SEO Settings', 'lw-seo' ),
            __( 'LW SEO', 'lw-seo' ),
            'manage_options',
            'lw-seo',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p><?php esc_html_e( 'LW SEO automatically adds meta tags to your pages.', 'lw-seo' ); ?></p>
        </div>
        <?php
    }
}
