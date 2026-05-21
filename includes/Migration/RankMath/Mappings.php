<?php
/**
 * RankMath migration mapping constants.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\RankMath;

/**
 * Mapping constants for RankMath to LW SEO migration.
 *
 * Map iteration order matters: PostMetaMigrator/TermMetaMigrator copy from
 * the first non-empty source whose target slot is still empty, so primary
 * keys (rank_math_facebook_*) must precede fallbacks (rank_math_twitter_*).
 */
final class Mappings {

	/**
	 * RankMath post meta key → LW SEO meta field name (without prefix).
	 *
	 * Twitter and og_content_image map to og_* targets because LW SEO renders
	 * Twitter Cards from the OpenGraph values (see RestApi::get_twitter_*).
	 */
	public const POST_META_MAP = [
		// Primary single-source mappings.
		'rank_math_title'                => 'title',
		'rank_math_description'          => 'description',
		'rank_math_canonical_url'        => 'canonical',
		'rank_math_facebook_title'       => 'og_title',
		'rank_math_facebook_description' => 'og_description',
		'rank_math_facebook_image'       => 'og_image',

		// Fallbacks: only applied if the LW SEO target is still empty
		// after the primary keys above were processed.
		'rank_math_og_content_image'     => 'og_image',
		'rank_math_twitter_title'        => 'og_title',
		'rank_math_twitter_description'  => 'og_description',
		'rank_math_twitter_image'        => 'og_image',
	];

	/**
	 * Primary term map: RankMath meta key → LW SEO meta key (without prefix).
	 *
	 * RankMath stores the primary term ID per taxonomy. Target meta is
	 * '_lw_seo_primary_{taxonomy}' — consumed by future Schema/breadcrumb code.
	 */
	public const PRIMARY_TERM_MAP = [
		'rank_math_primary_category'      => 'primary_category',
		'rank_math_primary_product_cat'   => 'primary_product_cat',
		'rank_math_primary_product_brand' => 'primary_product_brand',
	];

	/**
	 * RankMath meta keys that have no LW SEO equivalent.
	 *
	 * Counted and reported so users know data was inspected but not moved.
	 * Internal/score/analytic keys are intentionally NOT listed — they are
	 * vendor-specific runtime caches with no migration value.
	 */
	public const NON_MIGRATABLE_META = [
		'rank_math_advanced_robots',
		'rank_math_breadcrumb_title',
		'rank_math_focus_keyword',
		'rank_math_news_sitemap_robots',
		'rank_math_pillar_content',
		'rank_math_lock_modified_date',
	];

	/**
	 * Comparison type → LW SEO redirect regex flag.
	 *
	 * Used by RedirectsMigrator to translate RankMath's source comparison
	 * modes into LW SEO's regex/non-regex storage.
	 */
	public const REDIRECT_COMPARISON_MAP = [
		'exact'    => false,
		'regex'    => true,
		'contains' => true,
		'start'    => true,
		'end'      => true,
	];

	/**
	 * RankMath title options → LW SEO options mapping.
	 */
	public const TITLE_OPTIONS_MAP = [
		'title_separator'      => 'separator',
		'homepage_title'       => 'title_home',
		'homepage_description' => 'desc_home',
		'pt_post_title'        => 'title_post',
		'pt_page_title'        => 'title_page',
		'pt_product_title'     => 'title_product',
		'tax_category_title'   => 'title_category',
		'tax_post_tag_title'   => 'title_post_tag',
		'author_archive_title' => 'title_author',
		'date_archive_title'   => 'title_date',
		'search_title'         => 'title_search',
		'404_title'            => 'title_404',
		'pt_post_robots'       => 'noindex_post',
		'pt_page_robots'       => 'noindex_page',
		'pt_product_robots'    => 'noindex_product',
		'tax_category_robots'  => 'noindex_category',
		'tax_post_tag_robots'  => 'noindex_post_tag',
		'author_robots'        => 'noindex_author',
		'date_robots'          => 'noindex_date',
	];

	/**
	 * RankMath general options → LW SEO options mapping.
	 */
	public const GENERAL_OPTIONS_MAP = [
		'knowledgegraph_type'             => 'knowledge_type',
		'knowledgegraph_name'             => 'knowledge_name',
		'knowledgegraph_logo'             => 'knowledge_logo',
		'social_url_facebook'             => 'social_facebook',
		'twitter_author_names'            => 'social_twitter',
		'social_url_instagram'            => 'social_instagram',
		'social_url_linkedin'             => 'social_linkedin',
		'social_url_youtube'              => 'social_youtube',
		'breadcrumbs'                     => 'breadcrumbs_enabled',
		'wc_remove_category_base'         => 'wc_remove_category_base',
		'wc_remove_category_parent_slugs' => 'wc_remove_category_parent_slugs',
		'wc_remove_product_base'          => 'wc_remove_product_base',
	];

	/**
	 * RankMath sitemap options → LW SEO options mapping.
	 */
	public const SITEMAP_OPTIONS_MAP = [
		'pt_post_sitemap'      => 'sitemap_posts',
		'pt_page_sitemap'      => 'sitemap_pages',
		'tax_category_sitemap' => 'sitemap_categories',
		'tax_post_tag_sitemap' => 'sitemap_tags',
		'pt_product_sitemap'   => 'sitemap_products',
	];

	/**
	 * RankMath variable name → LW SEO variable name mapping.
	 */
	public const VARIABLE_NAME_MAP = [
		'search_query'     => 'searchphrase',
		'name'             => 'author',
		'term'             => 'term_title',
		'term_description' => 'term_description',
	];
}
