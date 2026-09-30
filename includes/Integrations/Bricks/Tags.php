<?php
/**
 * Bricks dynamic tag <-> LW SEO variable conversion.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\Bricks;

/**
 * Translates the Bricks dynamic data tags that have an LW SEO variable
 * counterpart ({post_title} <-> %%title%%). Text holding a tag or variable
 * without a counterpart (or a tag with arguments, {post_title:10}) is not
 * convertible: both methods return null so the caller leaves that field
 * alone instead of copying text the other side would print literally.
 */
final class Tags {

	/**
	 * Bricks tag => LW SEO variable name.
	 */
	private const MAP = [
		'post_title'    => 'title',
		'post_excerpt'  => 'excerpt',
		'post_id'       => 'id',
		'post_date'     => 'date',
		'post_modified' => 'modified',
		'author_name'   => 'author',
		'site_title'    => 'sitename',
		'site_tagline'  => 'sitedesc',
		'current_date'  => 'currentdate',
	];

	/**
	 * A Bricks tag: {name} or {name:arguments}.
	 */
	private const BRICKS_TAG = '/\{([a-z_][a-z0-9_]*)(:[^{}\s]*)?\}/';

	/**
	 * An LW SEO variable.
	 */
	private const LW_VARIABLE = '/%%([a-z_]+)%%/';

	/**
	 * Bricks text as LW SEO text.
	 *
	 * @param string $text Bricks setting value.
	 * @return string|null Null when a tag has no LW SEO counterpart.
	 */
	public static function to_lw( string $text ): ?string {
		return self::convert( $text, self::BRICKS_TAG, self::MAP, '%%%%%s%%%%' );
	}

	/**
	 * LW SEO text as Bricks text.
	 *
	 * @param string $text LW SEO field value.
	 * @return string|null Null when a variable has no Bricks counterpart.
	 */
	public static function to_bricks( string $text ): ?string {
		return self::convert( $text, self::LW_VARIABLE, array_flip( self::MAP ), '{%s}' );
	}

	/**
	 * Replace every match through the map; any unmapped match fails the text.
	 *
	 * @param string                $text    Source text.
	 * @param string                $pattern Placeholder pattern (group 1 = name, group 2 = arguments).
	 * @param array<string, string> $map     Source name => target name.
	 * @param string                $format  sprintf() format of a target placeholder.
	 * @return string|null
	 */
	private static function convert( string $text, string $pattern, array $map, string $format ): ?string {
		$convertible = true;

		$converted = preg_replace_callback(
			$pattern,
			static function ( array $match ) use ( $map, $format, &$convertible ): string {
				if ( ! empty( $match[2] ) || ! isset( $map[ $match[1] ] ) ) {
					$convertible = false;
					return $match[0];
				}

				return sprintf( $format, $map[ $match[1] ] );
			},
			$text
		);

		return $convertible && null !== $converted ? $converted : null;
	}
}
