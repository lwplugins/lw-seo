<?php
/**
 * `wp lw-seo migrate` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\Migration\MigratorInterface;
use LightweightPlugins\SEO\Migration\RankMath\Migrator as RankMathMigrator;
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

		$rows = [
			[
				'metric' => 'Options migrated',
				'count'  => (string) $result['options_migrated'],
			],
			[
				'metric' => 'Posts migrated',
				'count'  => (string) $result['posts']['migrated'],
			],
			[
				'metric' => 'Terms migrated',
				'count'  => (string) $result['terms']['migrated'],
			],
			[
				'metric' => 'Primary terms migrated',
				'count'  => (string) $result['primary_terms']['migrated'],
			],
			[
				'metric' => 'Redirects migrated',
				'count'  => (string) $result['redirects']['migrated'],
			],
		];
		\WP_CLI\Utils\format_items( 'table', $rows, [ 'metric', 'count' ] );

		foreach ( $result['warnings'] as $warning ) {
			\WP_CLI::warning( $warning['message'] );
		}

		\WP_CLI::success(
			$dry_run ? 'Dry run complete — no data modified.' : sprintf( '%s migration complete.', $label )
		);
	}
}
