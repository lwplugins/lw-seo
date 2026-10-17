<?php
/**
 * Fills missing image alt and title attributes from templates.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Images;

use LightweightPlugins\SEO\Options;

/**
 * Pure attribute logic shared by the attachment and content filters.
 */
final class AltResolver {

	/**
	 * Whether either template is switched on.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return (bool) Options::get( 'image_alt_enabled' ) || (bool) Options::get( 'image_title_enabled' );
	}

	/**
	 * Fill alt (when missing or blank) and title (when missing).
	 *
	 * @param array<string, mixed> $attrs      Image attributes.
	 * @param \WP_Post|null        $attachment Attachment.
	 * @param \WP_Post|null        $context    Post the image is shown in.
	 * @return array<string, mixed>
	 */
	public static function fill( array $attrs, ?\WP_Post $attachment, ?\WP_Post $context ): array {
		if ( self::is_decorative( $attrs ) ) {
			return $attrs;
		}

		if ( Options::get( 'image_alt_enabled' ) && '' === trim( (string) ( $attrs['alt'] ?? '' ) ) ) {
			$alt = ImageVars::replace( (string) Options::get( 'image_alt_template' ), $attachment, $context );
			if ( '' !== $alt ) {
				$attrs['alt'] = $alt;
			}
		}

		if ( Options::get( 'image_title_enabled' ) && ! isset( $attrs['title'] ) ) {
			$title = ImageVars::replace( (string) Options::get( 'image_title_template' ), $attachment, $context );
			if ( '' !== $title ) {
				$attrs['title'] = $title;
			}
		}

		return $attrs;
	}

	/**
	 * Images marked as decorative must stay as they are.
	 *
	 * @param array<string, mixed> $attrs Image attributes.
	 * @return bool
	 */
	private static function is_decorative( array $attrs ): bool {
		$role = strtolower( trim( (string) ( $attrs['role'] ?? '' ) ) );
		$hide = strtolower( trim( (string) ( $attrs['aria-hidden'] ?? '' ) ) );

		return 'presentation' === $role || 'none' === $role || 'true' === $hide;
	}
}
