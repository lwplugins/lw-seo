<?php
/**
 * Breadcrumbs class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

/**
 * Handles breadcrumbs generation and output.
 */
final class Breadcrumbs {

    /**
     * Breadcrumb items.
     *
     * @var array<array{title: string, url: string}>
     */
    private array $items = [];

    /**
     * Constructor.
     */
    public function __construct() {
        if ( ! Options::get( 'breadcrumbs_enabled' ) ) {
            return;
        }

        add_shortcode( 'lw_breadcrumbs', [ $this, 'shortcode' ] );
    }

    /**
     * Shortcode callback.
     *
     * @param array<string, string>|string $atts Shortcode attributes.
     * @return string
     */
    public function shortcode( $atts ): string {
        $atts = shortcode_atts(
            [
                'separator'    => '»',
                'home'         => __( 'Home', 'lw-seo' ),
                'show_current' => 'true',
                'class'        => 'lw-breadcrumbs',
            ],
            $atts,
            'lw_breadcrumbs'
        );

        return $this->render( $atts );
    }

    /**
     * Render breadcrumbs.
     *
     * @param array<string, string> $args Arguments.
     * @return string
     */
    public function render( array $args = [] ): string {
        $args = wp_parse_args(
            $args,
            [
                'separator'    => '»',
                'home'         => __( 'Home', 'lw-seo' ),
                'show_current' => true,
                'class'        => 'lw-breadcrumbs',
            ]
        );

        $this->items = [];
        $this->build_breadcrumbs( $args );

        if ( empty( $this->items ) ) {
            return '';
        }

        return $this->render_html( $args );
    }

    /**
     * Build breadcrumb items.
     *
     * @param array<string, mixed> $args Arguments.
     * @return void
     */
    private function build_breadcrumbs( array $args ): void {
        // Home.
        $this->items[] = [
            'title' => $args['home'],
            'url'   => home_url( '/' ),
        ];

        if ( is_front_page() ) {
            return;
        }

        if ( is_home() ) {
            $this->add_blog_page( $args );
        } elseif ( is_singular() ) {
            $this->add_singular( $args );
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $this->add_taxonomy( $args );
        } elseif ( is_author() ) {
            $this->add_author( $args );
        } elseif ( is_date() ) {
            $this->add_date( $args );
        } elseif ( is_search() ) {
            $this->add_search( $args );
        } elseif ( is_404() ) {
            $this->add_404( $args );
        } elseif ( is_archive() ) {
            $this->add_archive( $args );
        }
    }

    /**
     * Add blog page breadcrumb.
     *
     * @param array<string, mixed> $args Arguments.
     * @return void
     */
    private function add_blog_page( array $args ): void {
        $blog_page_id = get_option( 'page_for_posts' );

        if ( $blog_page_id && $args['show_current'] ) {
            $this->items[] = [
                'title' => get_the_title( $blog_page_id ),
                'url'   => '',
            ];
        }
    }

    /**
     * Add singular breadcrumbs.
     *
     * @param array<string, mixed> $args Arguments.
     * @return void
     */
    private function add_singular( array $args ): void {
        $post = get_queried_object();

        if ( ! $post instanceof \WP_Post ) {
            return;
        }

        // Add post type archive for custom post types.
        if ( 'post' !== $post->post_type && 'page' !== $post->post_type ) {
            $post_type = get_post_type_object( $post->post_type );
            if ( $post_type && $post_type->has_archive ) {
                $this->items[] = [
                    'title' => $post_type->labels->name,
                    'url'   => get_post_type_archive_link( $post->post_type ),
                ];
            }
        }

        // Add categories for posts.
        if ( 'post' === $post->post_type ) {
            $categories = get_the_category( $post->ID );
            if ( ! empty( $categories ) ) {
                $category = $categories[0];

                // Add parent categories.
                $parents = get_ancestors( $category->term_id, 'category' );
                $parents = array_reverse( $parents );

                foreach ( $parents as $parent_id ) {
                    $parent = get_term( $parent_id, 'category' );
                    if ( $parent instanceof \WP_Term ) {
                        $this->items[] = [
                            'title' => $parent->name,
                            'url'   => get_term_link( $parent ),
                        ];
                    }
                }

                $this->items[] = [
                    'title' => $category->name,
                    'url'   => get_term_link( $category ),
                ];
            }
        }

        // Add parent pages for pages.
        if ( 'page' === $post->post_type && $post->post_parent ) {
            $parents = get_ancestors( $post->ID, 'page' );
            $parents = array_reverse( $parents );

            foreach ( $parents as $parent_id ) {
                $this->items[] = [
                    'title' => get_the_title( $parent_id ),
                    'url'   => get_permalink( $parent_id ),
                ];
            }
        }

        // Current page.
        if ( $args['show_current'] ) {
            $this->items[] = [
                'title' => get_the_title( $post ),
                'url'   => '',
            ];
        }
    }

    /**
     * Add taxonomy breadcrumbs.
     *
     * @param array<string, mixed> $args Arguments.
     * @return void
     */
    private function add_taxonomy( array $args ): void {
        $term = get_queried_object();

        if ( ! $term instanceof \WP_Term ) {
            return;
        }

        // Add parent terms.
        $parents = get_ancestors( $term->term_id, $term->taxonomy );
        $parents = array_reverse( $parents );

        foreach ( $parents as $parent_id ) {
            $parent = get_term( $parent_id, $term->taxonomy );
            if ( $parent instanceof \WP_Term ) {
                $this->items[] = [
                    'title' => $parent->name,
                    'url'   => get_term_link( $parent ),
                ];
            }
        }

        if ( $args['show_current'] ) {
            $this->items[] = [
                'title' => $term->name,
                'url'   => '',
            ];
        }
    }

    /**
     * Add author breadcrumb.
     *
     * @param array<string, mixed> $args Arguments.
     * @return void
     */
    private function add_author( array $args ): void {
        $author = get_queried_object();

        if ( ! $author instanceof \WP_User ) {
            return;
        }

        if ( $args['show_current'] ) {
            $this->items[] = [
                'title' => $author->display_name,
                'url'   => '',
            ];
        }
    }

    /**
     * Add date archive breadcrumb.
     *
     * @param array<string, mixed> $args Arguments.
     * @return void
     */
    private function add_date( array $args ): void {
        if ( is_year() ) {
            $this->items[] = [
                'title' => get_the_date( 'Y' ),
                'url'   => '',
            ];
        } elseif ( is_month() ) {
            $this->items[] = [
                'title' => get_the_date( 'Y' ),
                'url'   => get_year_link( get_the_date( 'Y' ) ),
            ];
            $this->items[] = [
                'title' => get_the_date( 'F' ),
                'url'   => '',
            ];
        } elseif ( is_day() ) {
            $this->items[] = [
                'title' => get_the_date( 'Y' ),
                'url'   => get_year_link( get_the_date( 'Y' ) ),
            ];
            $this->items[] = [
                'title' => get_the_date( 'F' ),
                'url'   => get_month_link( get_the_date( 'Y' ), get_the_date( 'm' ) ),
            ];
            $this->items[] = [
                'title' => get_the_date( 'j' ),
                'url'   => '',
            ];
        }
    }

    /**
     * Add search breadcrumb.
     *
     * @param array<string, mixed> $args Arguments.
     * @return void
     */
    private function add_search( array $args ): void {
        if ( $args['show_current'] ) {
            /* translators: %s: search query */
            $this->items[] = [
                'title' => sprintf( __( 'Search: %s', 'lw-seo' ), get_search_query() ),
                'url'   => '',
            ];
        }
    }

    /**
     * Add 404 breadcrumb.
     *
     * @param array<string, mixed> $args Arguments.
     * @return void
     */
    private function add_404( array $args ): void {
        if ( $args['show_current'] ) {
            $this->items[] = [
                'title' => __( 'Page not found', 'lw-seo' ),
                'url'   => '',
            ];
        }
    }

    /**
     * Add archive breadcrumb.
     *
     * @param array<string, mixed> $args Arguments.
     * @return void
     */
    private function add_archive( array $args ): void {
        $post_type = get_queried_object();

        if ( $post_type instanceof \WP_Post_Type && $args['show_current'] ) {
            $this->items[] = [
                'title' => $post_type->labels->name,
                'url'   => '',
            ];
        }
    }

    /**
     * Render HTML output.
     *
     * @param array<string, mixed> $args Arguments.
     * @return string
     */
    private function render_html( array $args ): string {
        $output  = '<nav class="' . esc_attr( $args['class'] ) . '" aria-label="' . esc_attr__( 'Breadcrumb', 'lw-seo' ) . '">';
        $output .= '<ol itemscope itemtype="https://schema.org/BreadcrumbList">';

        $count = count( $this->items );

        foreach ( $this->items as $index => $item ) {
            $position = $index + 1;
            $is_last  = ( $position === $count );

            $output .= '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';

            if ( ! empty( $item['url'] ) && ! $is_last ) {
                $output .= '<a itemprop="item" href="' . esc_url( $item['url'] ) . '">';
                $output .= '<span itemprop="name">' . esc_html( $item['title'] ) . '</span>';
                $output .= '</a>';
            } else {
                $output .= '<span itemprop="name">' . esc_html( $item['title'] ) . '</span>';
            }

            $output .= '<meta itemprop="position" content="' . esc_attr( (string) $position ) . '" />';
            $output .= '</li>';

            if ( ! $is_last ) {
                $output .= '<li class="separator" aria-hidden="true">' . esc_html( $args['separator'] ) . '</li>';
            }
        }

        $output .= '</ol>';
        $output .= '</nav>';

        return $output;
    }

    /**
     * Get breadcrumb items (for use in templates).
     *
     * @return array<array{title: string, url: string}>
     */
    public function get_items(): array {
        if ( empty( $this->items ) ) {
            $this->build_breadcrumbs( [
                'home'         => __( 'Home', 'lw-seo' ),
                'show_current' => true,
            ] );
        }

        return $this->items;
    }
}

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
