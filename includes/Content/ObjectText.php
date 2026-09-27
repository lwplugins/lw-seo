<?php
/**
 * Per-object SEO text as it is displayed.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Content;

use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\ReplaceVars;

/**
 * Reads a post's or term's own SEO text (title, description, social title
 * and description) and the home meta description, filling in the LW SEO
 * variables they contain with that object's values, the same way the
 * global templates are filled in. "Custom %%title%% %%sep%% %%sitename%%"
 * on a post shows "Custom My post - Site".
 *
 * Text without variables is returned exactly as saved.
 */
final class ObjectText {

	/**
	 * An LW SEO variable, as ReplaceVars matches it.
	 */
	private const VARIABLE = '/%%[a-z_]+%%/';

	/**
	 * A post's own SEO text field, with its variables filled in.
	 *
	 * @param \WP_Post $post  Post object.
	 * @param string   $field LW SEO meta field (title, description, og_title, og_description).
	 * @return string '' when the post has no value.
	 */
	public static function post( \WP_Post $post, string $field ): string {
		return self::resolve( MetaCoerce::as_string( Options::get_post_meta( (int) $post->ID, $field ) ), $post );
	}

	/**
	 * A term's own SEO text field, with its variables filled in.
	 *
	 * @param \WP_Term $term  Term object.
	 * @param string   $field LW SEO meta field (title, description, og_title, og_description).
	 * @return string '' when the term has no value.
	 */
	public static function term( \WP_Term $term, string $field ): string {
		return self::resolve( MetaCoerce::as_string( Options::get_term_meta( (int) $term->term_id, $field ) ), null, $term );
	}

	/**
	 * The home meta description setting, with its variables filled in.
	 *
	 * @return string '' when it is not set.
	 */
	public static function home_description(): string {
		return self::resolve( MetaCoerce::as_string( Options::get( 'desc_home' ) ) );
	}

	/**
	 * Fill in the LW SEO variables of a text; text without any is returned
	 * unchanged (no whitespace clean-up either).
	 *
	 * @param string        $text Text as saved.
	 * @param \WP_Post|null $post Post context.
	 * @param \WP_Term|null $term Term context.
	 * @return string
	 */
	public static function resolve( string $text, ?\WP_Post $post = null, ?\WP_Term $term = null ): string {
		if ( ! str_contains( $text, '%%' ) || 1 !== preg_match( self::VARIABLE, $text ) ) {
			return $text;
		}

		return ReplaceVars::replace( $text, $post, $term );
	}
}
