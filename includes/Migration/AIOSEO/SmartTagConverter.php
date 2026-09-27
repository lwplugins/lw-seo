<?php
/**
 * All in One SEO smart tag converter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\AIOSEO;

use LightweightPlugins\SEO\Migration\Support\TemplateTidy;
use LightweightPlugins\SEO\Migration\Support\VariableLog;

/**
 * Converts All in One SEO smart tags ("#post_title #separator_sa #site_title")
 * into LW SEO variables ("%%title%% %%sep%% %%sitename%%").
 *
 * Tags are matched the way All in One SEO matches them: "#tag" not followed by
 * a letter, digit or underscore, case-insensitive. Tags without an LW SEO
 * equivalent are removed and recorded; a "#word" that is not a tag is kept.
 */
final class SmartTagConverter {

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
	 * Convert the smart tags in a value.
	 *
	 * @param mixed $value Source value.
	 * @return string Converted value, '' for non-strings.
	 */
	public function convert( mixed $value ): string {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return '';
		}

		if ( ! str_contains( $value, '#' ) ) {
			return trim( $value );
		}

		$value = (string) preg_replace_callback( $this->suffix_pattern(), [ $this, 'remove' ], $value );
		$value = (string) preg_replace_callback( $this->tag_pattern(), [ $this, 'replace' ], $value );

		return TemplateTidy::tidy( $value );
	}

	/**
	 * Replace one matched tag.
	 *
	 * @param array<int, string> $matches Regex matches (1 = tag name).
	 * @return string
	 */
	private function replace( array $matches ): string {
		$tag = strtolower( $matches[1] );
		$var = Mappings::TAG_MAP[ $tag ] ?? '';

		if ( '' === $var ) {
			$this->log->removed( '#' . $tag );
			return '';
		}

		return '%%' . $var . '%%';
	}

	/**
	 * Remove one matched "#custom_field-key" / "#tax_name-taxonomy" tag.
	 *
	 * @param array<int, string> $matches Regex matches.
	 * @return string
	 */
	private function remove( array $matches ): string {
		$this->log->removed( strtolower( $matches[0] ) );
		return '';
	}

	/**
	 * Pattern matching every known tag, longest names first.
	 *
	 * @return string
	 */
	private function tag_pattern(): string {
		$names = array_keys( Mappings::TAG_MAP );
		usort( $names, static fn( string $a, string $b ): int => strlen( $b ) <=> strlen( $a ) );

		return '/#(' . implode( '|', array_map( 'preg_quote', $names ) ) . ')(?![a-zA-Z0-9_])/i';
	}

	/**
	 * Pattern matching the tags that take a "-key" suffix.
	 *
	 * @return string
	 */
	private function suffix_pattern(): string {
		return '/#(?:' . implode( '|', Mappings::SUFFIX_TAGS ) . ')-[a-zA-Z0-9_-]+/i';
	}
}
