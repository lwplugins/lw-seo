<?php
/**
 * Fills in variables of imported per-object text.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Support;

use LightweightPlugins\SEO\ReplaceVars;

/**
 * Other plugins allow variables in a post's or term's own SEO fields
 * ("%%post_title%% - Shop"). Since 1.8.0 LW SEO fills in the variables of
 * those fields (and of the home meta description) when it displays them
 * (Content\ObjectText), but the SEOPress and All in One SEO importers still
 * fill them in at import time, after converting them to LW SEO variables.
 * Deliberately: the stored text is then final wherever raw meta is read
 * (the editor fields, the Site Manager abilities, third-party code), and
 * text without variables passes the display-time step unchanged, so nothing
 * is replaced twice. The trade-off is that an imported title does not follow
 * a later change of the post title; the editor can type the variable again.
 */
final class TextResolver {

	/**
	 * LW SEO fields that hold text (the others are URLs and flags).
	 */
	private const TEXT_FIELDS = [ 'title', 'description', 'og_title', 'og_description' ];

	/**
	 * Resolve the variables in the text fields of one object.
	 *
	 * @param array<string, string> $fields LW field => value (LW SEO variables).
	 * @param \WP_Post|null         $post   Post the fields belong to.
	 * @param \WP_Term|null         $term   Term the fields belong to.
	 * @return array<string, string>
	 */
	public function resolve( array $fields, ?\WP_Post $post = null, ?\WP_Term $term = null ): array {
		foreach ( self::TEXT_FIELDS as $field ) {
			if ( isset( $fields[ $field ] ) && str_contains( $fields[ $field ], '%%' ) ) {
				$fields[ $field ] = ReplaceVars::replace( $fields[ $field ], $post, $term );
			}
		}

		return $fields;
	}

	/**
	 * Resolve the variables of a site-wide text (the home meta description),
	 * with the separator the import is setting.
	 *
	 * @param string|null $text      Text with LW SEO variables.
	 * @param string      $separator Separator to use for %%sep%%.
	 * @return string|null Null when there is no text.
	 */
	public function resolve_site_text( ?string $text, string $separator ): ?string {
		if ( null === $text || ! str_contains( $text, '%%' ) ) {
			return $text;
		}

		$resolved = ReplaceVars::replace( str_replace( '%%sep%%', $separator, $text ) );

		return '' === $resolved ? null : $resolved;
	}
}
