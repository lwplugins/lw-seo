<?php
/**
 * Bricks page settings <-> LW SEO fields.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Compat;

/**
 * Maps the SEO part of a Bricks page settings array (`_bricks_page_settings`)
 * to LW SEO post fields and back. Text only ever travels when it is not
 * empty and can be converted (see BricksTags), so neither side's text is
 * cleared by the other; robots flags travel both ways, as on/off.
 */
final class BricksSeoMap {

	/**
	 * Bricks text setting => LW SEO field.
	 */
	public const TEXT = [
		'documentTitle'      => 'title',
		'metaDescription'    => 'description',
		'sharingTitle'       => 'og_title',
		'sharingDescription' => 'og_description',
	];

	/**
	 * LW SEO robots field => Bricks metaRobots value.
	 */
	public const FLAGS = [
		'noindex'  => 'noindex',
		'nofollow' => 'nofollow',
	];

	/**
	 * Every LW SEO field kept in sync.
	 */
	public const FIELDS = [ 'title', 'description', 'og_title', 'og_description', 'og_image', 'noindex', 'nofollow' ];

	/**
	 * LW SEO values held by Bricks settings. Empty, unconvertible and dynamic
	 * values are left out; the robots flags are always there ('1' or '').
	 *
	 * @param array<string, mixed> $settings Bricks page settings.
	 * @return array<string, string>
	 */
	public static function to_lw( array $settings ): array {
		$values = [];

		foreach ( self::TEXT as $key => $field ) {
			$text = isset( $settings[ $key ] ) && is_string( $settings[ $key ] ) ? trim( $settings[ $key ] ) : '';
			$text = '' === $text ? null : BricksTags::to_lw( $text );

			if ( null !== $text ) {
				$values[ $field ] = $text;
			}
		}

		$image = $settings['sharingImage'] ?? null;
		if ( is_array( $image ) && empty( $image['useDynamicData'] ) && ! empty( $image['url'] ) && is_string( $image['url'] ) ) {
			$values['og_image'] = $image['url'];
		}

		$robots = self::robots( $settings );
		foreach ( self::FLAGS as $field => $flag ) {
			$values[ $field ] = in_array( $flag, $robots, true ) ? '1' : '';
		}

		return $values;
	}

	/**
	 * LW SEO values a Bricks save changed: edited non-empty text and image,
	 * and every robots flag that was switched on or off.
	 *
	 * @param array<string, mixed> $old Bricks settings before the save.
	 * @param array<string, mixed> $new Bricks settings after the save.
	 * @return array<string, string>
	 */
	public static function changes( array $old, array $new ): array {
		$before  = self::to_lw( $old );
		$changes = [];

		foreach ( self::to_lw( $new ) as $field => $value ) {
			if ( ( $before[ $field ] ?? '' ) !== $value && ( '' !== $value || isset( self::FLAGS[ $field ] ) ) ) {
				$changes[ $field ] = $value;
			}
		}

		return $changes;
	}

	/**
	 * Bricks settings with one LW SEO field's value written in.
	 *
	 * @param array<string, mixed> $settings Bricks page settings.
	 * @param string               $field    LW SEO field.
	 * @param string               $value    Its new value.
	 * @param int                  $image_id Attachment ID of an og_image URL (0 = unknown).
	 * @return array<string, mixed>|null Null when nothing may be written: empty
	 *                                   or unconvertible text, unmapped field.
	 */
	public static function apply( array $settings, string $field, string $value, int $image_id = 0 ): ?array {
		if ( isset( self::FLAGS[ $field ] ) ) {
			return self::apply_flag( $settings, self::FLAGS[ $field ], '' !== $value );
		}

		$key = array_search( $field, self::TEXT, true );
		if ( '' === $value || ( false === $key && 'og_image' !== $field ) ) {
			return null;
		}

		if ( 'og_image' === $field ) {
			$settings['sharingImage'] = array_filter(
				[
					'id'       => $image_id,
					'filename' => basename( (string) wp_parse_url( $value, PHP_URL_PATH ) ),
					'size'     => 'full',
					'full'     => $value,
					'url'      => $value,
				]
			);

			return $settings;
		}

		$text = BricksTags::to_bricks( $value );
		if ( null === $text ) {
			return null;
		}

		$settings[ $key ] = $text;

		return $settings;
	}

	/**
	 * Switch one metaRobots flag, expanding Bricks' 'none' first.
	 *
	 * @param array<string, mixed> $settings Bricks page settings.
	 * @param string               $flag     noindex|nofollow.
	 * @param bool                 $on       Whether the flag is set.
	 * @return array<string, mixed>
	 */
	private static function apply_flag( array $settings, string $flag, bool $on ): array {
		$robots = array_values( array_diff( self::robots( $settings ), [ $flag ] ) );

		if ( $on ) {
			$robots[] = $flag;
		}

		if ( [] === $robots ) {
			unset( $settings['metaRobots'] );
		} else {
			$settings['metaRobots'] = $robots;
		}

		return $settings;
	}

	/**
	 * The metaRobots values, with 'none' written out as noindex + nofollow.
	 *
	 * @param array<string, mixed> $settings Bricks page settings.
	 * @return array<int, string>
	 */
	private static function robots( array $settings ): array {
		$robots = is_array( $settings['metaRobots'] ?? null ) ? array_values( array_filter( $settings['metaRobots'], 'is_string' ) ) : [];

		if ( in_array( 'none', $robots, true ) ) {
			$robots = array_merge( array_diff( $robots, [ 'none' ] ), [ 'noindex', 'nofollow' ] );
		}

		return array_values( array_unique( $robots ) );
	}
}
