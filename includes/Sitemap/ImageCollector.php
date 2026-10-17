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

		foreach ( self::sources( (string) $post->post_content ) as $src ) {
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
