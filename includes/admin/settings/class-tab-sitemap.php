<?php
/**
 * Sitemap Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

/**
 * Handles the XML Sitemap settings tab.
 */
final class Tab_Sitemap implements Tab_Interface {

    use Field_Renderer;

    /**
     * Get the tab slug.
     *
     * @return string
     */
    public function get_slug(): string {
        return 'sitemap';
    }

    /**
     * Get the tab label.
     *
     * @return string
     */
    public function get_label(): string {
        return __( 'Sitemap', 'lw-seo' );
    }

    /**
     * Get the tab icon.
     *
     * @return string
     */
    public function get_icon(): string {
        return 'dashicons-sitemap';
    }

    /**
     * Render the tab content.
     *
     * @return void
     */
    public function render(): void {
        $sitemap_url = home_url( '/sitemap.xml' );
        ?>
        <h2><?php esc_html_e( 'XML Sitemap', 'lw-seo' ); ?></h2>

        <div class="lw-seo-section-description">
            <p>
                <?php esc_html_e( 'Your sitemap:', 'lw-seo' ); ?>
                <a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank"><?php echo esc_html( $sitemap_url ); ?></a>
            </p>
        </div>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'XML Sitemap', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'sitemap_enabled',
                        'label' => __( 'Enable XML sitemap', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Include Posts', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'sitemap_posts',
                        'label' => __( 'Include posts in sitemap', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Include Pages', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'sitemap_pages',
                        'label' => __( 'Include pages in sitemap', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Include Categories', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'sitemap_categories',
                        'label' => __( 'Include categories in sitemap', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
        </table>
        <?php
    }
}
