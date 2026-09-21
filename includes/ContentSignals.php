<?php
/**
 * Content Signals class.
 *
 * Outputs AI content signal HTTP headers and meta tags.
 *
 * @package LightweightPlugins\SEO
 * @see https://blog.cloudflare.com/content-signals-policy/
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Handles Content Signals HTTP headers and meta tags.
 */
final class ContentSignals {

	/**
	 * Signal => post/term meta key.
	 */
	private const META_KEYS = [
		'search'   => 'search',
		'ai-input' => 'ai_input',
		'ai-train' => 'ai_train',
	];

	/**
	 * Pre-1.6.0 header name, still sent alongside Content-Signal.
	 */
	private const LEGACY_HEADER = 'X-Content-Signals';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'wp_headers', [ $this, 'add_signal_headers' ] );
		add_action( 'wp_head', [ $this, 'output_meta_tag' ], 2 );
	}

	/**
	 * Site-wide signals that are set.
	 *
	 * @return array<string, string>
	 */
	public static function global_signals(): array {
		$signals = [];

		foreach ( SignalValue::KEYS as $signal => $option ) {
			$value = SignalValue::sanitize( Options::get( $option ) );
			if ( '' !== $value ) {
				$signals[ $signal ] = $value;
			}
		}

		return $signals;
	}

	/**
	 * Signals for an object: global values overridden per post/term.
	 *
	 * @param \WP_Post|\WP_Term|null $object Optional object to resolve for.
	 * @return array<string, string> Only signals that are set.
	 */
	public static function resolve( \WP_Post|\WP_Term|null $object = null ): array {
		$signals = self::global_signals();

		foreach ( self::META_KEYS as $signal => $meta_key ) {
			$override = '';
			if ( $object instanceof \WP_Post ) {
				$override = SignalValue::sanitize( Options::get_post_meta( (int) $object->ID, $meta_key ) );
			} elseif ( $object instanceof \WP_Term ) {
				$override = SignalValue::sanitize( Options::get_term_meta( (int) $object->term_id, $meta_key ) );
			}

			if ( '' !== $override ) {
				$signals[ $signal ] = $override;
			}
		}

		/**
		 * Filter Content Signals values.
		 *
		 * @param array<string, string>  $signals Signal key-value pairs.
		 * @param \WP_Post|\WP_Term|null $object  Current object.
		 */
		return (array) apply_filters( 'lw_seo_content_signals', $signals, $object );
	}

	/**
	 * Header / directive value in canonical order.
	 *
	 * @param array<string, string> $signals Signals.
	 * @return string '' when no signal is set.
	 */
	public static function format_header( array $signals ): string {
		$parts = [];

		foreach ( array_keys( SignalValue::KEYS ) as $key ) {
			if ( isset( $signals[ $key ] ) && '' !== $signals[ $key ] ) {
				$parts[] = $key . '=' . $signals[ $key ];
			}
		}

		return implode( ', ', $parts );
	}

	/**
	 * HTTP headers for the given signals.
	 *
	 * @param array<string, string> $signals Signals.
	 * @return array<string, string> Header name => value.
	 */
	public static function headers( array $signals ): array {
		$value = self::format_header( $signals );

		if ( '' === $value ) {
			return [];
		}

		return [
			'Content-Signal'    => $value,
			self::LEGACY_HEADER => $value,
		];
	}

	/**
	 * Add the headers to front-end responses.
	 *
	 * @param array<string, string> $headers WordPress headers.
	 * @return array<string, string>
	 */
	public function add_signal_headers( array $headers ): array {
		return array_merge( $headers, self::headers( self::resolve( self::current_object() ) ) );
	}

	/**
	 * Output the meta tag.
	 *
	 * @return void
	 */
	public function output_meta_tag(): void {
		$value = self::format_header( self::resolve( self::current_object() ) );

		if ( '' === $value ) {
			return;
		}

		printf( '<meta name="ai-content-signals" content="%s" />' . "\n", esc_attr( $value ) );
	}

	/**
	 * Queried post or term, if any.
	 *
	 * @return \WP_Post|\WP_Term|null
	 */
	private static function current_object(): \WP_Post|\WP_Term|null {
		if ( ! is_singular() && ! is_category() && ! is_tag() && ! is_tax() ) {
			return null;
		}

		$object = get_queried_object();

		return $object instanceof \WP_Post || $object instanceof \WP_Term ? $object : null;
	}
}
