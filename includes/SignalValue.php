<?php
/**
 * Content signal value.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Three-state content signal: 'yes', 'no', or '' (not set).
 *
 * @see https://blog.cloudflare.com/content-signals-policy/
 */
final class SignalValue {

	/**
	 * Signal => option key, in the Content Signals Policy order.
	 */
	public const KEYS = [
		'search'   => 'content_signals_search',
		'ai-input' => 'content_signals_ai_input',
		'ai-train' => 'content_signals_ai_train',
	];

	/**
	 * Normalise a stored or submitted value.
	 *
	 * Booleans come from pre-1.6.0 options.
	 *
	 * @param mixed $value Raw value.
	 * @return string 'yes', 'no' or ''.
	 */
	public static function sanitize( mixed $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'yes' : 'no';
		}

		$value = is_string( $value ) ? strtolower( trim( $value ) ) : '';

		return in_array( $value, [ 'yes', 'no' ], true ) ? $value : '';
	}
}
