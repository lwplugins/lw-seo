<?php
/**
 * SEOPress → LW SEO migration mapping constants.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

/**
 * Mapping constants for the SEOPress importer.
 *
 * Verified against SEOPress 10.2 (free) and SEOPress PRO 10.2.1:
 *   - post and term meta share the `_seopress_*` keys
 *     (inc/admin/metaboxes/admin-metaboxes.php:375-565, admin-term-metaboxes.php:196-352)
 *   - robots flags store 'yes' for the exclusion: `_seopress_robots_index` = 'yes'
 *     means noindex (inc/functions/options-titles-metas.php:783-804)
 *   - template variables: inc/functions/variables/dynamic-variables.php:246-363
 */
final class Mappings {

	/**
	 * Meta key → LW SEO field, in priority order. Twitter keys are fallbacks for
	 * the og_* targets (LW SEO renders Twitter Cards from the Open Graph values).
	 */
	public const META_MAP = [
		'_seopress_titles_title'         => 'title',
		'_seopress_titles_desc'          => 'description',
		'_seopress_robots_canonical'     => 'canonical',
		'_seopress_social_fb_title'      => 'og_title',
		'_seopress_social_fb_desc'       => 'og_description',
		'_seopress_social_fb_img'        => 'og_image',
		'_seopress_social_twitter_title' => 'og_title',
		'_seopress_social_twitter_desc'  => 'og_description',
		'_seopress_social_twitter_img'   => 'og_image',
	];

	/**
	 * LW SEO fields that hold templates (variables converted).
	 */
	public const TEMPLATE_FIELDS = [ 'title', 'description', 'og_title', 'og_description' ];

	/**
	 * LW SEO term fields (terms have no canonical or nofollow in LW SEO).
	 */
	public const TERM_FIELDS = [ 'title', 'description', 'og_title', 'og_description', 'og_image', 'noindex' ];

	/**
	 * Meta keys with no LW SEO equivalent (counted and reported).
	 */
	public const NON_MIGRATABLE_META = [
		'_seopress_analysis_target_kw',
		'_seopress_robots_snippet',
		'_seopress_robots_imageindex',
		'_seopress_robots_breadcrumbs',
	];

	/**
	 * SEOPress variable name → LW SEO variable name; '' = no LW SEO equivalent.
	 * Names not listed here (dynamic %%_cf_*%%, %%_ct_*%%, WooCommerce, author
	 * details…) are removed and reported too.
	 */
	public const VARIABLE_MAP = [
		'sep'                   => 'sep',
		'sitetitle'             => 'sitename',
		'sitename'              => 'sitename',
		'tagline'               => 'sitedesc',
		'sitedesc'              => 'sitedesc',
		'post_title'            => 'title',
		'title'                 => 'title',
		'cpt_plural'            => 'title',
		'post_excerpt'          => 'excerpt',
		'excerpt'               => 'excerpt',
		'wc_single_short_desc'  => 'excerpt',
		'post_date'             => 'date',
		'date'                  => 'date',
		'post_modified_date'    => 'modified',
		'post_author'           => 'author',
		'post_category'         => 'category',
		'post_tag'              => 'tag',
		'_category_title'       => 'term_title',
		'_category_description' => 'term_description',
		'tag_title'             => 'term_title',
		'tag_description'       => 'term_description',
		'term_title'            => 'term_title',
		'term_description'      => 'term_description',
		'search_keywords'       => 'searchphrase',
		'current_pagination'    => 'pagenumber',
		'page'                  => 'pagenumber',
		'currentday'            => 'currentday',
		'currentmonth'          => 'currentmonth',
		'currentyear'           => 'currentyear',
		'currentdate'           => 'currentdate',
		'archive_date'          => 'currentdate',
	];

	/**
	 * Post types whose title template and noindex LW SEO has options for.
	 */
	public const POST_TYPES = [ 'post', 'page', 'product' ];

	/**
	 * Taxonomies whose title template and noindex LW SEO has options for.
	 */
	public const TAXONOMIES = [ 'category', 'post_tag' ];

	/**
	 * Archive keys of seopress_titles_option_name → LW SEO option.
	 */
	public const ARCHIVE_OPTIONS = [
		'seopress_titles_archives_author_title'   => 'title_author',
		'seopress_titles_archives_date_title'     => 'title_date',
		'seopress_titles_archives_search_title'   => 'title_search',
		'seopress_titles_archives_404_title'      => 'title_404',
		'seopress_titles_archives_author_noindex' => 'noindex_author',
		'seopress_titles_archives_date_noindex'   => 'noindex_date',
	];

	/**
	 * Profile keys of seopress_social_option_name → LW SEO option (URLs).
	 */
	public const PROFILE_MAP = [
		'seopress_social_accounts_facebook'  => 'social_facebook',
		'seopress_social_accounts_instagram' => 'social_instagram',
		'seopress_social_accounts_linkedin'  => 'social_linkedin',
		'seopress_social_accounts_youtube'   => 'social_youtube',
	];

	/**
	 * Redirect types LW SEO supports.
	 */
	public const REDIRECT_TYPES = [ 301, 302, 307, 410, 451 ];

	/**
	 * Post meta prefixes that are caches, not user data (never inspected).
	 */
	public const CACHE_META_PREFIXES = [ '_seopress_analysis_data', '_seopress_content_analysis', '_seopress_404' ];
}
