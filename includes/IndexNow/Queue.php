<?php
/**
 * IndexNow URL queue.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\IndexNow;

use LightweightPlugins\SEO\Options;

/**
 * Collects changed URLs and sends them in one batch by a single cron event.
 */
final class Queue {

	/**
	 * Cron hook.
	 */
	public const HOOK = 'lw_seo_indexnow_flush';

	/**
	 * Option that stores pending URLs.
	 */
	public const OPTION = 'lw_seo_indexnow_queue';

	/**
	 * Hook the cron handler. Runs even while the feature is off, so a leftover
	 * queue is discarded instead of sent.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( self::HOOK, [ self::class, 'flush' ] );
		add_action( 'update_option_' . Options::OPTION_NAME, [ self::class, 'on_options_updated' ], 10, 2 );
	}

	/**
	 * Drop the queue and the pending flush when the feature is switched off,
	 * so stale URLs are not sent after it is enabled again.
	 *
	 * @param mixed $old_value Previous options.
	 * @param mixed $new_value New options.
	 * @return void
	 */
	public static function on_options_updated( $old_value, $new_value ): void {
		$was = is_array( $old_value ) && ! empty( $old_value['indexnow_enabled'] );
		$is  = is_array( $new_value ) && ! empty( $new_value['indexnow_enabled'] );

		if ( $was && ! $is ) {
			delete_option( self::OPTION );
			wp_clear_scheduled_hook( self::HOOK );
		}
	}

	/**
	 * Add URLs (deduplicated) and make sure a flush is scheduled.
	 *
	 * @param array<int, string> $urls URLs.
	 * @return void
	 */
	public static function add( array $urls ): void {
		$merged = array_values( array_unique( array_merge( self::pending(), array_map( 'strval', $urls ) ) ) );

		if ( array() === $merged ) {
			return;
		}

		update_option( self::OPTION, $merged, false );

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_single_event( time() + 60, self::HOOK );
		}
	}

	/**
	 * Send and clear the queue (cron callback).
	 *
	 * @return void
	 */
	public static function flush(): void {
		$urls = self::pending();
		delete_option( self::OPTION );

		if ( array() === $urls || ! Options::get( 'indexnow_enabled' ) ) {
			return;
		}

		Client::submit( $urls, 'auto' );
	}

	/**
	 * Pending URLs.
	 *
	 * @return array<int, string>
	 */
	public static function pending(): array {
		$urls = get_option( self::OPTION, [] );

		return is_array( $urls ) ? array_values( array_map( 'strval', $urls ) ) : [];
	}
}
