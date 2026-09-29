<?php
/**
 * Bricks theme compatibility.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Compat;

use LightweightPlugins\SEO\Meta\HeadMeta;
use LightweightPlugins\SEO\Options;

/**
 * Bricks prints its own meta description/robots, document title and Open
 * Graph tags in `wp_head`, which duplicates ours (an empty og:title on
 * pages without Bricks sharing settings). Its filters turn them off while
 * LW SEO renders the head; the filters are inert on other themes. The
 * values saved in Bricks are not lost: BricksSeoSync copies them into the
 * LW SEO fields (and LW SEO saves back into Bricks).
 */
final class Bricks {

	/**
	 * Register the Bricks filters.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'bricks/frontend/disable_seo', [ $this, 'disable_seo' ] );
		add_filter( 'bricks/frontend/disable_opengraph', [ $this, 'disable_opengraph' ] );
		( new BricksSeoSync() )->register();
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
