<?php
/**
 * Template functions.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Template function for breadcrumbs.
 *
 * @param array<string, mixed> $args Arguments.
 * @return void
 */
function lw_seo_breadcrumbs( array $args = [] ): void {
	$breadcrumbs = new Breadcrumbs();
	echo $breadcrumbs->render( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
