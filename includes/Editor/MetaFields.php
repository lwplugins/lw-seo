<?php
/**
 * SEO meta field definitions.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Editor;

use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\SignalValue;

/**
 * The per-post and per-term SEO fields (`_lw_seo_{field}` meta), with the
 * one sanitizing and reading rule every save path shares (classic meta
 * box, term form, REST field).
 */
final class MetaFields {

	/**
	 * Single-line text.
	 */
	private const TEXT = 'text';

	/**
	 * Multi-line text.
	 */
	private const TEXTAREA = 'textarea';

	/**
	 * URL.
	 */
	private const URL = 'url';

	/**
	 * Robots flag, stored as '1' or deleted.
	 */
	public const FLAG = 'flag';

	/**
	 * Content signal: 'yes', 'no' or ''.
	 */
	private const SIGNAL = 'signal';

	/**
	 * Post fields => kind.
	 */
	public const POST = [
		'title'            => self::TEXT,
		'description'      => self::TEXTAREA,
		'noindex'          => self::FLAG,
		'nofollow'         => self::FLAG,
		'canonical'        => self::URL,
		'og_title'         => self::TEXT,
		'og_description'   => self::TEXTAREA,
		'og_image'         => self::URL,
		'ai_train'         => self::SIGNAL,
		'ai_input'         => self::SIGNAL,
		'search'           => self::SIGNAL,
		'markdown_content' => self::TEXTAREA,
	];

	/**
	 * Term fields => kind.
	 */
	public const TERM = [
		'title'            => self::TEXT,
		'description'      => self::TEXTAREA,
		'noindex'          => self::FLAG,
		'og_title'         => self::TEXT,
		'og_description'   => self::TEXTAREA,
		'og_image'         => self::URL,
		'ai_train'         => self::SIGNAL,
		'ai_input'         => self::SIGNAL,
		'search'           => self::SIGNAL,
		'markdown_content' => self::TEXTAREA,
	];

	/**
	 * Sanitize a submitted value for storage; '' means "delete the meta".
	 *
	 * @param string $kind  Field kind (a value of POST / TERM).
	 * @param mixed  $value Submitted value.
	 * @return string
	 */
	public static function sanitize( string $kind, mixed $value ): string {
		if ( self::FLAG === $kind ) {
			return filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ? '1' : '';
		}

		if ( self::SIGNAL === $kind ) {
			return SignalValue::sanitize( $value );
		}

		$value = is_scalar( $value ) && ! is_bool( $value ) ? (string) $value : '';

		return match ( $kind ) {
			self::TEXTAREA => sanitize_textarea_field( $value ),
			self::URL      => esc_url_raw( $value ),
			default        => sanitize_text_field( $value ),
		};
	}

	/**
	 * Read stored values for the editors: flags as booleans, everything
	 * else as strings (og_image coerced from migrated array shapes).
	 *
	 * @param array<string, string> $fields Field => kind (POST or TERM).
	 * @param callable              $get    Reads the raw meta for a field.
	 * @return array<string, bool|string>
	 */
	public static function read( array $fields, callable $get ): array {
		$values = [];

		foreach ( $fields as $field => $kind ) {
			$raw = $get( $field );

			if ( self::FLAG === $kind ) {
				$values[ $field ] = '1' === MetaCoerce::as_string( $raw );
			} elseif ( 'og_image' === $field ) {
				$values[ $field ] = MetaCoerce::as_url( $raw );
			} else {
				$values[ $field ] = MetaCoerce::as_string( $raw );
			}
		}

		return $values;
	}
}
