<?php
/**
 * `wp lw-seo indexnow` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\IndexNow\Client;
use LightweightPlugins\SEO\IndexNow\Key;
use LightweightPlugins\SEO\IndexNow\Log;
use LightweightPlugins\SEO\IndexNow\ManualUrls;
use LightweightPlugins\SEO\IndexNow\Queue;
use LightweightPlugins\SEO\Options;

/**
 * Submit URLs to IndexNow and inspect its state.
 */
final class IndexNowCommand {

	/**
	 * Submit URLs now (or add them to the queue).
	 *
	 * ## OPTIONS
	 *
	 * <url>...
	 * : One or more URLs on this site.
	 *
	 * [--queue]
	 * : Add to the queue (sent by the next scheduled flush) instead of sending now.
	 *
	 * @param array<int, string>    $args       URLs.
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function submit( array $args, array $assoc_args ): void {
		if ( ! $this->enabled() ) {
			return;
		}

		$split = ManualUrls::split( $args );
		if ( array() !== $split['rejected'] ) {
			\WP_CLI::error( sprintf( 'Only addresses on this site can be submitted: %s', $split['rejected'][0] ) );
			return;
		}
		if ( array() === $split['accepted'] ) {
			\WP_CLI::error( 'Provide at least one URL.' );
			return;
		}

		if ( isset( $assoc_args['queue'] ) ) {
			Queue::add( $split['accepted'] );
			\WP_CLI::success( sprintf( 'Queued %d URL(s).', count( $split['accepted'] ) ) );
			return;
		}

		$result = Client::submit( $split['accepted'], 'manual' );
		if ( ! $result['ok'] ) {
			\WP_CLI::error( sprintf( 'IndexNow rejected the submission: %s (HTTP %d)', $result['message'], $result['code'] ) );
			return;
		}

		\WP_CLI::success( sprintf( 'Submitted %d URL(s): %s.', count( $split['accepted'] ), $result['message'] ) );
	}

	/**
	 * Show IndexNow state: enabled, key file, queue and the last submissions.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Format of the submission log. Options: table, csv, json. Default: table.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function status( array $args, array $assoc_args ): void {
		\WP_CLI::line( 'Enabled: ' . ( Options::get( 'indexnow_enabled' ) ? 'yes' : 'no' ) );
		\WP_CLI::line( 'Key file: ' . ( '' === Key::get() ? '(none yet)' : Key::location() ) );
		\WP_CLI::line( 'Queued changes: ' . Queue::size() );

		$rows = [];
		foreach ( array_reverse( Log::all() ) as $entry ) {
			$rows[] = [
				'time'   => gmdate( 'Y-m-d H:i:s', (int) ( $entry['time'] ?? 0 ) ),
				'source' => (string) ( $entry['source'] ?? '' ),
				'urls'   => (string) ( $entry['count'] ?? 0 ),
				'code'   => (string) ( $entry['code'] ?? 0 ),
				'result' => (string) ( $entry['message'] ?? '' ),
			];
		}
		\WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $rows, [ 'time', 'source', 'urls', 'code', 'result' ] );
	}

	/**
	 * Show the key file URL, optionally replacing the key.
	 *
	 * ## OPTIONS
	 *
	 * [--regenerate]
	 * : Replace the key with a new random one (IndexNow must be on).
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function key( array $args, array $assoc_args ): void {
		if ( isset( $assoc_args['regenerate'] ) ) {
			if ( ! $this->enabled() ) {
				return;
			}
			Key::regenerate();
			\WP_CLI::success( 'Key regenerated: ' . Key::location() );
			return;
		}

		\WP_CLI::line( '' === Key::get() ? '(no key yet)' : Key::location() );
	}

	/**
	 * Report and refuse when the feature is off.
	 *
	 * @return bool True when IndexNow is on.
	 */
	private function enabled(): bool {
		if ( Options::get( 'indexnow_enabled' ) ) {
			return true;
		}

		\WP_CLI::error( 'Turn on IndexNow first (wp lw-seo option update indexnow_enabled 1).' );

		return false;
	}
}
