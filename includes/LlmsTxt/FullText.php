<?php
/**
 * LLMS-full.txt assembly.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\LlmsTxt;

use LightweightPlugins\SEO\Markdown\Dispatcher;

/**
 * Concatenates the Markdown of every listed post, capped in size.
 */
final class FullText {

	/**
	 * Size cap in bytes (1 MiB).
	 */
	public const MAX_BYTES = 1048576;

	/**
	 * Appended when the cap stops the output.
	 */
	public const TRUNCATED = "\n\n> Truncated at the size limit. The full page list is in /llms.txt.\n";

	/**
	 * Assemble the document, consuming chunks only until the cap.
	 *
	 * @param string   $title     Site name.
	 * @param iterable $chunks    Rendered string chunks (a generator renders lazily).
	 * @param int      $max_bytes Size cap.
	 * @return string
	 */
	public static function assemble( string $title, iterable $chunks, int $max_bytes = self::MAX_BYTES ): string {
		$output = '# ' . Document::text( $title ) . "\n";

		foreach ( $chunks as $chunk ) {
			$piece = "\n" . $chunk;
			if ( strlen( $output ) + strlen( $piece ) > $max_bytes ) {
				return $output . self::TRUNCATED;
			}
			$output .= $piece;
		}

		return $output;
	}

	/**
	 * One post as a Markdown chunk.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	public static function chunk( \WP_Post $post ): string {
		// the_content filters (shortcodes, blocks) read the global post.
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored by wp_reset_postdata() below.
		setup_postdata( $post );

		$body = Dispatcher::body( $post );

		wp_reset_postdata();

		return "---\n\nURL: " . get_permalink( $post ) . "\n\n" . trim( $body ) . "\n";
	}
}
