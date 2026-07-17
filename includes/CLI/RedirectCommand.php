<?php
/**
 * `wp lw-seo redirect` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\Redirects\Manager;

/**
 * Manage LW SEO redirects.
 */
final class RedirectCommand {

	/**
	 * List all redirects.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format. Options: table, csv, json, count. Default: table.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function list( array $args, array $assoc_args ): void {
		$rows = [];
		foreach ( Manager::get_all() as $id => $redirect ) {
			$rows[] = [
				'id'          => (string) $id,
				'source'      => $redirect['source'],
				'destination' => $redirect['destination'],
				'type'        => (string) $redirect['type'],
				'regex'       => ! empty( $redirect['regex'] ) ? 'yes' : 'no',
				'hits'        => (string) $redirect['hits'],
			];
		}
		$format = $assoc_args['format'] ?? 'table';
		\WP_CLI\Utils\format_items( $format, $rows, [ 'id', 'source', 'destination', 'type', 'regex', 'hits' ] );
	}

	/**
	 * Add a redirect.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : Source path or pattern.
	 *
	 * <destination>
	 * : Destination URL (may be empty for 410/451).
	 *
	 * [--type=<type>]
	 * : HTTP status code (301, 302, 307, 410, 451). Default: 301.
	 *
	 * [--regex]
	 * : Treat the source as a regex pattern.
	 *
	 * @param array<int, string>    $args       [source, destination].
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function add( array $args, array $assoc_args ): void {
		$type   = (int) ( $assoc_args['type'] ?? 301 );
		$result = Manager::add( $args[0], $args[1] ?? '', $type, isset( $assoc_args['regex'] ) );

		if ( false === $result ) {
			\WP_CLI::error( 'Failed to add redirect (empty source, or missing destination for a redirecting type).' );
		}

		\WP_CLI::success( sprintf( 'Redirect added (#%d): %s → %s [%d]', (int) $result, $args[0], $args[1] ?? '', $type ) );
	}

	/**
	 * Delete a redirect by ID, or all redirects.
	 *
	 * ## OPTIONS
	 *
	 * [<id>]
	 * : Redirect ID to delete.
	 *
	 * [--all]
	 * : Delete every redirect.
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * @param array<int, string>    $args       [id?].
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function delete( array $args, array $assoc_args ): void {
		if ( isset( $assoc_args['all'] ) ) {
			\WP_CLI::confirm( 'Delete ALL redirects?', $assoc_args );
			Manager::delete_all();
			\WP_CLI::success( 'All redirects deleted.' );
			return;
		}

		if ( ! isset( $args[0] ) || '' === $args[0] ) {
			\WP_CLI::error( 'Provide a redirect ID or use --all.' );
		}

		if ( ! Manager::delete( (int) $args[0] ) ) {
			\WP_CLI::error( sprintf( 'No redirect with ID %d.', (int) $args[0] ) );
		}

		\WP_CLI::success( sprintf( 'Redirect #%d deleted.', (int) $args[0] ) );
	}

	/**
	 * Import redirects from a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to a CSV file (source,destination,type,regex).
	 *
	 * @param array<int, string>    $args       [file].
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function import( array $args, array $assoc_args ): void {
		if ( ! is_readable( $args[0] ) ) {
			\WP_CLI::error( sprintf( 'Cannot read file: %s', $args[0] ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- CLI reads a user-supplied local file path; wp_remote_get is for URLs.
		$result = Manager::import_csv( (string) file_get_contents( $args[0] ) );
		\WP_CLI::success( sprintf( 'Imported %d redirect(s), skipped %d.', (int) $result['imported'], (int) $result['skipped'] ) );
	}

	/**
	 * Export redirects to CSV (stdout or a file).
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : Write CSV to this file instead of stdout.
	 *
	 * @param array<int, string>    $args       [file?].
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function export( array $args, array $assoc_args ): void {
		$csv = Manager::export_csv();

		if ( isset( $args[0] ) && '' !== $args[0] ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- CLI writes to a user-supplied local file path.
			if ( false === file_put_contents( $args[0], $csv ) ) {
				\WP_CLI::error( sprintf( 'Cannot write file: %s', $args[0] ) );
			}
			\WP_CLI::success( sprintf( 'Exported to %s', $args[0] ) );
			return;
		}

		\WP_CLI::line( $csv );
	}
}
