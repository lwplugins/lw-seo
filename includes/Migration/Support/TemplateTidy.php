<?php
/**
 * Tidies converted title templates.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Support;

/**
 * Cleans up a converted template after unsupported variables were removed:
 * collapses whitespace, merges repeated separators and drops a separator left
 * dangling at the start or end ("%%sep%% %%sitename%%" from
 * "#archive_date #separator_sa #site_title").
 */
final class TemplateTidy {

	/**
	 * Tidy a converted template.
	 *
	 * @param string $template Template with LW SEO variables.
	 * @return string
	 */
	public static function tidy( string $template ): string {
		$template = (string) preg_replace( '/\s+/', ' ', $template );
		$template = (string) preg_replace( '/%%sep%%(\s*%%sep%%)+/', '%%sep%%', $template );
		$template = (string) preg_replace( '/^\s*%%sep%%\s*|\s*%%sep%%\s*$/', '', trim( $template ) );

		return trim( $template );
	}
}
