<?php
/**
 * Post descriptions that respect content restriction.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Content;

use LightweightPlugins\SEO\Options;

/**
 * Builds the description LW SEO prints for a post (meta, Open Graph,
 * Twitter, schema, Markdown, llms.txt).
 *
 * Generated text comes from get_the_excerpt(), never from the raw
 * post_content, so membership and paywall plugins that mask the excerpt
 * mask these outputs too. A password-protected post gets no generated
 * text. Every description, including one the editor typed, runs through
 * the lw_seo_meta_description filter as the last step.
 */
final class PostDescription {

	/**
	 * Public filter name.
	 */
	public const FILTER = 'lw_seo_meta_description';

	/**
	 * Word limit of a generated description.
	 */
	public const WORDS = 30;

	/**
	 * The head descriptions of a post: meta, og:description and
	 * twitter:description, each filtered with its own context.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array{meta: string, og: string, twitter: string}
	 */
	public static function for_head( \WP_Post $post ): array {
		$source    = self::source( $post, (string) Options::get_post_meta( (int) $post->ID, 'description' ) );
		$custom_og = wp_strip_all_tags( (string) Options::get_post_meta( (int) $post->ID, 'og_description' ) );
		$og_source = '' !== trim( $custom_og ) ? $custom_og : $source;

		return [
			'meta'    => self::filter( $source, $post, 'meta' ),
			'og'      => self::filter( $og_source, $post, 'og' ),
			'twitter' => self::filter( $og_source, $post, 'twitter' ),
		];
	}

	/**
	 * The unfiltered description: the editor's text, or a generated one.
	 *
	 * @param \WP_Post $post   Post object.
	 * @param string   $custom Description typed by the editor ('' = generate).
	 * @param int      $words  Word limit of a generated description.
	 * @return string Plain text.
	 */
	public static function source( \WP_Post $post, string $custom = '', int $words = self::WORDS ): string {
		$custom = wp_strip_all_tags( $custom );

		return '' !== trim( $custom ) ? $custom : self::generated( $post, $words );
	}

	/**
	 * Run a description through the public filter.
	 *
	 * @param string   $description Description.
	 * @param \WP_Post $post        Post object.
	 * @param string   $context     'meta'|'og'|'twitter'|'schema'|'markdown'|'llms'.
	 * @return string
	 */
	public static function filter( string $description, \WP_Post $post, string $context ): string {
		/**
		 * Filter a post description before LW SEO outputs it.
		 *
		 * Runs for generated descriptions and for the ones the editor
		 * typed, so a content restriction plugin can replace or empty it.
		 *
		 * @param string   $description Description (plain text, except 'llms').
		 * @param \WP_Post $post        The post.
		 * @param string   $context     'meta', 'og', 'twitter', 'schema', 'markdown' or 'llms'.
		 */
		return (string) apply_filters( 'lw_seo_meta_description', $description, $post, $context );
	}

	/**
	 * A description generated through get_the_excerpt(): the manual
	 * excerpt as is, otherwise the automatic excerpt cut to $words words.
	 *
	 * @param \WP_Post $post  Post object.
	 * @param int      $words Word limit of the automatic excerpt.
	 * @return string Plain text; '' for a password-protected post.
	 */
	public static function generated( \WP_Post $post, int $words = self::WORDS ): string {
		if ( post_password_required( $post ) ) {
			return '';
		}

		$text = html_entity_decode( wp_strip_all_tags( (string) get_the_excerpt( $post ) ), ENT_QUOTES, 'UTF-8' );

		return '' !== trim( (string) $post->post_excerpt ) ? $text : wp_trim_words( $text, $words, '...' );
	}

	/**
	 * The manual excerpt through the get_the_excerpt filters, never an
	 * automatic one built from the content.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Excerpt as filtered; '' when there is none or the post is password protected.
	 */
	public static function manual_excerpt( \WP_Post $post ): string {
		if ( '' === trim( (string) $post->post_excerpt ) || post_password_required( $post ) ) {
			return '';
		}

		return (string) get_the_excerpt( $post );
	}
}
