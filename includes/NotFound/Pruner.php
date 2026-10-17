<?php
/**
 * Daily 404 log pruning.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\NotFound;

use LightweightPlugins\SEO\Options;

/**
 * Keeps the 404 log inside its retention window and row cap.
 */
final class Pruner {

	/**
	 * Cron hook.
	 */
	public const HOOK = 'lw_seo_404_prune';

	/**
	 * Constructor.
	 *
	 * @param LogRepository $repository Log storage.
	 */
	public function __construct( private LogRepository $repository ) {}

	/**
	 * Register the cron callback.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::HOOK, [ $this, 'run' ] );
	}

	/**
	 * Prune with the saved limits.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->repository->prune(
			absint( Options::get( 'not_found_log_max_rows', 1000 ) ),
			absint( Options::get( 'not_found_log_retention_days', 30 ) )
		);
	}

	/**
	 * Schedule the daily run unless it is already scheduled.
	 *
	 * @return void
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	/**
	 * Remove the scheduled run.
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::HOOK );
	}
}
