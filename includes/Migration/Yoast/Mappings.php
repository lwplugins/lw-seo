<?php
/**
 * Yoast SEO → LW SEO migration mapping constants.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

/**
 * Mapping constants for Yoast to LW SEO migration.
 *
 * Map order matters: primary (opengraph) keys precede twitter fallbacks so the
 * first non-empty source fills each still-empty LW SEO target.
 *
 * Verified against Yoast/wordpress-seo trunk (2026-07-17):
 *   - post meta-robots-noindex: '0'=default, '2'=index, '1'=noindex
 *   - term wpseo_noindex: string 'default'|'index'|'noindex'
 *   - term description field is 'wpseo_desc' (not 'wpseo_metadesc')
 *   - knowledge-graph keys live in wpseo_titles
 */
final class Mappings {

	/**
	 * Yoast post meta key (with _yoast_wpseo_ prefix) → LW SEO field (no prefix).
	 */
	public const POST_META_MAP = [
		'_yoast_wpseo_title'                 => 'title',
		'_yoast_wpseo_metadesc'              => 'description',
		'_yoast_wpseo_canonical'             => 'canonical',
		'_yoast_wpseo_opengraph-title'       => 'og_title',
		'_yoast_wpseo_opengraph-description' => 'og_description',
		'_yoast_wpseo_opengraph-image'       => 'og_image',
		'_yoast_wpseo_twitter-title'         => 'og_title',
		'_yoast_wpseo_twitter-description'   => 'og_description',
		'_yoast_wpseo_twitter-image'         => 'og_image',
	];

	/**
	 * Yoast term-meta field (inside wpseo_taxonomy_meta) → LW SEO field.
	 * NB: term description is 'wpseo_desc' (not 'wpseo_metadesc').
	 */
	public const TERM_META_MAP = [
		'wpseo_title'                 => 'title',
		'wpseo_desc'                  => 'description',
		'wpseo_canonical'             => 'canonical',
		'wpseo_opengraph-title'       => 'og_title',
		'wpseo_opengraph-description' => 'og_description',
		'wpseo_opengraph-image'       => 'og_image',
		'wpseo_twitter-title'         => 'og_title',
		'wpseo_twitter-description'   => 'og_description',
		'wpseo_twitter-image'         => 'og_image',
	];

	/**
	 * Yoast primary-term meta key → LW SEO meta field (no prefix).
	 */
	public const PRIMARY_TERM_MAP = [
		'_yoast_wpseo_primary_category'    => 'primary_category',
		'_yoast_wpseo_primary_product_cat' => 'primary_product_cat',
	];

	/**
	 * Yoast meta keys with no LW SEO equivalent (counted + reported).
	 */
	public const NON_MIGRATABLE_META = [
		'_yoast_wpseo_focuskw',
		'_yoast_wpseo_bctitle',
		'_yoast_wpseo_meta-robots-adv',
		'_yoast_wpseo_schema_page_type',
		'_yoast_wpseo_schema_article_type',
	];

	/**
	 * Yoast wpseo_titles option key → LW SEO option key.
	 *
	 * Robots/noindex handled separately (bool cast). Placeholders like
	 * title-<posttype> are resolved dynamically in OptionsMigrator.
	 */
	public const TITLE_OPTIONS_MAP = [
		'separator'           => 'separator',
		'title-home-wpseo'    => 'title_home',
		'metadesc-home-wpseo' => 'desc_home',
		'title-post'          => 'title_post',
		'title-page'          => 'title_page',
		'title-product'       => 'title_product',
		'title-tax-category'  => 'title_category',
		'title-tax-post_tag'  => 'title_post_tag',
		'title-author-wpseo'  => 'title_author',
		'title-archive-wpseo' => 'title_date',
		'title-search-wpseo'  => 'title_search',
		'title-404-wpseo'     => 'title_404',
	];

	/**
	 * Yoast wpseo_titles noindex key → LW SEO noindex option key (bool).
	 */
	public const NOINDEX_OPTIONS_MAP = [
		'noindex-post'          => 'noindex_post',
		'noindex-page'          => 'noindex_page',
		'noindex-product'       => 'noindex_product',
		'noindex-tax-category'  => 'noindex_category',
		'noindex-tax-post_tag'  => 'noindex_post_tag',
		'noindex-author-wpseo'  => 'noindex_author',
		'noindex-archive-wpseo' => 'noindex_date',
	];

	/**
	 * Yoast wpseo_social option key → LW SEO option key.
	 */
	public const SOCIAL_OPTIONS_MAP = [
		'facebook_site'     => 'social_facebook',
		'twitter_site'      => 'social_twitter',
		'instagram_url'     => 'social_instagram',
		'linkedin_url'      => 'social_linkedin',
		'youtube_url'       => 'social_youtube',
		'og_default_image'  => 'default_og_image',
		'twitter_card_type' => 'twitter_card_type',
	];

	/**
	 * Yoast %%variable%% name → LW SEO variable name. Unlisted names pass
	 * through unchanged; names mapped to '' are stripped (no LW equivalent).
	 */
	public const VARIABLE_NAME_MAP = [
		'name'             => 'author',
		'primary_category' => 'category',
		'pt_single'        => '',
		'pt_plural'        => '',
		'focuskw'          => '',
		'sitedesc'         => 'sitedesc',
	];

	/**
	 * Yoast separator token → literal character (LW SEO supported set only).
	 */
	public const SEPARATOR_TOKEN_MAP = [
		'sc-dash'   => '-',
		'sc-mdash'  => '—',
		'sc-middot' => '·',
		'sc-pipe'   => '|',
		'sc-raquo'  => '»',
		'sc-lt'     => '>',
	];
}
