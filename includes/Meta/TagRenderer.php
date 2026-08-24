<?php
/**
 * Head meta tag renderer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Meta;

use LightweightPlugins\SEO\Options;

/**
 * Prints the canonical, description, Open Graph and Twitter Card tags.
 */
final class TagRenderer {

	/**
	 * Render meta tags.
	 *
	 * @param string $title          The page title.
	 * @param string $description    The meta description.
	 * @param string $canonical      The canonical URL.
	 * @param string $og_title       The OG title.
	 * @param string $og_description The OG description.
	 * @param string $og_image       The OG image URL.
	 * @param string $og_type        The OG type.
	 * @return void
	 */
	public function render(
		string $title,
		string $description,
		string $canonical,
		string $og_title,
		string $og_description,
		string $og_image,
		string $og_type
	): void {
		echo "\n<!-- LW SEO -->\n";

		// Meta description.
		if ( ! empty( $description ) ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
		}

		// Canonical URL.
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );

		// Open Graph tags.
		if ( Options::get( 'opengraph_enabled' ) ) {
			printf( '<meta property="og:locale" content="%s" />' . "\n", esc_attr( get_locale() ) );
			printf( '<meta property="og:type" content="%s" />' . "\n", esc_attr( $og_type ) );
			printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $og_title ) );
			printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $canonical ) );
			printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );

			if ( ! empty( $og_description ) ) {
				printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $og_description ) );
			}

			if ( ! empty( $og_image ) ) {
				printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $og_image ) );
			}

			$this->render_article_times( $og_type );
		}

		// Twitter Cards.
		if ( Options::get( 'twitter_enabled' ) ) {
			printf( '<meta name="twitter:card" content="%s" />' . "\n", esc_attr( Options::get( 'twitter_card_type' ) ) );
			printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $og_title ) );

			if ( ! empty( $og_description ) ) {
				printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $og_description ) );
			}

			if ( ! empty( $og_image ) ) {
				printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $og_image ) );
			}
		}

		echo "<!-- /LW SEO -->\n\n";
	}

	/**
	 * Print article:published_time / article:modified_time for singular articles.
	 *
	 * @param string $og_type The OG type.
	 * @return void
	 */
	private function render_article_times( string $og_type ): void {
		if ( 'article' !== $og_type || ! is_singular() ) {
			return;
		}

		$post = get_queried_object();

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		printf(
			'<meta property="article:published_time" content="%s" />' . "\n",
			esc_attr( get_the_date( 'c', $post ) )
		);
		printf(
			'<meta property="article:modified_time" content="%s" />' . "\n",
			esc_attr( get_the_modified_date( 'c', $post ) )
		);
	}

	/**
	 * Print a robots meta tag for the given directives.
	 *
	 * @param string[] $directives Robots directives, e.g. [ 'noindex', 'follow' ].
	 * @return void
	 */
	public function render_robots( array $directives ): void {
		if ( empty( $directives ) ) {
			return;
		}

		printf( '<meta name="robots" content="%s" />' . "\n", esc_attr( implode( ', ', $directives ) ) );
	}
}
