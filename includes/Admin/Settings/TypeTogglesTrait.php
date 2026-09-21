<?php
/**
 * Per-type toggle renderer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

use LightweightPlugins\SEO\Options;

/**
 * Renders one checkbox per object type, stored as a name => bool map option.
 */
trait TypeTogglesTrait {

	/**
	 * Render the checkboxes.
	 *
	 * A hidden "0" precedes each checkbox so unticking is saved explicitly
	 * (unticked checkboxes are not submitted at all).
	 *
	 * @param string                $option  Option key holding the map.
	 * @param array<string, string> $types   Type name => label.
	 * @param bool                  $default State for types missing from the map.
	 * @return void
	 */
	protected function render_type_toggles( string $option, array $types, bool $default ): void {
		$map = Options::get( $option );
		$map = is_array( $map ) ? $map : [];

		echo '<fieldset>';
		foreach ( $types as $name => $label ) {
			$field   = sprintf( '%s[%s][%s]', Options::OPTION_NAME, $option, $name );
			$checked = array_key_exists( $name, $map ) ? (bool) $map[ $name ] : $default;

			printf(
				'<input type="hidden" name="%1$s" value="0" /><label><input type="checkbox" name="%1$s" value="1" %2$s /> %3$s <code>%4$s</code></label><br />',
				esc_attr( $field ),
				checked( $checked, true, false ),
				esc_html( $label ),
				esc_html( $name )
			);
		}
		echo '</fieldset>';
	}
}
