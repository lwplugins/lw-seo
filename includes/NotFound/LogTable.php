<?php
/**
 * 404 log table schema.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\NotFound;

/**
 * Creates and drops the table behind the 404 log.
 */
final class LogTable {

	/**
	 * Option holding the installed schema version.
	 */
	public const VERSION_OPTION = 'lw_seo_404_table_version';

	/**
	 * Current schema version.
	 */
	public const VERSION = '1';

	/**
	 * Full table name, including the site's prefix.
	 *
	 * @return string
	 */
	public static function name(): string {
		global $wpdb;

		return $wpdb->prefix . 'lw_seo_404_log';
	}

	/**
	 * Create or update the table and record the schema version.
	 *
	 * @return void
	 */
	public static function install(): void {
		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) && defined( 'ABSPATH' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$table   = self::name();
		$charset = $wpdb->get_charset_collate();

		// dbDelta is picky: two spaces after PRIMARY KEY, one definition per line.
		$sql = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  url_hash char(40) NOT NULL,
  url varchar(2048) NOT NULL,
  hits int(10) unsigned NOT NULL DEFAULT 1,
  referrer varchar(2048) NOT NULL DEFAULT '',
  user_agent varchar(255) NOT NULL DEFAULT '',
  first_seen datetime NOT NULL,
  last_seen datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY url_hash (url_hash),
  KEY last_seen (last_seen)
) {$charset};";

		dbDelta( $sql );

		if ( self::VERSION !== get_option( self::VERSION_OPTION ) ) {
			update_option( self::VERSION_OPTION, self::VERSION );
		}
	}

	/**
	 * Drop the table and forget the schema version.
	 *
	 * @return void
	 */
	public static function drop(): void {
		global $wpdb;

		$table = self::name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- internal prefix-based table name, no user input.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		delete_option( self::VERSION_OPTION );
	}
}
