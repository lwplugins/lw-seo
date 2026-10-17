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
