<?php
/**
 * IndexNow API key.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\IndexNow;

/**
 * The key lives in its own option so a settings save can never wipe it.
 */
final class Key {

	/**
	 * Option that stores the key.
	 */
	public const OPTION = 'lw_seo_indexnow_key';

	/**
	 * Stored key, or an empty string when none (or a malformed one) is stored.
	 *
	 * @return string
	 */
	public static function get(): string {
		$key = get_option( self::OPTION, '' );

		return is_string( $key ) && 1 === preg_match( '/^[a-f0-9]{32}$/', $key ) ? $key : '';
	}

	/**
	 * Stored key; generated and saved first if missing.
	 *
	 * @return string
	 */
	public static function ensure(): string {
		$key = self::get();

		return '' !== $key ? $key : self::regenerate();
	}

	/**
	 * Replace the key with a new random one.
	 *
	 * @return string
	 */
	public static function regenerate(): string {
		$key = bin2hex( random_bytes( 16 ) );
		update_option( self::OPTION, $key, true );

		return $key;
	}

	/**
	 * Public URL of the key file.
	 *
	 * @return string
	 */
	public static function location(): string {
		return home_url( '/' . self::get() . '.txt' );
	}
}
