<?php
/**
 * `wp lw-seo not-found` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\NotFound\LogRepository;
use LightweightPlugins\SEO\Options;

/**
 * Inspect and maintain the 404 log.
 */
final class NotFoundCommand {

	/**
	 * Columns of `list`.
	 */
	private const FIELDS = [ 'id', 'url', 'hits', 'referrer', 'last_seen' ];

	/**
	 * Log storage.
	 *
	 * @var LogRepository
	 */
	private LogRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param LogRepository|null $repository Log storage (default: the real table).
	 */
	public function __construct( ?LogRepository $repository = null ) {
		$this->repository = $repository ?? new LogRepository();
	}

	/**
	 * List logged 404 URLs, most recent first.
	 *
	 * ## OPTIONS
	 *
	 * [--search=<text>]
	 * : Only URLs containing this text.
	 *
	 * [--orderby=<column>]
	 * : Sort column. Options: last_seen, hits, url. Default: last_seen.
	 *
	 * [--limit=<number>]
	 * : Rows to show (1-100). Default: 20.
	 *
	 * [--format=<format>]
	 * : Output format. Options: table, csv, json, count. Default: table.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function list( array $args, array $assoc_args ): void {
		$limit   = min( 100, max( 1, (int) ( $assoc_args['limit'] ?? 20 ) ) );
		$orderby = (string) ( $assoc_args['orderby'] ?? 'last_seen' );
		$result  = $this->repository->paginate(
			(string) ( $assoc_args['search'] ?? '' ),
			$orderby,
			'url' === $orderby ? 'asc' : 'desc',
			1,
			$limit
		);

		\WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', self::rows( $result['items'] ), self::FIELDS );
	}

	/**
	 * Empty the 404 log.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function clear( array $args, array $assoc_args ): void {
		\WP_CLI::confirm( 'Delete every logged 404 URL?', $assoc_args );
		$this->repository->clear();
		\WP_CLI::success( '404 log cleared.' );
	}

	/**
	 * Apply the retention window and row cap now.
	 *
	 * Uses the saved settings (retention days, max rows).
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function prune( array $args, array $assoc_args ): void {
		$deleted = $this->repository->prune(
			absint( Options::get( 'not_found_log_max_rows', 1000 ) ),
			absint( Options::get( 'not_found_log_retention_days', 30 ) )
		);

		\WP_CLI::success( sprintf( 'Pruned %d row(s).', $deleted ) );
	}

	/**
	 * Table rows for `list`.
	 *
	 * @param array<int, array<string, mixed>> $items Repository rows.
	 * @return array<int, array<string, string>>
	 */
	public static function rows( array $items ): array {
		$rows = [];
		foreach ( $items as $item ) {
			$row = [];
			foreach ( self::FIELDS as $field ) {
				$row[ $field ] = (string) ( $item[ $field ] ?? '' );
			}
			$rows[] = $row;
		}

		return $rows;
	}
}
