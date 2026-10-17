<?php
/**
 * 404 log queries.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\NotFound;

/**
 * Reads and writes the 404 log table.
 *
 * Not final, so the logger and pruner tests can double it.
 *
 * Every variable value goes through $wpdb->prepare(); the table name and the
 * whitelisted ORDER BY column are internal constants, never user input.
 */
class LogRepository {

	/**
	 * Sortable columns (request value => column).
	 */
	private const ORDERBY = [
		'last_seen' => 'last_seen',
		'hits'      => 'hits',
		'url'       => 'url',
	];

	/**
	 * Count a hit on a path, inserting the row on its first one.
	 *
	 * @param string $path       Requested path.
	 * @param string $referrer   Referrer URL without its query string.
	 * @param string $user_agent User agent ('' when not stored).
	 * @return void
	 */
	public function record( string $path, string $referrer, string $user_agent ): void {
		global $wpdb;

		$now   = gmdate( 'Y-m-d H:i:s' );
		$table = LogTable::name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery -- internal prefix-based table name; values are prepared.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (url_hash, url, hits, referrer, user_agent, first_seen, last_seen)
				VALUES (%s, %s, 1, %s, %s, %s, %s)
				ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = VALUES(last_seen), referrer = VALUES(referrer)",
				sha1( $path ),
				$path,
				$referrer,
				$user_agent,
				$now,
				$now
			)
		);
		// phpcs:enable
	}

	/**
	 * One page of log rows.
	 *
	 * @param string $search   Substring to look for in the URL ('' = all).
	 * @param string $orderby  last_seen | hits | url (anything else = last_seen).
	 * @param string $order    asc | desc (anything else = desc).
	 * @param int    $page     1-based page number.
	 * @param int    $per_page Rows per page.
	 * @return array{items: array<int, array{id:int,url:string,hits:int,referrer:string,user_agent:string,first_seen:string,last_seen:string}>, total:int}
	 */
	public function paginate( string $search, string $orderby, string $order, int $page, int $per_page ): array {
		global $wpdb;

		$table    = LogTable::name();
		$column   = self::ORDERBY[ $orderby ] ?? 'last_seen';
		$dir      = 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';
		$per_page = max( 1, $per_page );
		$offset   = max( 0, $page - 1 ) * $per_page;
		$like     = '%' . $wpdb->esc_like( $search ) . '%';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery -- internal table name and whitelisted column/direction; values are prepared.
		$total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE url LIKE %s", $like )
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, url, hits, referrer, user_agent, first_seen, last_seen FROM {$table}
				WHERE url LIKE %s ORDER BY {$column} {$dir}, id DESC LIMIT %d OFFSET %d",
				$like,
				$per_page,
				$offset
			),
			ARRAY_A
		);
		// phpcs:enable

		$items = [];
		foreach ( (array) $rows as $row ) {
			$items[] = [
				'id'         => (int) $row['id'],
				'url'        => (string) $row['url'],
				'hits'       => (int) $row['hits'],
				'referrer'   => (string) $row['referrer'],
				'user_agent' => (string) $row['user_agent'],
				'first_seen' => (string) $row['first_seen'],
				'last_seen'  => (string) $row['last_seen'],
			];
		}

		return [
			'items' => $items,
			'total' => $total,
		];
	}

	/**
	 * Delete rows by id.
	 *
	 * @param array<int, mixed> $ids Row ids.
	 * @return int Rows deleted.
	 */
	public function delete( array $ids ): int {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( [] === $ids ) {
			return 0;
		}

		$table        = LogTable::name();
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery -- internal table name; one %d placeholder per id.
		return (int) $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids )
		);
		// phpcs:enable
	}

	/**
	 * Empty the log.
	 *
	 * @return void
	 */
	public function clear(): void {
		global $wpdb;

		$table = LogTable::name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- internal prefix-based table name, no user input.
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}

	/**
	 * Drop rows older than the retention window, then keep the newest rows only.
	 *
	 * @param int $max_rows Rows to keep (by last_seen).
	 * @param int $days     Retention in days.
	 * @return int Rows deleted.
	 */
	public function prune( int $max_rows, int $days ): int {
		global $wpdb;

		$table   = LogTable::name();
		$cutoff  = gmdate( 'Y-m-d H:i:s', time() - max( 1, $days ) * DAY_IN_SECONDS );
		$deleted = 0;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery -- internal table name; values are prepared.
		$deleted += (int) $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE last_seen < %s", $cutoff )
		);

		// The derived table works around MySQL's "no LIMIT in IN subquery".
		$deleted += (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE id NOT IN (
					SELECT id FROM ( SELECT id FROM {$table} ORDER BY last_seen DESC, id DESC LIMIT %d ) AS keep_rows
				)",
				max( 1, $max_rows )
			)
		);
		// phpcs:enable

		return $deleted;
	}
}
