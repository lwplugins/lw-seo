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
 * Handles robots.txt generation.
 */
final class Robots_Txt {

    /**
     * Constructor.
     */
    public function __construct() {
        if ( ! Options::get( 'robots_txt_enabled' ) ) {
            return;
        }

        add_action( 'init', [ $this, 'add_rewrite_rules' ] );
        add_action( 'template_redirect', [ $this, 'handle_request' ] );
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );

        // Also hook into WP's virtual robots.txt as fallback.
        add_filter( 'robots_txt', [ $this, 'modify_robots_txt' ], 10, 2 );
    }

    /**
     * Add rewrite rules.
     *
     * @return void
     */
    public function add_rewrite_rules(): void {
        add_rewrite_rule(
            '^robots\.txt$',
            'index.php?lw_robots_txt=1',
            'top'
        );
    }

    /**
     * Add query vars.
     *
     * @param array<string> $vars Query vars.
     * @return array<string>
     */
    public function add_query_vars( array $vars ): array {
        $vars[] = 'lw_robots_txt';
        return $vars;
    }

    /**
     * Handle robots.txt request.
     *
     * @return void
     */
    public function handle_request(): void {
        if ( ! get_query_var( 'lw_robots_txt' ) ) {
            return;
        }

        header( 'Content-Type: text/plain; charset=UTF-8' );
        header( 'X-Robots-Tag: noindex' );

        echo $this->generate_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        exit;
    }

    /**
     * Generate robots.txt content.
     *
     * @return string
     */
    private function generate_content(): string {
        $lines = [];

        // Default rules.
        $lines[] = 'User-agent: *';
        $lines[] = 'Allow: /';
        $lines[] = '';

        // Disallow wp-admin but allow admin-ajax.
        $lines[] = 'Disallow: /wp-admin/';
        $lines[] = 'Allow: /wp-admin/admin-ajax.php';
        $lines[] = '';

        // Add sitemap URL if enabled.
        if ( Options::get( 'sitemap_enabled' ) ) {
            $lines[] = 'Sitemap: ' . Sitemap::get_index_url();
            $lines[] = '';
        }

        // Add AI crawler blocks.
        $ai_blocks = $this->get_ai_crawler_blocks();
        if ( ! empty( $ai_blocks ) ) {
            $lines[] = '# AI Crawler Restrictions';
            foreach ( $ai_blocks as $agent ) {
                $lines[] = 'User-agent: ' . $agent;
                $lines[] = 'Disallow: /';
                $lines[] = '';
            }
        }

        // Add llms.txt reference if enabled.
        if ( Options::get( 'llms_txt_enabled' ) ) {
            $lines[] = '# AI Crawler Information';
            $lines[] = '# See: ' . home_url( '/llms.txt' );
        }

        return implode( "\n", $lines );
    }

    /**
     * Get list of blocked AI crawler user agents.
     *
     * @return array<string>
     */
    private function get_ai_crawler_blocks(): array {
        $blocked = [];

        $crawlers = [
            'gptbot'          => 'GPTBot',
            'chatgpt_user'    => 'ChatGPT-User',
            'claude_web'      => 'Claude-Web',
            'google_extended' => 'Google-Extended',
            'bytespider'      => 'Bytespider',
            'ccbot'           => 'CCBot',
            'perplexitybot'   => 'PerplexityBot',
            'cohere_ai'       => 'cohere-ai',
        ];

        foreach ( $crawlers as $key => $agent ) {
            if ( Options::get( 'block_' . $key ) ) {
                $blocked[] = $agent;
            }
        }

        return $blocked;
    }

    /**
     * Modify WordPress virtual robots.txt content (fallback).
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

        // Add AI crawler blocks.
        $ai_blocks = $this->get_ai_crawler_blocks();
        if ( ! empty( $ai_blocks ) ) {
            $additions[] = '';
            $additions[] = '# AI Crawler Restrictions';
            foreach ( $ai_blocks as $agent ) {
                $additions[] = 'User-agent: ' . $agent;
                $additions[] = 'Disallow: /';
            }
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

    /**
     * Flush rewrite rules on activation.
     *
     * @return void
     */
    public static function activate(): void {
        $robots = new self();
        $robots->add_rewrite_rules();
        flush_rewrite_rules();
    }
}
