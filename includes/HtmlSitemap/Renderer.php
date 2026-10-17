<?php
/**
 * HTML sitemap markup.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\HtmlSitemap;

/**
 * Renders the collected sections as a nav with nested lists.
 */
final class Renderer {

	/**
	 * Render sections; sections without items are left out.
	 *
	 * @param array<int, array{label: string, type: string, items: array<int, array{title: string, url: string, depth: int}>, archive_url: ?string}> $sections Sections.
	 * @return string Empty when there is nothing to list.
	 */
	public static function render( array $sections ): string {
		$html = '';
		foreach ( $sections as $section ) {
			if ( array() === $section['items'] ) {
				continue;
			}

			$html .= '<section><h2>' . esc_html( $section['label'] ) . '</h2>' . self::list( $section['items'] );

			if ( null !== $section['archive_url'] ) {
				$html .= '<p class="lw-html-sitemap__more"><a href="' . esc_url( $section['archive_url'] ) . '">' . esc_html__( 'View all', 'lw-seo' ) . '</a></p>';
			}

			$html .= '</section>';
		}

		if ( '' === $html ) {
			return '';
		}

		return '<nav class="lw-html-sitemap" aria-label="' . esc_attr__( 'Sitemap', 'lw-seo' ) . '">' . $html . '</nav>';
	}

	/**
	 * Nested list of items. A level is only opened one step at a time.
	 *
	 * @param array<int, array{title: string, url: string, depth: int}> $items Items in tree order.
	 * @return string
	 */
	private static function list( array $items ): string {
		$html  = '<ul>';
		$level = 0;

		foreach ( $items as $index => $item ) {
			$depth = max( 0, min( $item['depth'], $level + 1 ) );

			if ( 0 === $index ) {
				$depth = 0;
			} elseif ( $depth > $level ) {
				$html .= '<ul>';
			} else {
				$html .= str_repeat( '</li></ul>', $level - $depth ) . '</li>';
			}

			$html .= '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['title'] ) . '</a>';
			$level = $depth;
		}

		return $html . str_repeat( '</li></ul>', $level + 1 );
	}
}
