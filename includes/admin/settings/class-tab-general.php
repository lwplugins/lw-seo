<?php
/**
 * General Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

/**
 * Handles the General settings tab.
 */
final class Tab_General implements Tab_Interface {

    use Field_Renderer;

    /**
     * Get the tab slug.
     *
     * @return string
     */
    public function get_slug(): string {
        return 'general';
    }

    /**
     * Get the tab label.
     *
     * @return string
     */
    public function get_label(): string {
        return __( 'General', 'lw-seo' );
    }

    /**
     * Get the tab icon.
     *
     * @return string
     */
    public function get_icon(): string {
        return 'dashicons-admin-settings';
    }

    /**
     * Render the tab content.
     *
     * @return void
     */
    public function render(): void {
        ?>
        <h2><?php esc_html_e( 'General Settings', 'lw-seo' ); ?></h2>

        <div class="lw-seo-section-description">
            <p><?php esc_html_e( 'Basic SEO settings for your site.', 'lw-seo' ); ?></p>
        </div>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="separator"><?php esc_html_e( 'Title Separator', 'lw-seo' ); ?></label>
                </th>
                <td><?php $this->render_separator_field(); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="title_home"><?php esc_html_e( 'Homepage Title', 'lw-seo' ); ?></label>
                </th>
                <td>
                    <?php
                    $this->render_text_field( [
                        'name'        => 'title_home',
                        'description' => __( 'Variables: %%sitename%%, %%sitedesc%%, %%sep%%', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="desc_home"><?php esc_html_e( 'Homepage Description', 'lw-seo' ); ?></label>
                </th>
                <td>
                    <?php
                    $this->render_textarea_field( [
                        'name'        => 'desc_home',
                        'description' => __( 'Leave empty to use the site tagline.', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="knowledge_type"><?php esc_html_e( 'Site Represents', 'lw-seo' ); ?></label>
                </th>
                <td>
                    <?php
                    $this->render_select_field( [
                        'name'    => 'knowledge_type',
                        'options' => [
                            'organization' => __( 'Organization', 'lw-seo' ),
                            'person'       => __( 'Person', 'lw-seo' ),
                        ],
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="knowledge_name"><?php esc_html_e( 'Organization/Person Name', 'lw-seo' ); ?></label>
                </th>
                <td>
                    <?php
                    $this->render_text_field( [
                        'name'        => 'knowledge_name',
                        'description' => __( 'Used in Schema.org markup.', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
        </table>
        <?php
    }
}
