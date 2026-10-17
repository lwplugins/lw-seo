<?php
/**
 * Alt/title templates for wp_get_attachment_image().
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Images;

use LightweightPlugins\SEO\Meta\HeadMeta;

/**
 * Output-time filter; the media library is never modified.
 */
final class AttachmentAttributes {

	/**
	 * Whether templates may be applied to the current request.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return AltResolver::is_enabled()
			&& ! is_admin()
			&& ! is_feed()
			&& ! ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			&& ! HeadMeta::is_conflicting_plugin_active();
	}

	/**
	 * Register the filter.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'wp_get_attachment_image_attributes', [ $this, 'filter' ], 20, 3 );
	}

	/**
	 * Filter callback.
	 *
	 * @param array<string, mixed> $attr       Image attributes.
	 * @param \WP_Post             $attachment Attachment.
	 * @param string|int[]         $size       Requested size (unused).
	 * @return array<string, mixed>
	 */
	public function filter( $attr, $attachment = null, $size = '' ) {
		unset( $size );
		if ( ! self::is_active() ) {
			return $attr;
		}

		$context = get_post();

		return AltResolver::fill(
			$attr,
			$attachment instanceof \WP_Post ? $attachment : null,
			$context instanceof \WP_Post ? $context : null
		);
	}
}
