<?php
/**
 * Detects redirects that share a source.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Redirects;

/**
 * Only the first redirect with a given source can ever match, so a second
 * one is dead weight. Two sources are the same when they are stored the
 * same way: plain paths after normalization ("old", "/old/" and the full
 * URL are one source), regex patterns as written (trimmed). A plain path
 * and a regex with the same text are different sources.
 */
final class DuplicateSource {

	/**
	 * Comparable key of a source.
	 *
	 * @param string $source Source path, URL or pattern.
	 * @param bool   $regex  Whether it is a regex.
	 * @return string
	 */
	public static function key( string $source, bool $regex ): string {
		return $regex ? 'r:' . trim( $source ) : 'p:' . Manager::normalize_source( trim( $source ) );
	}

	/**
	 * Position of the redirect that already has this source.
	 *
	 * @param array<int|string, mixed> $redirects Stored redirects.
	 * @param string                   $source    Source path, URL or pattern.
	 * @param bool                     $regex     Whether it is a regex.
	 * @param int|null                 $except    Position to ignore (the redirect being updated).
	 * @return int|null Null when no other redirect has it.
	 */
	public static function find( array $redirects, string $source, bool $regex, ?int $except = null ): ?int {
		$key = self::key( $source, $regex );

		foreach ( $redirects as $index => $redirect ) {
			if ( (int) $index === $except || ! is_array( $redirect ) || ! isset( $redirect['source'] ) ) {
				continue;
			}

			if ( self::key( (string) $redirect['source'], ! empty( $redirect['regex'] ) ) === $key ) {
				return (int) $index;
			}
		}

		return null;
	}

	/**
	 * Error message for a duplicate source.
	 *
	 * @return string
	 */
	public static function message(): string {
		return __( 'A redirect for this source already exists.', 'lw-seo' );
	}
}
