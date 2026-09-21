<?php
/**
 * Markdown / llms.txt discovery links.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

use LightweightPlugins\SEO\Content\Eligibility;
use LightweightPlugins\SEO\Options;

/**
 * Advertises a page's Markdown version (rel="alternate") and the covering
 * llms.txt (rel="describedby") as <link> elements and a Link header,
 * per llms.txt v2.
 */
final class Discovery {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', [ $this, 'print_links' ], 3 );
		add_filter( 'wp_headers', [ $this, 'add_link_header' ] );
	}

	/**
	 * Print <link> elements.
	 *
	 * @return void
	 */
	public function print_links(): void {
		$markdown = $this->markdown_url();
		if ( '' !== $markdown ) {
			printf( '<link rel="alternate" type="text/markdown" href="%s" />' . "\n", esc_url( $markdown ) );
		}

		$llms = self::llms_url();
		if ( '' !== $llms ) {
			printf( '<link rel="describedby" href="%s" />' . "\n", esc_url( $llms ) );
		}
	}

	/**
	 * Add the Link header.
	 *
	 * @param array<string, string> $headers Response headers.
	 * @return array<string, string>
	 */
	public function add_link_header( array $headers ): array {
		$value = self::link_header( $this->markdown_url(), self::llms_url() );

		if ( '' !== $value ) {
			$headers['Link'] = isset( $headers['Link'] ) ? $headers['Link'] . ', ' . $value : $value;
		}

		return $headers;
	}

	/**
	 * Link header value.
	 *
	 * @param string $markdown_url Markdown URL ('' = none).
	 * @param string $llms_url     llms.txt URL ('' = none).
	 * @return string
	 */
	public static function link_header( string $markdown_url, string $llms_url ): string {
		$parts = [];

		if ( '' !== $markdown_url ) {
			$parts[] = '<' . $markdown_url . '>; rel="alternate"; type="text/markdown"';
		}

		if ( '' !== $llms_url ) {
			$parts[] = '<' . $llms_url . '>; rel="describedby"';
		}

		return implode( ', ', $parts );
	}

	/**
	 * Markdown URL of the current singular page, if it is served.
	 *
	 * @return string
	 */
	private function markdown_url(): string {
		if ( ! is_singular() ) {
			return '';
		}

		$post = get_queried_object();

		return $post instanceof \WP_Post && Eligibility::is_ai_visible( $post ) ? Url::for_post( $post ) : '';
	}

	/**
	 * LLMS.txt URL when enabled.
	 *
	 * @return string
	 */
	private static function llms_url(): string {
		return Options::get( 'llms_txt_enabled' ) ? home_url( '/llms.txt' ) : '';
	}
}
