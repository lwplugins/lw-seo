<?php
/**
 * Advanced Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

/**
 * Handles the Advanced settings tab.
 */
final class Tab_Advanced implements Tab_Interface {

    use Field_Renderer;

    /**
     * Get the tab slug.
     *
     * @return string
     */
    public function get_slug(): string {
        return 'advanced';
    }

    /**
     * Get the tab label.
     *
     * @return string
     */
    public function get_label(): string {
        return __( 'Advanced', 'lw-seo' );
    }

    /**
     * Get the tab icon.
     *
     * @return string
     */
    public function get_icon(): string {
        return 'dashicons-admin-tools';
    }

    /**
     * Render the tab content.
     *
     * @return void
     */
    public function render(): void {
        ?>
        <h2><?php esc_html_e( 'Advanced Settings', 'lw-seo' ); ?></h2>

        <div class="lw-seo-section-description">
            <p><?php esc_html_e( 'Advanced settings and cleanup options.', 'lw-seo' ); ?></p>
        </div>

        <?php
        $this->render_features_section();
        $this->render_cleanup_section();
    }

    /**
     * Render features section.
     *
     * @return void
     */
    private function render_features_section(): void {
        $robots_url = home_url( '/robots.txt' );
        ?>
        <h3><?php esc_html_e( 'Features', 'lw-seo' ); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Breadcrumbs', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'breadcrumbs_enabled',
                        'label' => __( 'Enable breadcrumbs', 'lw-seo' ),
                    ] );
                    ?>
                    <p class="description">
                        <?php esc_html_e( 'Use shortcode [lw_breadcrumbs] or function lw_seo_breadcrumbs()', 'lw-seo' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Schema.org', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'schema_enabled',
                        'label' => __( 'Enable Schema.org JSON-LD output', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'robots.txt', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'robots_txt_enabled',
                        'label' => __( 'Add sitemap URL and AI rules to robots.txt', 'lw-seo' ),
                    ] );
                    ?>
                    <p class="description">
                        <?php
                        printf(
                            '%s: <a href="%s" target="_blank">%s</a>',
                            esc_html__( 'Your robots.txt', 'lw-seo' ),
                            esc_url( $robots_url ),
                            esc_html( $robots_url )
                        );
                        ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render cleanup section.
     *
     * @return void
     */
    private function render_cleanup_section(): void {
        ?>
        <h3><?php esc_html_e( 'Head Cleanup', 'lw-seo' ); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Shortlinks', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'remove_shortlinks',
                        'label' => __( 'Remove shortlinks from head', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'RSD Link', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'remove_rsd',
                        'label' => __( 'Remove RSD link from head', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'WLW Manifest', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'remove_wlw',
                        'label' => __( 'Remove Windows Live Writer link', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
        </table>
        <?php
    }
}
