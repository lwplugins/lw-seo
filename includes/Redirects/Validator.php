<?php
/**
 * Redirect input validator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Redirects;

/**
 * Validates a redirect before it is added or updated.
 */
final class Validator {

	/**
	 * Types that answer with a status code only, without a destination.
	 */
	public const NO_DESTINATION_TYPES = [ 410, 451 ];

	/**
	 * Return the first problem with the input, or null when it is valid.
	 *
	 * @param string $source      Source path or pattern.
	 * @param string $destination Destination URL.
	 * @param int    $type        Redirect type (unknown types fall back to 301).
	 * @param bool   $regex       Whether the source is a regex.
	 * @return string|null Translated error message.
	 */
	public static function error( string $source, string $destination, int $type, bool $regex ): ?string {
		if ( '' === trim( $source ) ) {
			return __( 'Source URL is required.', 'lw-seo' );
		}

		if ( ! in_array( self::normalize_type( $type ), self::NO_DESTINATION_TYPES, true ) && '' === trim( $destination ) ) {
			return __( 'Destination URL is required for this redirect type.', 'lw-seo' );
		}

		if ( $regex && ! self::is_valid_regex( $source ) ) {
			return __( 'Invalid regex pattern.', 'lw-seo' );
		}

		return null;
	}

	/**
	 * A supported type, 301 for anything else (as Manager does).
	 *
	 * @param int $type Requested type.
	 * @return int
	 */
	public static function normalize_type( int $type ): int {
		return isset( Manager::TYPES[ $type ] ) ? $type : 301;
	}

	/**
	 * Whether the source compiles as the pattern Manager::find_match() builds.
	 *
	 * @param string $source Source pattern.
	 * @return bool
	 */
	private static function is_valid_regex( string $source ): bool {
		$pattern = '@' . str_replace( '@', '\\@', $source ) . '@i';

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- An invalid user pattern must not emit a warning.
		return false !== @preg_match( $pattern, '' );
	}
}
