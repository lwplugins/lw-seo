<?php
/**
 * `wp lw-seo bricks` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\Bricks;

/**
 * Bricks page SEO settings.
 */
final class Command {

	/**
	 * Copy the SEO settings saved in Bricks (Page settings → SEO / Social
	 * media) into the LW SEO fields that are still empty. Existing LW SEO
	 * values are never overwritten. New saves are kept in sync
	 * automatically; this is for settings saved before.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Count what would be filled without writing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-seo bricks import --dry-run
	 *
	 * @param array<int, string>         $args       Positional args (unused).
	 * @param array<string, string|bool> $assoc_args Associative args.
	 * @return void
	 */
	public function import( array $args, array $assoc_args ): void {
		$dry_run = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$tally   = Backfill::run( $dry_run );

		\WP_CLI::success(
			sprintf(
				'%s %d field(s) on %d post(s).',
				$dry_run ? 'Would fill' : 'Filled',
				$tally['fields'],
				$tally['posts']
			)
		);
	}
}
