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
 * LW SEO shows a post's or term's own SEO title, description and social text
 * exactly as saved: variables are only replaced in the global templates. Other
 * plugins allow variables in those per-object fields ("%%post_title%% - Shop"),
 * so the importers fill them in with the object's values at import time,
 * after converting them to LW SEO variables. The home meta description is
 * shown as saved too.
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
	 * Resolve the variables of a site-wide text LW SEO shows as saved (the
	 * home meta description), with the separator the import is setting.
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
