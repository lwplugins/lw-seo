<?php
/**
 * Replace Variables class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Handles replacement of %%variables%% in titles and descriptions.
 */
final class Replace_Vars {

    /**
     * Current post object.
     *
     * @var \WP_Post|null
     */
    private static ?\WP_Post $post = null;

    /**
     * Current term object.
     *
     * @var \WP_Term|null
     */
    private static ?\WP_Term $term = null;

    /**
     * Current user object.
     *
     * @var \WP_User|null
     */
    private static ?\WP_User $user = null;

    /**
     * Replace variables in a string.
     *
     * @param string        $string The string with variables.
     * @param \WP_Post|null $post   Optional post object.
     * @param \WP_Term|null $term   Optional term object.
     * @param \WP_User|null $user   Optional user object.
     * @return string
     */
    public static function replace( string $string, ?\WP_Post $post = null, ?\WP_Term $term = null, ?\WP_User $user = null ): string {
        self::$post = $post;
        self::$term = $term;
        self::$user = $user;

        // Find all %%variable%% patterns.
        $string = preg_replace_callback(
            '/%%([a-z_]+)%%/',
            [ self::class, 'replace_callback' ],
            $string
        );

        // Clean up multiple spaces.
        $string = preg_replace( '/\s+/', ' ', $string );

        return trim( $string );
    }

    /**
     * Callback for variable replacement.
     *
     * @param array<string> $matches Regex matches.
     * @return string
     */
    private static function replace_callback( array $matches ): string {
        $var = $matches[1];

        return match ( $var ) {
            // Site variables.
            'sitename'    => get_bloginfo( 'name' ),
            'sitedesc'    => get_bloginfo( 'description' ),
            'siteurl'     => home_url(),

            // Separator.
            'sep'         => self::get_separator(),

            // Post variables.
            'title'       => self::get_title(),
            'excerpt'     => self::get_excerpt(),
            'date'        => self::get_date(),
            'modified'    => self::get_modified_date(),
            'author'      => self::get_author(),
            'category'    => self::get_primary_category(),
            'tag'         => self::get_first_tag(),
            'id'          => self::get_post_id(),

            // Term variables.
            'term_title'       => self::get_term_title(),
            'term_description' => self::get_term_description(),

            // Archive variables.
            'searchphrase' => get_search_query(),

            // Date variables.
            'currentdate'  => wp_date( get_option( 'date_format' ) ),
            'currentyear'  => wp_date( 'Y' ),
            'currentmonth' => wp_date( 'F' ),
            'currentday'   => wp_date( 'j' ),

            // Pagination.
            'page'         => self::get_page_number(),
            'pagetotal'    => self::get_page_total(),
            'pagenumber'   => self::get_page_string(),

            // Default: return empty or original.
            default        => '',
        };
    }

    /**
     * Get the separator.
     *
     * @return string
     */
    private static function get_separator(): string {
        return Options::get( 'separator' ) ?: '-';
    }

    /**
     * Get the title.
     *
     * @return string
     */
    private static function get_title(): string {
        if ( self::$post instanceof \WP_Post ) {
            return get_the_title( self::$post );
        }

        if ( self::$term instanceof \WP_Term ) {
            return self::$term->name;
        }

        if ( self::$user instanceof \WP_User ) {
            return self::$user->display_name;
        }

        return '';
    }

    /**
     * Get the excerpt.
     *
     * @return string
     */
    private static function get_excerpt(): string {
        if ( ! self::$post instanceof \WP_Post ) {
            return '';
        }

        $excerpt = self::$post->post_excerpt;

        if ( empty( $excerpt ) ) {
            $excerpt = wp_trim_words( wp_strip_all_tags( self::$post->post_content ), 30, '...' );
        }

        return $excerpt;
    }

    /**
     * Get the post date.
     *
     * @return string
     */
    private static function get_date(): string {
        if ( ! self::$post instanceof \WP_Post ) {
            return '';
        }

        return get_the_date( '', self::$post );
    }

    /**
     * Get the modified date.
     *
     * @return string
     */
    private static function get_modified_date(): string {
        if ( ! self::$post instanceof \WP_Post ) {
            return '';
        }

        return get_the_modified_date( '', self::$post );
    }

    /**
     * Get the author name.
     *
     * @return string
     */
    private static function get_author(): string {
        if ( self::$user instanceof \WP_User ) {
            return self::$user->display_name;
        }

        if ( self::$post instanceof \WP_Post ) {
            return get_the_author_meta( 'display_name', self::$post->post_author );
        }

        return '';
    }

    /**
     * Get the primary category.
     *
     * @return string
     */
    private static function get_primary_category(): string {
        if ( ! self::$post instanceof \WP_Post ) {
            return '';
        }

        $categories = get_the_category( self::$post->ID );

        if ( empty( $categories ) ) {
            return '';
        }

        return $categories[0]->name;
    }

    /**
     * Get the first tag.
     *
     * @return string
     */
    private static function get_first_tag(): string {
        if ( ! self::$post instanceof \WP_Post ) {
            return '';
        }

        $tags = get_the_tags( self::$post->ID );

        if ( empty( $tags ) || is_wp_error( $tags ) ) {
            return '';
        }

        return $tags[0]->name;
    }

    /**
     * Get the post ID.
     *
     * @return string
     */
    private static function get_post_id(): string {
        if ( ! self::$post instanceof \WP_Post ) {
            return '';
        }

        return (string) self::$post->ID;
    }

    /**
     * Get the term title.
     *
     * @return string
     */
    private static function get_term_title(): string {
        if ( self::$term instanceof \WP_Term ) {
            return self::$term->name;
        }

        return single_term_title( '', false ) ?: '';
    }

    /**
     * Get the term description.
     *
     * @return string
     */
    private static function get_term_description(): string {
        if ( self::$term instanceof \WP_Term ) {
            return wp_strip_all_tags( term_description( self::$term ) );
        }

        return '';
    }

    /**
     * Get current page number.
     *
     * @return string
     */
    private static function get_page_number(): string {
        $page = get_query_var( 'paged', 1 );

        if ( $page < 1 ) {
            $page = 1;
        }

        return (string) $page;
    }

    /**
     * Get total pages.
     *
     * @return string
     */
    private static function get_page_total(): string {
        global $wp_query;

        return (string) ( $wp_query->max_num_pages ?? 1 );
    }

    /**
     * Get page string like "Page 2 of 5".
     *
     * @return string
     */
    private static function get_page_string(): string {
        $page  = (int) self::get_page_number();
        $total = (int) self::get_page_total();

        if ( $page <= 1 && $total <= 1 ) {
            return '';
        }

        /* translators: 1: current page number, 2: total page count */
        return sprintf( __( 'Page %1$d of %2$d', 'lw-seo' ), $page, $total );
    }

    /**
     * Get available variables for documentation.
     *
     * @return array<string, string>
     */
    public static function get_available_variables(): array {
        return [
            '%%sitename%%'         => __( 'Site name', 'lw-seo' ),
            '%%sitedesc%%'         => __( 'Site tagline', 'lw-seo' ),
            '%%sep%%'              => __( 'Separator', 'lw-seo' ),
            '%%title%%'            => __( 'Post/page title', 'lw-seo' ),
            '%%excerpt%%'          => __( 'Post excerpt', 'lw-seo' ),
            '%%date%%'             => __( 'Post date', 'lw-seo' ),
            '%%modified%%'         => __( 'Post modified date', 'lw-seo' ),
            '%%author%%'           => __( 'Author name', 'lw-seo' ),
            '%%category%%'         => __( 'Primary category', 'lw-seo' ),
            '%%tag%%'              => __( 'First tag', 'lw-seo' ),
            '%%term_title%%'       => __( 'Term name', 'lw-seo' ),
            '%%term_description%%' => __( 'Term description', 'lw-seo' ),
            '%%searchphrase%%'     => __( 'Search query', 'lw-seo' ),
            '%%currentyear%%'      => __( 'Current year', 'lw-seo' ),
            '%%currentmonth%%'     => __( 'Current month', 'lw-seo' ),
            '%%currentdate%%'      => __( 'Current date', 'lw-seo' ),
            '%%page%%'             => __( 'Page number', 'lw-seo' ),
            '%%pagenumber%%'       => __( 'Page X of Y', 'lw-seo' ),
        ];
    }
}
