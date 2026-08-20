<?php
/**
 * Deferred rewrite flush when a virtual endpoint is toggled.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Flushing inside the option-save request would miss the rules of a feature
 * that was just enabled (its `init` hook never ran with the new value), so the
 * flush is deferred to `wp_loaded` of the next request via a transient flag.
 */
final class RewriteFlusher {

	/**
	 * Transient that marks a pending flush.
	 */
	public const FLAG = 'lw_seo_flush_rewrite';

	/**
	 * Option keys whose change adds or removes rewrite rules.
	 */
	private const WATCHED_KEYS = [ 'sitemap_enabled', 'robots_txt_enabled', 'llms_txt_enabled' ];

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'update_option_' . Options::OPTION_NAME, [ $this, 'schedule_on_toggle' ], 10, 2 );
		add_action( 'wp_loaded', [ $this, 'maybe_flush' ] );
	}

	/**
	 * Flag a flush when a rewrite-affecting toggle changed.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 * @return void
	 */
	public function schedule_on_toggle( $old_value, $new_value ): void {
		if ( self::toggled( $old_value, $new_value ) ) {
			set_transient( self::FLAG, 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Flush once all `init` rewrite rules are registered, if flagged.
	 *
	 * @return void
	 */
	public function maybe_flush(): void {
		if ( ! get_transient( self::FLAG ) ) {
			return;
		}

		delete_transient( self::FLAG );
		flush_rewrite_rules( false );
	}

	/**
	 * Whether any watched key changed truthiness between two option values.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 * @return bool
	 */
	public static function toggled( $old_value, $new_value ): bool {
		$old = is_array( $old_value ) ? $old_value : [];
		$new = is_array( $new_value ) ? $new_value : [];

		foreach ( self::WATCHED_KEYS as $key ) {
			if ( ! empty( $old[ $key ] ) !== ! empty( $new[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}
}
