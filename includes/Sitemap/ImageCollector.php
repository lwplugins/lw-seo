<?php
/**
 * Images of a post for the image sitemap.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

/**
 * Reads the featured image and the raw post content only: no content
 * filters run, so a large sitemap page stays cheap.
 */
final class ImageCollector {

	/**
	 * Maximum images per URL (the sitemap protocol limit).
	 */
	private const MAX_IMAGES = 1000;

	/**
	 * Blocks whose saved HTML is public as written (no server-side gating).
	 */
	private const STATIC_BLOCKS = [ 'core/image', 'core/gallery', 'core/cover', 'core/media-text' ];

	/**
	 * Absolute image URLs of a post, on this site's host only.
	 *
	 * @param \WP_Post $post Post.
	 * @return array<int, string>
	 */
	public static function for_post( \WP_Post $post ): array {
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$urls = [];

		$featured = get_the_post_thumbnail_url( $post, 'full' );
		if ( is_string( $featured ) && '' !== $featured ) {
			$urls[] = $featured;
		}

		foreach ( self::sources( self::scannable_html( $post ) ) as $src ) {
			$urls[] = $src;
		}

		$clean = [];
		foreach ( $urls as $url ) {
			$absolute = self::absolute( trim( $url ) );

			if ( '' !== $absolute && wp_parse_url( $absolute, PHP_URL_HOST ) === $host ) {
				$clean[ $absolute ] = $absolute;
			}
		}

		$clean = array_slice( array_values( $clean ), 0, self::MAX_IMAGES );

		/**
		 * Filter the image URLs listed for a post in the image sitemap.
		 *
		 * @since 1.9.0
		 *
		 * @param array<int, string> $urls Absolute image URLs.
		 * @param \WP_Post           $post Post.
		 */
		return array_values( array_filter( (array) apply_filters( 'lw_seo_sitemap_images', $clean, $post ), 'is_string' ) );
	}

	/**
	 * Whether images beyond the featured image may be listed for a post.
	 *
	 * @param \WP_Post $post Post.
	 * @return bool
	 */
	public static function content_allowed( \WP_Post $post ): bool {
		/**
		 * Whether the images inside the post content (and integration
		 * galleries) may be listed.
		 *
		 * Restriction plugins can return false to list the featured image only.
		 *
		 * @since 1.9.0
		 *
		 * @param bool     $include Whether to list them (default true).
		 * @param \WP_Post $post    Post.
		 */
		return (bool) apply_filters( 'lw_seo_sitemap_content_images', true, $post );
	}

	/**
	 * The part of the post content that is safe to scan for images.
	 *
	 * Shortcodes (with their enclosed content) are dropped, and in block
	 * content only static image blocks and classic chunks are read, so
	 * access-restricted content does not leak into the public sitemap.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private static function scannable_html( \WP_Post $post ): string {
		$content = (string) $post->post_content;

		// Most posts have no inline image: skip the shortcode and block parsing.
		if ( false === stripos( $content, '<img' ) || ! self::content_allowed( $post ) ) {
			return '';
		}

		if ( function_exists( 'strip_shortcodes' ) ) {
			$content = strip_shortcodes( $content );
		}

		if ( function_exists( 'has_blocks' ) && has_blocks( $content ) ) {
			return self::block_html( parse_blocks( $content ) );
		}

		return $content;
	}

	/**
	 * Saved HTML of the allowlisted blocks, recursively.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @return string
	 */
	private static function block_html( array $blocks ): string {
		$html = '';

		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? null;

			if ( null !== $name && ! in_array( $name, self::STATIC_BLOCKS, true ) ) {
				continue;
			}

			$html .= (string) ( $block['innerHTML'] ?? '' );
			$html .= self::block_html( (array) ( $block['innerBlocks'] ?? [] ) );
		}

		return $html;
	}

	/**
	 * The `src` values of the `<img>` tags in raw HTML.
	 *
	 * @param string $html Raw post content.
	 * @return array<int, string>
	 */
	private static function sources( string $html ): array {
		if ( '' === $html || false === stripos( $html, '<img' ) ) {
			return [];
		}

		$found = [];

		if ( class_exists( '\WP_HTML_Tag_Processor' ) ) {
			$processor = new \WP_HTML_Tag_Processor( $html );
			while ( $processor->next_tag( [ 'tag_name' => 'img' ] ) ) {
				$src = $processor->get_attribute( 'src' );
				if ( is_string( $src ) && '' !== $src ) {
					$found[] = $src;
				}
			}

			return $found;
		}

		if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+)/i', $html, $matches ) ) {
			$found = $matches[1];
		}

		return $found;
	}

	/**
	 * Make a protocol-relative or relative URL absolute; drop other schemes.
	 *
	 * @param string $url Image URL.
	 * @return string Empty when the URL cannot be used.
	 */
	private static function absolute( string $url ): string {
		if ( '' === $url ) {
			return '';
		}

		if ( str_starts_with( $url, '//' ) ) {
			return 'https:' . $url;
		}

		if ( str_starts_with( $url, '/' ) ) {
			return home_url( $url );
		}

		if ( 1 === preg_match( '#^https?://#i', $url ) ) {
			return $url;
		}

		// "data:", "javascript:" and other schemes are not images to list.
		if ( 1 === preg_match( '/^[a-z][a-z0-9+.-]*:/i', $url ) ) {
			return '';
		}

		return home_url( '/' . $url );
	}
}
