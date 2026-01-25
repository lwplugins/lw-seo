<?php
/**
 * Field Renderer Trait.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

use LightweightPlugins\SEO\Options;

/**
 * Trait for rendering form fields.
 */
trait Field_Renderer {

    /**
     * Render a text input field.
     *
     * @param array{name: string, description?: string} $args Field arguments.
     * @return void
     */
    protected function render_text_field( array $args ): void {
        $name  = $args['name'];
        $value = Options::get( $name );
        $desc  = $args['description'] ?? '';

        printf(
            '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" />',
            esc_attr( $name ),
            esc_attr( Options::OPTION_NAME ),
            esc_attr( (string) $value )
        );

        if ( $desc ) {
            printf( '<p class="description">%s</p>', esc_html( $desc ) );
        }
    }

    /**
     * Render a textarea field.
     *
     * @param array{name: string, description?: string} $args Field arguments.
     * @return void
     */
    protected function render_textarea_field( array $args ): void {
        $name  = $args['name'];
        $value = Options::get( $name );
        $desc  = $args['description'] ?? '';

        printf(
            '<textarea id="%1$s" name="%2$s[%1$s]" rows="3" class="large-text">%3$s</textarea>',
            esc_attr( $name ),
            esc_attr( Options::OPTION_NAME ),
            esc_textarea( (string) $value )
        );

        if ( $desc ) {
            printf( '<p class="description">%s</p>', esc_html( $desc ) );
        }
    }

    /**
     * Render a URL input field.
     *
     * @param array{name: string} $args Field arguments.
     * @return void
     */
    protected function render_url_field( array $args ): void {
        $name  = $args['name'];
        $value = Options::get( $name );

        printf(
            '<input type="url" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" placeholder="https://" />',
            esc_attr( $name ),
            esc_attr( Options::OPTION_NAME ),
            esc_url( (string) $value )
        );
    }

    /**
     * Render a checkbox field.
     *
     * @param array{name: string, label: string} $args Field arguments.
     * @return void
     */
    protected function render_checkbox_field( array $args ): void {
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
     * Render a select field.
     *
     * @param array{name: string, options: array<string, string>} $args Field arguments.
     * @return void
     */
    protected function render_select_field( array $args ): void {
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
     * Render separator select field.
     *
     * @return void
     */
    protected function render_separator_field(): void {
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
}
