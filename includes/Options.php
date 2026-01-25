<?php
/**
 * Options management class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Handles plugin options and settings.
 */
final class Options {

	/**
	 * Option name in database.
	 */
	public const OPTION_NAME = 'lw_seo_options';

	/**
	 * Meta key prefix for post meta.
	 */
	public const META_PREFIX = '_lw_seo_';

	/**
	 * Cached options.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $options = null;

	/**
	 * Get default options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return [
			// General.
			'separator'             => '-',
			'title_home'            => '%%sitename%% %%sep%% %%sitedesc%%',
			'desc_home'             => '',

			// Content types defaults.
			'title_post'            => '%%title%% %%sep%% %%sitename%%',
			'title_page'            => '%%title%% %%sep%% %%sitename%%',
			'noindex_post'          => false,
			'noindex_page'          => false,

			// Taxonomies.
			'title_category'        => '%%term_title%% %%sep%% %%sitename%%',
			'title_post_tag'        => '%%term_title%% %%sep%% %%sitename%%',
			'noindex_category'      => false,
			'noindex_post_tag'      => false,

			// Archives.
			'title_author'          => '%%author%% %%sep%% %%sitename%%',
			'title_date'            => '%%currentdate%% %%sep%% %%sitename%%',
			'title_search'          => 'Search: %%searchphrase%% %%sep%% %%sitename%%',
			'title_404'             => 'Page not found %%sep%% %%sitename%%',
			'noindex_author'        => false,
			'noindex_date'          => true,

			// Social.
			'opengraph_enabled'     => true,
			'twitter_enabled'       => true,
			'twitter_card_type'     => 'summary_large_image',
			'default_og_image'      => '',
			'social_facebook'       => '',
			'social_twitter'        => '',
			'social_instagram'      => '',
			'social_linkedin'       => '',
			'social_youtube'        => '',

			// Organization/Person.
			'knowledge_type'        => 'organization',
			'knowledge_name'        => '',
			'knowledge_logo'        => '',

			// Sitemap.
			'sitemap_enabled'       => true,
			'sitemap_posts'         => true,
			'sitemap_pages'         => true,
			'sitemap_categories'    => true,
			'sitemap_tags'          => false,

			// Advanced.
			'breadcrumbs_enabled'   => true,
			'schema_enabled'        => true,
			'robots_txt_enabled'    => true,
			'remove_shortlinks'     => true,
			'remove_rsd'            => true,
			'remove_wlw'            => true,

			// AI/LLM Crawlers.
			'llms_txt_enabled'      => true,
			'block_gptbot'          => false,
			'block_chatgpt_user'    => false,
			'block_claude_web'      => false,
			'block_google_extended' => false,
			'block_bytespider'      => false,
			'block_ccbot'           => false,
			'block_perplexitybot'   => false,
			'block_cohere_ai'       => false,

			// WooCommerce.
			'woo_enabled'           => true,
			'title_product'         => '%%title%% %%sep%% %%sitename%%',
			'noindex_product'       => false,
			'woo_schema_enabled'    => true,
			'woo_schema_reviews'    => true,
			'sitemap_products'      => true,
			'sitemap_product_cat'   => true,
			'sitemap_product_tag'   => false,

			// Local SEO.
			'local_enabled'         => false,
			'local_business_type'   => 'LocalBusiness',
			'local_business_name'   => '',
			'local_description'     => '',
			'local_price_range'     => '',
			'local_street'          => '',
			'local_street_2'        => '',
			'local_city'            => '',
			'local_state'           => '',
			'local_zip'             => '',
			'local_country'         => '',
			'local_phone'           => '',
			'local_email'           => '',
			'local_lat'             => '',
			'local_lng'             => '',
			'local_hours_enabled'   => false,

			// Redirects.
			'redirects_enabled'     => true,

			// 404.
			'redirect_404_to_home'  => false,
		];
	}

	/**
	 * Get all options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		if ( null === self::$options ) {
			$saved         = get_option( self::OPTION_NAME, [] );
			self::$options = wp_parse_args( $saved, self::get_defaults() );
		}

		return self::$options;
	}

	/**
	 * Get a single option.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value if not set.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$options = self::get_all();

		if ( array_key_exists( $key, $options ) ) {
			return $options[ $key ];
		}

		return $default ?? ( self::get_defaults()[ $key ] ?? null );
	}

	/**
	 * Set a single option.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 * @return bool
	 */
	public static function set( string $key, mixed $value ): bool {
		$options         = self::get_all();
		$options[ $key ] = $value;

		return self::save( $options );
	}

	/**
	 * Save all options.
	 *
	 * @param array<string, mixed> $options Options to save.
	 * @return bool
	 */
	public static function save( array $options ): bool {
		self::$options = $options;
		return update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Reset options to defaults.
	 *
	 * @return bool
	 */
	public static function reset(): bool {
		self::$options = null;
		return delete_option( self::OPTION_NAME );
	}

	/**
	 * Get post meta value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key (without prefix).
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_post_meta( int $post_id, string $key, mixed $default = '' ): mixed {
		$value = get_post_meta( $post_id, self::META_PREFIX . $key, true );

		return '' !== $value ? $value : $default;
	}

	/**
	 * Set post meta value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key (without prefix).
	 * @param mixed  $value   Value to save.
	 * @return bool
	 */
	public static function set_post_meta( int $post_id, string $key, mixed $value ): bool {
		if ( '' === $value || null === $value ) {
			return delete_post_meta( $post_id, self::META_PREFIX . $key );
		}

		return (bool) update_post_meta( $post_id, self::META_PREFIX . $key, $value );
	}

	/**
	 * Get available separators.
	 *
	 * @return array<string, string>
	 */
	public static function get_separators(): array {
		return [
			'-' => '-',
			'|' => '|',
			'>' => '>',
			'»' => '»',
			'·' => '·',
			'—' => '—',
			'/' => '/',
		];
	}

	/**
	 * Clear options cache.
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		self::$options = null;
	}
}
