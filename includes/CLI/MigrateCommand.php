<?php
/**
 * `wp lw-seo migrate` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\Migration\AIOSEO\Migrator as AioseoMigrator;
use LightweightPlugins\SEO\Migration\MigratorInterface;
use LightweightPlugins\SEO\Migration\RankMath\Migrator as RankMathMigrator;
use LightweightPlugins\SEO\Migration\SEOPress\Migrator as SeopressMigrator;
use LightweightPlugins\SEO\Migration\Yoast\Migrator as YoastMigrator;

/**
 * Migrate SEO data from another plugin into LW SEO.
 */
final class MigrateCommand {

	/**
	 * Migrate RankMath SEO data into LW SEO.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview without writing any data.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-seo migrate rankmath --dry-run
	 *     wp lw-seo migrate rankmath --yes
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function rankmath( array $args, array $assoc_args ): void {
		$this->execute( new RankMathMigrator( isset( $assoc_args['dry-run'] ) ), $assoc_args, 'RankMath' );
	}

	/**
	 * Migrate Yoast SEO data into LW SEO.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview without writing any data.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-seo migrate yoast --dry-run
	 *     wp lw-seo migrate yoast --yes
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function yoast( array $args, array $assoc_args ): void {
		$this->execute( new YoastMigrator( isset( $assoc_args['dry-run'] ) ), $assoc_args, 'Yoast' );
	}

	/**
	 * Migrate SEOPress data into LW SEO.
	 *
	 * Reads SEOPress post meta, term meta and settings, plus SEOPress PRO
	 * redirects when present. SEOPress does not need to be active.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview without writing any data.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-seo migrate seopress --dry-run
	 *     wp lw-seo migrate seopress --yes
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function seopress( array $args, array $assoc_args ): void {
		$this->execute( new SeopressMigrator( isset( $assoc_args['dry-run'] ) ), $assoc_args, 'SEOPress' );
	}

	/**
	 * Migrate All in One SEO data into LW SEO.
	 *
	 * Reads the All in One SEO tables and settings. The plugin does not need
	 * to be active, its tables are enough.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview without writing any data.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-seo migrate aioseo --dry-run
	 *     wp lw-seo migrate aioseo --yes
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function aioseo( array $args, array $assoc_args ): void {
		$this->execute( new AioseoMigrator( isset( $assoc_args['dry-run'] ) ), $assoc_args, 'All in One SEO' );
	}

	/**
	 * Run a migrator and print the result.
	 *
	 * @param MigratorInterface     $migrator   Migrator instance.
	 * @param array<string, string> $assoc_args Associative args.
	 * @param string                $label      Human label.
	 * @return void
	 */
	private function execute( MigratorInterface $migrator, array $assoc_args, string $label ): void {
		$dry_run = isset( $assoc_args['dry-run'] );

		if ( ! $dry_run ) {
			\WP_CLI::confirm(
				sprintf( 'Migrate %s data into LW SEO? Existing LW SEO data is preserved.', $label ),
				$assoc_args
			);
		}

		$result = $migrator->run();

		$rows = $this->rows( $result );
		\WP_CLI\Utils\format_items( 'table', $rows, [ 'metric', 'count' ] );

		foreach ( $result['warnings'] as $warning ) {
			\WP_CLI::warning( $warning['message'] );
		}

		\WP_CLI::success(
			$dry_run ? 'Dry run complete — no data modified.' : sprintf( '%s migration complete.', $label )
		);
	}

	/**
	 * Result table rows.
	 *
	 * @param array<string, mixed> $result Migrator result.
	 * @return array<array{metric: string, count: string}>
	 */
	private function rows( array $result ): array {
		$metrics = [
			'Options migrated'                    => $result['options_migrated'],
			'Posts migrated'                      => $result['posts']['migrated'],
			'Posts skipped (LW SEO data present)' => $result['posts']['skipped_already_present'] ?? 0,
			'Posts skipped (no data)'             => $result['posts']['skipped_no_data'] ?? 0,
			'Terms migrated'                      => $result['terms']['migrated'],
			'Terms skipped (LW SEO data present)' => $result['terms']['skipped_already_present'] ?? 0,
			'Terms skipped (no data)'             => $result['terms']['skipped_no_data'] ?? 0,
			'Primary terms migrated'              => $result['primary_terms']['migrated'],
			'Redirects migrated'                  => $result['redirects']['migrated'],
			'Redirects skipped'                   => $result['redirects']['skipped'] ?? 0,
		];

		$rows = [];
		foreach ( $metrics as $metric => $count ) {
			$rows[] = [
				'metric' => $metric,
				'count'  => (string) $count,
			];
		}

		return $rows;
	}
}
