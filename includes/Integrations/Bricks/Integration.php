<?php
/**
 * Bricks theme integration.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\Bricks;

use LightweightPlugins\SEO\Integrations\IntegrationInterface;
use LightweightPlugins\SEO\Meta\HeadMeta;
use LightweightPlugins\SEO\Options;

/**
 * Bricks prints its own meta description/robots, document title and Open
 * Graph tags in `wp_head`, which duplicates ours (an empty og:title on
 * pages without Bricks sharing settings). Its filters turn them off while
 * LW SEO renders the head. The values saved in Bricks are not lost:
 * SeoSync copies them into the LW SEO fields (and LW SEO saves back into
 * Bricks). Bricks pages' content reaches the Markdown output and
 * llms-full.txt through MarkdownContent.
 */
final class Integration implements IntegrationInterface {

	/**
	 * Integration ID.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'bricks';
	}

	/**
	 * Bricks is the active theme: its folder is the template (a Bricks
	 * child theme counts too: get_template() is the parent), or Bricks'
	 * functions.php ran from a folder with another name.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return 'bricks' === get_template() || defined( 'BRICKS_VERSION' );
	}

	/**
	 * Register the Bricks hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'bricks/frontend/disable_seo', [ $this, 'disable_seo' ] );
		add_filter( 'bricks/frontend/disable_opengraph', [ $this, 'disable_opengraph' ] );
		add_filter( 'lw_seo_markdown_source_html', [ MarkdownContent::class, 'filter' ], 10, 2 );
		( new SeoSync() )->register();

		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'lw-seo bricks', Command::class );
		}
	}

	/**
	 * Turn off Bricks' SEO tags while our head meta is active.
	 *
	 * @param mixed $disabled Whether Bricks' settings already disable them.
	 * @return bool
	 */
	public function disable_seo( $disabled ): bool {
		return (bool) $disabled || ! HeadMeta::is_conflicting_plugin_active();
	}

	/**
	 * Turn off Bricks' Open Graph tags while ours are enabled.
	 *
	 * @param mixed $disabled Whether Bricks' settings already disable them.
	 * @return bool
	 */
	public function disable_opengraph( $disabled ): bool {
		return (bool) $disabled || ( ! HeadMeta::is_conflicting_plugin_active() && (bool) Options::get( 'opengraph_enabled' ) );
	}
}
