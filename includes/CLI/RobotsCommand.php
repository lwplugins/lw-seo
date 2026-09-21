<?php
/**
 * `wp lw-seo robots` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\RobotsTxt;

/**
 * Preview the robots.txt LW SEO builds.
 */
final class RobotsCommand {

	/**
	 * Preview the robots.txt WordPress would serve right now, including LW
	 * SEO's rules (sitemap, llms.txt, content signals, blocked crawlers).
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function preview( array $args, array $assoc_args ): void {
		$physical = RobotsTxt::physical_file();
		if ( '' !== $physical ) {
			\WP_CLI::warning(
				sprintf( 'A physical robots.txt exists and takes priority over this virtual one: %s', $physical )
			);
		}

		\WP_CLI::line( RobotsTxt::preview() );
	}
}
