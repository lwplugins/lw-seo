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
 */
final class Mappings {

	/**
	 * RankMath post meta key → LW SEO meta field name (without prefix).
	 */
	public const POST_META_MAP = [
		'rank_math_title'                => 'title',
		'rank_math_description'          => 'description',
		'rank_math_canonical_url'        => 'canonical',
		'rank_math_facebook_title'       => 'og_title',
		'rank_math_facebook_description' => 'og_description',
		'rank_math_facebook_image'       => 'og_image',
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
		'knowledgegraph_type'  => 'knowledge_type',
		'knowledgegraph_name'  => 'knowledge_name',
		'knowledgegraph_logo'  => 'knowledge_logo',
		'social_url_facebook'  => 'social_facebook',
		'twitter_author_names' => 'social_twitter',
		'social_url_instagram' => 'social_instagram',
		'social_url_linkedin'  => 'social_linkedin',
		'social_url_youtube'   => 'social_youtube',
		'breadcrumbs'          => 'breadcrumbs_enabled',
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
