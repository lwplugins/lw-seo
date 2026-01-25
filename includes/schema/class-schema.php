<?php
/**
 * Main Schema class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Schema;

use LightweightPlugins\SEO\Options;

/**
 * Handles Schema.org JSON-LD output.
 */
final class Schema {

    /**
     * Constructor.
     */
    public function __construct() {
        if ( ! Options::get( 'schema_enabled' ) ) {
            return;
        }

        add_action( 'wp_head', [ $this, 'output_schema' ], 99 );
    }

    /**
     * Output Schema.org JSON-LD.
     *
     * @return void
     */
    public function output_schema(): void {
        $graph = $this->build_graph();

        if ( empty( $graph ) ) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];

        $json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

        echo "\n<!-- LW SEO Schema -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo $json . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</script>' . "\n";
        echo "<!-- /LW SEO Schema -->\n";
    }

    /**
     * Build the Schema graph.
     *
     * @return array<array<string, mixed>>
     */
    private function build_graph(): array {
        $graph = [];

        // Always add WebSite schema.
        $graph[] = $this->get_website_schema();

        // Add Organization or Person.
        $knowledge = $this->get_knowledge_graph_schema();
        if ( $knowledge ) {
            $graph[] = $knowledge;
        }

        // Context-specific schemas.
        if ( is_singular() ) {
            $graph[] = $this->get_webpage_schema();

            if ( is_singular( 'post' ) ) {
                $graph[] = $this->get_article_schema();
            }
        } elseif ( is_front_page() || is_home() ) {
            $graph[] = $this->get_webpage_schema( 'CollectionPage' );
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $graph[] = $this->get_webpage_schema( 'CollectionPage' );
        } elseif ( is_author() ) {
            $graph[] = $this->get_author_schema();
        } elseif ( is_search() ) {
            $graph[] = $this->get_webpage_schema( 'SearchResultsPage' );
        }

        return array_filter( $graph );
    }

    /**
     * Get WebSite schema.
     *
     * @return array<string, mixed>
     */
    private function get_website_schema(): array {
        $schema = [
            '@type'       => 'WebSite',
            '@id'         => home_url( '/#website' ),
            'url'         => home_url( '/' ),
            'name'        => get_bloginfo( 'name' ),
            'description' => get_bloginfo( 'description' ),
            'inLanguage'  => get_locale(),
        ];

        // Add search action.
        $schema['potentialAction'] = [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => home_url( '/?s={search_term_string}' ),
            ],
            'query-input' => 'required name=search_term_string',
        ];

        // Link to publisher.
        $knowledge_type = Options::get( 'knowledge_type' );
        if ( $knowledge_type ) {
            $schema['publisher'] = [
                '@id' => home_url( '/#' . $knowledge_type ),
            ];
        }

        return $schema;
    }

    /**
     * Get Knowledge Graph schema (Organization or Person).
     *
     * @return array<string, mixed>|null
     */
    private function get_knowledge_graph_schema(): ?array {
        $type = Options::get( 'knowledge_type' );
        $name = Options::get( 'knowledge_name' ) ?: get_bloginfo( 'name' );

        if ( empty( $type ) ) {
            return null;
        }

        $schema = [
            '@type' => ucfirst( $type ),
            '@id'   => home_url( '/#' . $type ),
            'name'  => $name,
            'url'   => home_url( '/' ),
        ];

        // Add logo for organization.
        $logo = Options::get( 'knowledge_logo' );
        if ( 'organization' === $type && ! empty( $logo ) ) {
            $schema['logo'] = [
                '@type'      => 'ImageObject',
                '@id'        => home_url( '/#logo' ),
                'url'        => $logo,
                'contentUrl' => $logo,
            ];
            $schema['image'] = [ '@id' => home_url( '/#logo' ) ];
        }

        // Add social profiles.
        $profiles = $this->get_social_profiles();
        if ( ! empty( $profiles ) ) {
            $schema['sameAs'] = $profiles;
        }

        return $schema;
    }

    /**
     * Get social profile URLs.
     *
     * @return array<string>
     */
    private function get_social_profiles(): array {
        $profiles = [];
        $keys     = [ 'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin', 'social_youtube' ];

        foreach ( $keys as $key ) {
            $url = Options::get( $key );
            if ( ! empty( $url ) ) {
                $profiles[] = $url;
            }
        }

        return $profiles;
    }

    /**
     * Get WebPage schema.
     *
     * @param string $type Page type.
     * @return array<string, mixed>
     */
    private function get_webpage_schema( string $type = 'WebPage' ): array {
        $schema = [
            '@type'           => $type,
            '@id'             => $this->get_current_url() . '#webpage',
            'url'             => $this->get_current_url(),
            'name'            => wp_get_document_title(),
            'isPartOf'        => [ '@id' => home_url( '/#website' ) ],
            'inLanguage'      => get_locale(),
            'potentialAction' => [
                [
                    '@type'  => 'ReadAction',
                    'target' => [ $this->get_current_url() ],
                ],
            ],
        ];

        if ( is_singular() ) {
            $post = get_queried_object();

            if ( $post instanceof \WP_Post ) {
                $schema['datePublished'] = get_the_date( 'c', $post );
                $schema['dateModified']  = get_the_modified_date( 'c', $post );

                // Featured image.
                if ( has_post_thumbnail( $post ) ) {
                    $image_id  = get_post_thumbnail_id( $post );
                    $image_url = get_the_post_thumbnail_url( $post, 'large' );

                    if ( $image_url ) {
                        $schema['primaryImageOfPage'] = [
                            '@id' => home_url( '/#primaryimage' ),
                        ];
                        $schema['thumbnailUrl'] = $image_url;
                    }
                }
            }
        }

        return $schema;
    }

    /**
     * Get Article schema.
     *
     * @return array<string, mixed>|null
     */
    private function get_article_schema(): ?array {
        $post = get_queried_object();

        if ( ! $post instanceof \WP_Post ) {
            return null;
        }

        $schema = [
            '@type'            => 'Article',
            '@id'              => get_permalink( $post ) . '#article',
            'headline'         => get_the_title( $post ),
            'datePublished'    => get_the_date( 'c', $post ),
            'dateModified'     => get_the_modified_date( 'c', $post ),
            'mainEntityOfPage' => [ '@id' => get_permalink( $post ) . '#webpage' ],
            'wordCount'        => str_word_count( wp_strip_all_tags( $post->post_content ) ),
            'inLanguage'       => get_locale(),
        ];

        // Author.
        $author = get_userdata( $post->post_author );
        if ( $author ) {
            $schema['author'] = [
                '@type' => 'Person',
                '@id'   => get_author_posts_url( $author->ID ) . '#author',
                'name'  => $author->display_name,
                'url'   => get_author_posts_url( $author->ID ),
            ];
        }

        // Publisher.
        $knowledge_type = Options::get( 'knowledge_type' );
        if ( $knowledge_type ) {
            $schema['publisher'] = [ '@id' => home_url( '/#' . $knowledge_type ) ];
        }

        // Featured image.
        if ( has_post_thumbnail( $post ) ) {
            $image_url = get_the_post_thumbnail_url( $post, 'large' );
            if ( $image_url ) {
                $schema['image'] = [
                    '@type' => 'ImageObject',
                    '@id'   => home_url( '/#primaryimage' ),
                    'url'   => $image_url,
                ];
            }
        }

        // Categories as keywords.
        $categories = get_the_category( $post->ID );
        if ( ! empty( $categories ) ) {
            $schema['keywords'] = implode( ', ', wp_list_pluck( $categories, 'name' ) );
        }

        return $schema;
    }

    /**
     * Get Author schema.
     *
     * @return array<string, mixed>|null
     */
    private function get_author_schema(): ?array {
        $author = get_queried_object();

        if ( ! $author instanceof \WP_User ) {
            return null;
        }

        return [
            '@type'       => 'ProfilePage',
            '@id'         => get_author_posts_url( $author->ID ) . '#webpage',
            'url'         => get_author_posts_url( $author->ID ),
            'name'        => $author->display_name,
            'description' => get_the_author_meta( 'description', $author->ID ),
            'mainEntity'  => [
                '@type'       => 'Person',
                '@id'         => get_author_posts_url( $author->ID ) . '#author',
                'name'        => $author->display_name,
                'description' => get_the_author_meta( 'description', $author->ID ),
                'url'         => get_author_posts_url( $author->ID ),
            ],
        ];
    }

    /**
     * Get current URL.
     *
     * @return string
     */
    private function get_current_url(): string {
        global $wp;
        return home_url( $wp->request ? $wp->request . '/' : '' );
    }
}
