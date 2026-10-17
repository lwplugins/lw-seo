<?php
/**
 * Image template variables.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Images;

use LightweightPlugins\SEO\ReplaceVars;

/**
 * Resolves %%image_title%%, %%image_caption%% and %%image_filename%% on top of
 * the regular replacement variables. ReplaceVars blanks unknown variables, so
 * the image variables are swapped for placeholders first and filled in after.
 */
final class ImageVars {

	private const NAMES = [ 'image_title', 'image_caption', 'image_filename' ];

	/**
	 * Labels of the image variables, keyed by name without the %% delimiters.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return [
			'image_title'    => __( 'Image title', 'lw-seo' ),
			'image_caption'  => __( 'Image caption', 'lw-seo' ),
			'image_filename' => __( 'Image file name', 'lw-seo' ),
		];
	}

	/**
	 * Resolve a template for one image.
	 *
	 * @param string        $template   Template with %%variables%%.
	 * @param \WP_Post|null $attachment The attachment; null resolves image variables to ''.
	 * @param \WP_Post|null $context    The post the image is displayed in.
	 * @return string Plain text, entities decoded, trimmed.
	 */
	public static function replace( string $template, ?\WP_Post $attachment, ?\WP_Post $context ): string {
		$values = self::values( $attachment );
		$used   = [];
		foreach ( self::NAMES as $name ) {
			if ( str_contains( $template, '%%' . $name . '%%' ) ) {
				$used[] = $name;
			}
		}

		foreach ( self::NAMES as $index => $name ) {
			$template = str_replace( '%%' . $name . '%%', "\x01" . $index . "\x01", $template );
		}

		$text = ReplaceVars::replace( $template, $context );

		foreach ( self::NAMES as $index => $name ) {
			$text = str_replace( "\x01" . $index . "\x01", $values[ $name ], $text );
		}

		// Image variables were requested but none resolved: nothing meaningful to say.
		if ( [] !== $used && '' === implode( '', array_intersect_key( $values, array_flip( $used ) ) ) ) {
			return '';
		}

		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );

		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * The post only when it is an attachment that may be shown publicly.
	 *
	 * @param \WP_Post|null $post Any post.
	 * @return \WP_Post|null
	 */
	public static function usable_attachment( ?\WP_Post $post ): ?\WP_Post {
		if ( null === $post || 'attachment' !== $post->post_type || (int) $post->ID <= 0 ) {
			return null;
		}

		$parent_id = (int) $post->post_parent;
		if ( $parent_id > 0 ) {
			$parent = get_post( $parent_id );
			if ( ! $parent instanceof \WP_Post || ! is_post_publicly_viewable( $parent ) ) {
				return null;
			}
		}

		return $post;
	}

	/**
	 * Raw values of the image variables.
	 *
	 * @param \WP_Post|null $attachment Attachment.
	 * @return array<string, string>
	 */
	private static function values( ?\WP_Post $attachment ): array {
		$attachment = self::usable_attachment( $attachment );
		if ( null === $attachment ) {
			return array_fill_keys( self::NAMES, '' );
		}

		$file = get_attached_file( (int) $attachment->ID );

		return [
			'image_title'    => (string) $attachment->post_title,
			'image_caption'  => (string) $attachment->post_excerpt,
			'image_filename' => is_string( $file ) ? self::humanise( $file ) : '',
		];
	}

	/**
	 * Readable name from a file path.
	 *
	 * @param string $path File path.
	 * @return string
	 */
	private static function humanise( string $path ): string {
		$name = pathinfo( $path, PATHINFO_FILENAME );
		$name = (string) preg_replace( '/-scaled$/', '', $name );
		$name = (string) preg_replace( '/-e\d{10,}$/', '', $name );
		$name = (string) preg_replace( '/-\d+x\d+$/', '', $name );
		$name = trim( (string) preg_replace( '/[-_\s]+/', ' ', $name ) );

		return ucfirst( $name );
	}
}
