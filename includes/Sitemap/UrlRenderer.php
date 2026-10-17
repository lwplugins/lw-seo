<?php
/**
 * XML sitemap markup.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

use LightweightPlugins\SEO\Options;

/**
 * Builds the sitemap index and urlset XML strings. Every value is escaped
 * here, so the caller can echo the result as it is.
 */
final class UrlRenderer {

	/**
	 * Sitemap protocol namespace.
	 */
	private const NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

	/**
	 * Image sitemap extension namespace.
	 */
	private const NS_IMAGE = 'http://www.google.com/schemas/sitemap-image/1.1';

	/**
	 * Processing instruction that links the browser stylesheet.
	 *
	 * @return string Empty when the stylesheet option is off.
	 */
	public static function stylesheet_pi(): string {
		if ( ! Options::get( 'sitemap_stylesheet' ) ) {
			return '';
		}

		return '<?xml-stylesheet type="text/xsl" href="' . esc_url( home_url( '/' . Stylesheet::URL_PATH ) ) . '"?>';
	}

	/**
	 * Sitemap index.
	 *
	 * @param array<int, array{loc: string, lastmod?: string}> $sitemaps Child sitemaps.
	 * @return string
	 */
	public static function index( array $sitemaps ): string {
		$xml = self::header() . '<sitemapindex xmlns="' . self::NS . '">' . "\n";

		foreach ( $sitemaps as $sitemap ) {
			$xml .= "\t<sitemap>\n\t\t<loc>" . esc_url( $sitemap['loc'] ) . "</loc>\n";
			$xml .= self::optional( 'lastmod', $sitemap['lastmod'] ?? '' );
			$xml .= "\t</sitemap>\n";
		}

		return $xml . '</sitemapindex>';
	}

	/**
	 * URL set.
	 *
	 * @param array<int, array{loc: string, lastmod?: string, changefreq?: string, priority?: string, images?: array<int, string>}> $items       URL entries.
	 * @param bool                                                                                                                  $with_images Whether to write image nodes.
	 * @return string
	 */
	public static function urlset( array $items, bool $with_images ): string {
		$ns  = ' xmlns="' . self::NS . '"' . ( $with_images ? ' xmlns:image="' . self::NS_IMAGE . '"' : '' );
		$xml = self::header() . '<urlset' . $ns . ">\n";

		foreach ( $items as $item ) {
			$xml .= "\t<url>\n\t\t<loc>" . esc_url( $item['loc'] ) . "</loc>\n";
			$xml .= self::optional( 'lastmod', $item['lastmod'] ?? '' );
			$xml .= self::optional( 'changefreq', $item['changefreq'] ?? '' );
			$xml .= self::optional( 'priority', $item['priority'] ?? '' );

			if ( $with_images ) {
				foreach ( $item['images'] ?? [] as $image ) {
					$xml .= "\t\t<image:image><image:loc>" . esc_url( $image ) . "</image:loc></image:image>\n";
				}
			}

			$xml .= "\t</url>\n";
		}

		return $xml . '</urlset>';
	}

	/**
	 * XML declaration plus the optional stylesheet instruction.
	 *
	 * @return string
	 */
	private static function header(): string {
		$pi = self::stylesheet_pi();

		return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . ( '' !== $pi ? $pi . "\n" : '' );
	}

	/**
	 * One optional child element.
	 *
	 * @param string $tag   Element name.
	 * @param string $value Text value.
	 * @return string
	 */
	private static function optional( string $tag, string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		$escaped = function_exists( 'esc_xml' ) ? esc_xml( $value ) : esc_html( $value );

		return "\t\t<{$tag}>" . $escaped . "</{$tag}>\n";
	}
}
