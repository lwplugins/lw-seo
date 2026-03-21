<?php
/**
 * Content Signals class.
 *
 * Outputs AI content signal HTTP headers and meta tags.
 *
 * @package LightweightPlugins\SEO
 * @see https://blog.cloudflare.com/markdown-for-agents/
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Handles Content Signals HTTP headers and meta tags.
 */
final class ContentSignals {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'wp_headers', [ $this, 'add_signal_headers' ] );
		add_action( 'wp_head', [ $this, 'output_meta_tag' ], 2 );
	}

	/**
	 * Get resolved signals for the current context.
	 *
	 * @param \WP_Post|\WP_Term|null $object Optional object to resolve for.
	 * @return array<string, string> Signal key-value pairs.
	 */
	public static function resolve( \WP_Post|\WP_Term|null $object = null ): array {
		$signals = [
			'ai-train' => Options::get( 'content_signals_ai_train' ) ? 'yes' : 'no',
			'ai-input' => Options::get( 'content_signals_ai_input' ) ? 'yes' : 'no',
			'search'   => Options::get( 'content_signals_search' ) ? 'yes' : 'no',
		];

		// Per-post override.
		if ( $object instanceof \WP_Post ) {
			$meta_keys = [
				'ai-train' => 'ai_train',
				'ai-input' => 'ai_input',
				'search'   => 'search',
			];

			foreach ( $meta_keys as $signal_key => $meta_key ) {
				$meta_value = Options::get_post_meta( $object->ID, $meta_key );
				if ( '' !== $meta_value && 'default' !== $meta_value ) {
					$signals[ $signal_key ] = $meta_value;
				}
			}
		}

		/**
		 * Filter Content Signals values.
		 *
		 * @param array<string, string>  $signals Signal key-value pairs.
		 * @param \WP_Post|\WP_Term|null $object  Current object.
		 */
		return apply_filters( 'lw_seo_content_signals', $signals, $object );
	}

	/**
	 * Format signals as header string.
	 *
	 * @param array<string, string> $signals Signal array.
	 * @return string Formatted header value.
	 */
	public static function format_header( array $signals ): string {
		$parts = [];
		foreach ( $signals as $key => $value ) {
			$parts[] = $key . '=' . $value;
		}
		return implode( ', ', $parts );
	}

	/**
	 * Add Content Signals HTTP header to all responses.
	 *
	 * @param array<string, string> $headers WordPress headers.
	 * @return array<string, string>
	 */
	public function add_signal_headers( array $headers ): array {
		$object = null;

		if ( is_singular() ) {
			$object = get_queried_object();
			$object = $object instanceof \WP_Post ? $object : null;
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$object = get_queried_object();
			$object = $object instanceof \WP_Term ? $object : null;
		}

		$signals                      = self::resolve( $object );
		$headers['X-Content-Signals'] = self::format_header( $signals );

		return $headers;
	}

	/**
	 * Output Content Signals meta tag in head.
	 *
	 * @return void
	 */
	public function output_meta_tag(): void {
		$object = null;

		if ( is_singular() ) {
			$object = get_queried_object();
			$object = $object instanceof \WP_Post ? $object : null;
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$object = get_queried_object();
			$object = $object instanceof \WP_Term ? $object : null;
		}

		$signals = self::resolve( $object );

		printf(
			'<meta name="ai-content-signals" content="%s" />' . "\n",
			esc_attr( self::format_header( $signals ) )
		);
	}
}
