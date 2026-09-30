<?php
/**
 * Content query arguments filter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Content;

/**
 * Runs the arguments of the queries that list a site's content (XML
 * sitemaps, llms.txt) through one filter, so an integration can adjust
 * them (Polylang: every language).
 */
final class QueryArgs {

	/**
	 * Posts of an XML sitemap.
	 */
	public const SITEMAP_POSTS = 'sitemap_posts';

	/**
	 * Terms of an XML sitemap.
	 */
	public const SITEMAP_TERMS = 'sitemap_terms';

	/**
	 * Posts of an llms.txt section.
	 */
	public const LLMS_POSTS = 'llms_posts';

	/**
	 * Filtered query arguments.
	 *
	 * @param array<string, mixed> $args    get_posts() / get_terms() arguments.
	 * @param string               $context One of the constants above.
	 * @return array<string, mixed>
	 */
	public static function filter( array $args, string $context ): array {
		/**
		 * Filter the arguments of a query that lists the site's content.
		 *
		 * @internal Used by the Polylang integration; not a public API yet.
		 *
		 * @param mixed  $args    Query arguments; a non-array return is ignored.
		 * @param string $context sitemap_posts, sitemap_terms or llms_posts.
		 */
		$filtered = apply_filters( 'lw_seo_query_args', $args, $context );

		return is_array( $filtered ) ? $filtered : $args;
	}
}
