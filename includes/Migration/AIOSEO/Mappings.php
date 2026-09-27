<?php
/**
 * All in One SEO → LW SEO migration mapping constants.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\AIOSEO;

/**
 * Mapping constants for the All in One SEO importer.
 *
 * Verified against All in One SEO 5.0.2 (free):
 *   - per-post data lives in {prefix}aioseo_posts (app/Common/Db/Schema.php:114-184);
 *     the free plugin creates no aioseo_terms or aioseo_redirects table (Schema.php:31-43)
 *   - smart tags are "#tag" matched as /#tag(?![a-zA-Z0-9_])/i (app/Common/Utils/Tags.php:951-958),
 *     tag table at Tags.php:404-716
 *   - settings are JSON in aioseo_options / aioseo_options_dynamic, full tree with plain
 *     leaf values (app/Common/Traits/Options.php:748, 968-1002)
 */
final class Mappings {

	/**
	 * Smart tag (without "#") → LW SEO variable name; '' = no LW SEO equivalent.
	 * Every tag All in One SEO registers is listed, so a "#word" that is not a
	 * tag stays literal text.
	 */
	public const TAG_MAP = [
		'site_title'             => 'sitename',
		'blog_title'             => 'sitename',
		'tagline'                => 'sitedesc',
		'site_description'       => 'sitedesc',
		'separator_sa'           => 'sep',
		'post_title'             => 'title',
		'archive_title'          => 'title',
		'post_excerpt'           => 'excerpt',
		'post_excerpt_only'      => 'excerpt',
		'post_date'              => 'date',
		'post_modified_date'     => 'modified',
		'author_name'            => 'author',
		'categories'             => 'category',
		'category'               => 'category',
		'taxonomy_title'         => 'term_title',
		'taxonomy_description'   => 'term_description',
		'search_term'            => 'searchphrase',
		'page_number'            => 'page',
		'current_year'           => 'currentyear',
		'current_month'          => 'currentmonth',
		'current_day'            => 'currentday',
		'current_date'           => 'currentdate',
		'archive_date'           => 'currentdate',
		'alt_tag'                => '',
		'attachment_caption'     => '',
		'attachment_description' => '',
		'author_link'            => '',
		'author_link_alt'        => '',
		'author_bio'             => '',
		'author_first_name'      => '',
		'author_last_name'       => '',
		'author_url'             => '',
		'blog_link'              => '',
		'category_link'          => '',
		'category_link_alt'      => '',
		'description'            => '',
		'featured_image'         => '',
		'featured_image_url'     => '',
		'parent_title'           => '',
		'permalink'              => '',
		'post_content'           => '',
		'post_date_w3c'          => '',
		'post_modified_date_w3c' => '',
		'post_day'               => '',
		'post_month'             => '',
		'post_year'              => '',
		'post_link'              => '',
		'post_link_alt'          => '',
		'site_link'              => '',
		'site_link_alt'          => '',
		'tax_parent_name'        => '',
		'event_start_date'       => '',
		'event_end_date'         => '',
	];

	/**
	 * Tags that take a "-<key>" suffix (#custom_field-price, #tax_name-genre).
	 */
	public const SUFFIX_TAGS = [ 'custom_field', 'tax_name' ];

	/**
	 * Post types whose title template and noindex setting LW SEO has options for.
	 */
	public const POST_TYPES = [ 'post', 'page', 'product' ];

	/**
	 * Taxonomies whose title template and noindex setting LW SEO has options for.
	 */
	public const TAXONOMIES = [ 'category', 'post_tag' ];

	/**
	 * Archive (searchAppearance.archives.*) → LW SEO title / noindex key suffix.
	 * Search archives have no LW SEO noindex option (always noindex).
	 */
	public const ARCHIVES = [
		'author' => 'author',
		'date'   => 'date',
		'search' => 'search',
	];

	/**
	 * Profile URL settings (social.profiles.urls.*) → LW SEO option.
	 */
	public const PROFILE_MAP = [
		'facebookPageUrl' => 'social_facebook',
		'twitterUrl'      => 'social_twitter',
		'instagramUrl'    => 'social_instagram',
		'linkedinUrl'     => 'social_linkedin',
		'youtubeUrl'      => 'social_youtube',
	];

	/**
	 * Taxonomies of primary_term entries LW SEO stores (as _lw_seo_primary_{taxonomy}).
	 */
	public const PRIMARY_TAXONOMIES = [ 'category', 'product_cat', 'product_brand' ];

	/**
	 * The aioseo_posts robots columns LW SEO has no per-post setting for.
	 */
	public const UNSUPPORTED_ROBOTS = [
		'robots_noarchive',
		'robots_nosnippet',
		'robots_noimageindex',
		'robots_noodp',
		'robots_notranslate',
	];

	/**
	 * Pro-only tables (not created by the free plugin); reported, not imported.
	 */
	public const PRO_TABLES = [ 'aioseo_terms', 'aioseo_redirects' ];
}
