<?php
/**
 * Robots.txt class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

use LightweightPlugins\SEO\Sitemap\Sitemap;

/**
 * Handles robots.txt modifications.
 */
final class Robots_Txt {

    /**
     * Constructor.
     */
    public function __construct() {
        if ( ! Options::get( 'robots_txt_enabled' ) ) {
            return;
        }

        add_filter( 'robots_txt', [ $this, 'modify_robots_txt' ], 10, 2 );
    }

    /**
     * Modify robots.txt content.
     *
     * @param string $output The robots.txt output.
     * @param bool   $public Whether the site is public.
     * @return string
     */
    public function modify_robots_txt( string $output, bool $public ): string {
        if ( ! $public ) {
            return $output;
        }

        $additions = [];

        // Add sitemap URL if enabled.
        if ( Options::get( 'sitemap_enabled' ) ) {
            $sitemap_url = Sitemap::get_index_url();
            $additions[] = 'Sitemap: ' . $sitemap_url;
        }

        // Add llms.txt reference if enabled.
        if ( Options::get( 'llms_txt_enabled' ) ) {
            $additions[] = '';
            $additions[] = '# AI Crawler Information';
            $additions[] = '# See: ' . home_url( '/llms.txt' );
        }

        if ( ! empty( $additions ) ) {
            $output .= "\n# LW SEO\n";
            $output .= implode( "\n", $additions );
            $output .= "\n";
        }

        return $output;
    }
}
