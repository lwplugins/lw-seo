<?php
/**
 * Social Settings Tab.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

/**
 * Handles the Social Media settings tab.
 */
final class Tab_Social implements Tab_Interface {

    use Field_Renderer;

    /**
     * Get the tab slug.
     *
     * @return string
     */
    public function get_slug(): string {
        return 'social';
    }

    /**
     * Get the tab label.
     *
     * @return string
     */
    public function get_label(): string {
        return __( 'Social', 'lw-seo' );
    }

    /**
     * Get the tab icon.
     *
     * @return string
     */
    public function get_icon(): string {
        return 'dashicons-share';
    }

    /**
     * Render the tab content.
     *
     * @return void
     */
    public function render(): void {
        ?>
        <h2><?php esc_html_e( 'Social Media', 'lw-seo' ); ?></h2>

        <div class="lw-seo-section-description">
            <p><?php esc_html_e( 'Settings for Open Graph and Twitter Cards.', 'lw-seo' ); ?></p>
        </div>

        <?php
        $this->render_meta_tags_section();
        $this->render_profiles_section();
    }

    /**
     * Render meta tags section.
     *
     * @return void
     */
    private function render_meta_tags_section(): void {
        ?>
        <h3><?php esc_html_e( 'Meta Tags', 'lw-seo' ); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Open Graph', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'opengraph_enabled',
                        'label' => __( 'Enable Open Graph meta tags', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Twitter Cards', 'lw-seo' ); ?></th>
                <td>
                    <?php
                    $this->render_checkbox_field( [
                        'name'  => 'twitter_enabled',
                        'label' => __( 'Enable Twitter Card meta tags', 'lw-seo' ),
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="twitter_card_type"><?php esc_html_e( 'Default Card Type', 'lw-seo' ); ?></label>
                </th>
                <td>
                    <?php
                    $this->render_select_field( [
                        'name'    => 'twitter_card_type',
                        'options' => [
                            'summary_large_image' => __( 'Summary with large image', 'lw-seo' ),
                            'summary'             => __( 'Summary', 'lw-seo' ),
                        ],
                    ] );
                    ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render social profiles section.
     *
     * @return void
     */
    private function render_profiles_section(): void {
        $profiles = [
            'social_facebook'  => __( 'Facebook', 'lw-seo' ),
            'social_twitter'   => __( 'Twitter / X', 'lw-seo' ),
            'social_instagram' => __( 'Instagram', 'lw-seo' ),
            'social_linkedin'  => __( 'LinkedIn', 'lw-seo' ),
            'social_youtube'   => __( 'YouTube', 'lw-seo' ),
        ];

        ?>
        <h3><?php esc_html_e( 'Social Profiles', 'lw-seo' ); ?></h3>
        <table class="form-table">
            <?php foreach ( $profiles as $name => $label ) : ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
                    </th>
                    <td><?php $this->render_url_field( [ 'name' => $name ] ); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }
}
