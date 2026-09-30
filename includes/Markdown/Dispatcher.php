<?php
/**
 * Markdown Dispatcher.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

/**
 * Selects the appropriate renderer and assembles markdown output.
 */
final class Dispatcher {

	/**
	 * Build markdown response for the current queried object.
	 *
	 * @param \WP_Post|\WP_Term $object Queried object.
	 * @return string Full markdown output.
	 */
	public static function dispatch( \WP_Post|\WP_Term $object ): string {
		$renderer = self::get_renderer( $object );
		$output   = Frontmatter::build( $renderer->frontmatter() ) . "\n" . $renderer->body();

		/**
		 * Filter the complete markdown output.
		 *
		 * @param string            $output Full markdown output.
		 * @param \WP_Post|\WP_Term $object Queried object.
		 */
		return (string) apply_filters( 'lw_seo_markdown_output', $output, $object );
	}

	/**
	 * Markdown body of an object, without frontmatter.
	 *
	 * @param \WP_Post|\WP_Term $object Post or term.
	 * @return string
	 */
	public static function body( \WP_Post|\WP_Term $object ): string {
		return self::get_renderer( $object )->body();
	}

	/**
	 * Get the appropriate renderer for an object.
	 *
	 * @param \WP_Post|\WP_Term $object Queried object.
	 * @return RendererInterface
	 */
	private static function get_renderer( \WP_Post|\WP_Term $object ): RendererInterface {
		if ( $object instanceof \WP_Term ) {
			return new TaxonomyRenderer( $object );
		}

		/**
		 * Renderer of a post type with its own Markdown layout.
		 *
		 * @internal Used by the WooCommerce integration; not a public API yet.
		 *
		 * @param mixed    $renderer Null by default (the post renderer); anything
		 *                           but a RendererInterface is ignored.
		 * @param \WP_Post $post     Post object.
		 */
		$renderer = apply_filters( 'lw_seo_markdown_renderer', null, $object );

		return $renderer instanceof RendererInterface ? $renderer : new PostRenderer( $object );
	}
}
