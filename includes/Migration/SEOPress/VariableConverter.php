<?php
/**
 * SEOPress template variable converter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

use LightweightPlugins\SEO\Migration\Support\TemplateTidy;
use LightweightPlugins\SEO\Migration\Support\VariableLog;

/**
 * Converts SEOPress %%variables%% to LW SEO variables. Both plugins use the
 * %%name%% syntax, so this renames the diverging names (%%sitetitle%% →
 * %%sitename%%, %%post_title%% → %%title%%…) and removes, with a log entry,
 * every variable LW SEO cannot render.
 */
final class VariableConverter {

	/**
	 * Removed-variable log.
	 *
	 * @var VariableLog
	 */
	private VariableLog $log;

	/**
	 * Constructor.
	 *
	 * @param VariableLog $log Removed-variable log.
	 */
	public function __construct( VariableLog $log ) {
		$this->log = $log;
	}

	/**
	 * Convert the variables in a value.
	 *
	 * @param mixed $value Source value.
	 * @return string Converted value, '' for non-strings.
	 */
	public function convert( mixed $value ): string {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return '';
		}

		$value = (string) preg_replace_callback( '/%%([A-Za-z0-9_\-]+)%%/', [ $this, 'replace' ], $value );

		return TemplateTidy::tidy( $value );
	}

	/**
	 * Replace one variable.
	 *
	 * @param array<int, string> $matches Regex matches (1 = variable name).
	 * @return string
	 */
	private function replace( array $matches ): string {
		$var = Mappings::VARIABLE_MAP[ $matches[1] ] ?? '';

		if ( '' === $var ) {
			$this->log->removed( $matches[0] );
			return '';
		}

		return '%%' . $var . '%%';
	}
}
