<?php
/**
 * Alt/title templates for images inside post content.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Images;

/**
 * Thin adapter between wp_content_img_tag and AltResolver.
 */
final class ContentImages {

	private const ATTRIBUTES = [ 'alt', 'title', 'role', 'aria-hidden' ];

	/**
	 * Register the filter.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'wp_content_img_tag', [ $this, 'filter' ], 20, 3 );
	}

	/**
	 * Filter callback.
	 *
	 * @param string $html          The img tag.
	 * @param string $context       Context (unused).
	 * @param int    $attachment_id Attachment ID, 0 when unknown.
	 * @return string
	 */
	public function filter( $html, $context = '', $attachment_id = 0 ) {
		unset( $context );
		if ( ! AttachmentAttributes::is_active() ) {
			return $html;
		}

		$processor = new \WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag( [ 'tag_name' => 'img' ] ) ) {
			return $html;
		}

		$before = [];
		foreach ( self::ATTRIBUTES as $name ) {
			$value = $processor->get_attribute( $name );
			if ( null !== $value ) {
				$before[ $name ] = true === $value ? '' : (string) $value;
			}
		}

		$attachment = (int) $attachment_id > 0 ? get_post( (int) $attachment_id ) : null;
		$attachment = $attachment instanceof \WP_Post ? ImageVars::usable_attachment( $attachment ) : null;
		$post       = get_post();
		$after      = AltResolver::fill(
			$before,
			$attachment,
			$post instanceof \WP_Post ? $post : null
		);

		if ( $after === $before ) {
			return $html;
		}

		foreach ( [ 'alt', 'title' ] as $name ) {
			if ( ( $after[ $name ] ?? null ) !== ( $before[ $name ] ?? null ) ) {
				$processor->set_attribute( $name, (string) $after[ $name ] );
			}
		}

		return $processor->get_updated_html();
	}
}
