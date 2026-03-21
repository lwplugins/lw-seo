<?php
/**
 * Markdown Dispatcher.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

/**
 * Selects the appropriate renderer and assembles markdown output.
 */
final class Dispatcher {

	/**
	 * Build markdown response for the current queried object.
	 *
	 * @param \WP_Post|\WP_Term $object Queried object.
	 * @return string|null Full markdown output or null if unsupported.
	 */
	public static function dispatch( \WP_Post|\WP_Term $object ): ?string {
		$renderer = self::get_renderer( $object );
		if ( null === $renderer ) {
			return null;
		}

		$frontmatter = $renderer->frontmatter();
		$body        = $renderer->body();

		$output = self::build_yaml( $frontmatter ) . "\n" . $body;

		/**
		 * Filter the complete markdown output.
		 *
		 * @param string            $output Full markdown output.
		 * @param \WP_Post|\WP_Term $object Queried object.
		 */
		return apply_filters( 'lw_seo_markdown_output', $output, $object );
	}

	/**
	 * Get the appropriate renderer for an object.
	 *
	 * @param \WP_Post|\WP_Term $object Queried object.
	 * @return RendererInterface|null
	 */
	private static function get_renderer( \WP_Post|\WP_Term $object ): ?RendererInterface {
		if ( $object instanceof \WP_Term ) {
			return new TaxonomyRenderer( $object );
		}

		if ( 'product' === $object->post_type && class_exists( 'WooCommerce' ) ) {
			return new ProductRenderer( $object );
		}

		return new PostRenderer( $object );
	}

	/**
	 * Build YAML frontmatter string from array.
	 *
	 * @param array<string, mixed> $data Key-value pairs.
	 * @return string YAML frontmatter block.
	 */
	private static function build_yaml( array $data ): string {
		$lines = [ '---' ];

		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$escaped = array_map(
					fn( $item ) => '"' . addslashes( (string) $item ) . '"',
					$value
				);
				$lines[] = $key . ': [' . implode( ', ', $escaped ) . ']';
			} elseif ( is_bool( $value ) ) {
				$lines[] = $key . ': ' . ( $value ? 'true' : 'false' );
			} elseif ( is_int( $value ) ) {
				$lines[] = $key . ': ' . $value;
			} elseif ( null === $value ) {
				$lines[] = $key . ': null';
			} else {
				$lines[] = $key . ': "' . addslashes( (string) $value ) . '"';
			}
		}

		$lines[] = '---';
		return implode( "\n", $lines ) . "\n";
	}
}
