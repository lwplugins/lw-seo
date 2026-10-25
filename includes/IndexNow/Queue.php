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
 * Collects changes and sends them in one batch by a single cron event.
 *
 * Published posts and edited terms are queued by ID and turned into URLs only
 * at flush time: WordPress, the meta boxes, the REST API and WooCommerce save
 * the noindex meta and the product visibility after the status change, so an
 * eligibility check made at that moment would see stale data. Removals are
 * queued as the URL captured while the object was still public.
 */
final class Queue {

	/**
	 * Cron hook.
	 */
	public const HOOK = 'lw_seo_indexnow_flush';

	/**
	 * Option that stores the queue: {posts: int[], terms: int[], urls: string[], full: bool}.
	 */
	public const OPTION = 'lw_seo_indexnow_queue';

	/**
	 * Maximum queued entries (posts + terms + URLs). Newer additions are dropped.
	 */
	public const MAX = 10000;

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
	 * Queue ready URLs (removals, manual submissions).
	 *
	 * @param array<int, string> $urls URLs.
	 * @return void
	 */
	public static function add( array $urls ): void {
		self::push( 'urls', array_map( 'strval', $urls ) );
	}

	/**
	 * Queue posts by ID; resolved to permalinks at flush time.
	 *
	 * @param array<int, int> $ids Post IDs.
	 * @return void
	 */
	public static function add_posts( array $ids ): void {
		self::push( 'posts', array_map( 'intval', $ids ) );
	}

	/**
	 * Queue terms by ID; resolved to archive links at flush time.
	 *
	 * @param array<int, int> $ids Term IDs.
	 * @return void
	 */
	public static function add_terms( array $ids ): void {
		self::push( 'terms', array_map( 'intval', $ids ) );
	}

	/**
	 * Send and clear the queue (cron callback).
	 *
	 * @return void
	 */
	public static function flush(): void {
		$queue = self::pending();
		delete_option( self::OPTION );

		// An inactive site (switched off, not public, not production) drops it.
		if ( 0 === self::count( $queue ) || ! Activity::is_active() ) {
			return;
		}

		$urls = QueueResolver::urls( $queue );

		if ( array() !== $urls ) {
			Client::submit( $urls, 'auto' );
		}
	}

	/**
	 * Number of queued entries.
	 *
	 * @return int
	 */
	public static function size(): int {
		return self::count( self::pending() );
	}

	/**
	 * The stored queue. A flat URL list stored by an earlier build is read as URLs.
	 *
	 * @return array{posts: array<int, int>, terms: array<int, int>, urls: array<int, string>, full: bool}
	 */
	public static function pending(): array {
		$raw = get_option( self::OPTION, [] );
		$raw = is_array( $raw ) ? $raw : [];

		if ( ! isset( $raw['posts'] ) && ! isset( $raw['terms'] ) && ! isset( $raw['urls'] ) ) {
			$raw = [ 'urls' => array_values( array_filter( $raw, 'is_string' ) ) ];
		}

		return [
			'posts' => array_values( array_map( 'intval', (array) ( $raw['posts'] ?? [] ) ) ),
			'terms' => array_values( array_map( 'intval', (array) ( $raw['terms'] ?? [] ) ) ),
			'urls'  => array_values( array_map( 'strval', (array) ( $raw['urls'] ?? [] ) ) ),
			'full'  => ! empty( $raw['full'] ),
		];
	}

	/**
	 * Add new entries to one bucket within the cap and schedule a flush.
	 *
	 * @param string            $bucket 'posts', 'terms' or 'urls'.
	 * @param array<int, mixed> $items  Entries.
	 * @return void
	 */
	private static function push( string $bucket, array $items ): void {
		$queue = self::pending();
		$new   = array_values( array_diff( array_unique( array_filter( $items ) ), $queue[ $bucket ] ) );

		if ( array() === $new ) {
			return;
		}

		$room = max( 0, self::MAX - self::count( $queue ) );

		if ( count( $new ) > $room ) {
			self::note_full( $queue, count( $new ) - $room );
			$new = array_slice( $new, 0, $room );
		}

		$queue[ $bucket ] = array_merge( $queue[ $bucket ], $new );
		update_option( self::OPTION, $queue, false );

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_single_event( time() + 60, self::HOOK );
		}
	}

	/**
	 * Log the first overflow of a queue; later ones stay silent until it is sent.
	 *
	 * @param array<string, mixed> $queue   Queue (marked full in place).
	 * @param int                  $dropped Entries left out.
	 * @return void
	 */
	private static function note_full( array &$queue, int $dropped ): void {
		if ( ! $queue['full'] ) {
			Log::add(
				[
					'time'    => time(),
					'count'   => $dropped,
					'code'    => 0,
					'ok'      => false,
					'message' => 'Queue full (' . self::MAX . ' entries); newer changes were not queued.',
					'source'  => 'auto',
				]
			);
		}

		$queue['full'] = true;
	}

	/**
	 * Entries in a queue.
	 *
	 * @param array<string, mixed> $queue Queue.
	 * @return int
	 */
	private static function count( array $queue ): int {
		return count( $queue['posts'] ) + count( $queue['terms'] ) + count( $queue['urls'] );
	}
}
