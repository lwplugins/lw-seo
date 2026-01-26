<?php
/**
 * REST API endpoints for headless WordPress support.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Post;
use WP_Term;
use WP_User;

/**
 * REST API class for headless SEO data.
 */
class RestApi {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private const NAMESPACE = 'lw-seo/v1';

	/**
	 * Initialize REST API.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Get SEO data by post ID.
		register_rest_route(
			self::NAMESPACE,
			'/meta/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_post_meta' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => __( 'Post ID', 'lw-seo' ),
					],
				],
			]
		);

		// Get SEO data by term ID.
		register_rest_route(
			self::NAMESPACE,
			'/meta/term/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_term_meta' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id'       => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => __( 'Term ID', 'lw-seo' ),
					],
					'taxonomy' => [
						'required'    => false,
						'type'        => 'string',
						'default'     => 'category',
						'description' => __( 'Taxonomy name', 'lw-seo' ),
					],
				],
			]
		);

		// Get SEO data by author ID.
		register_rest_route(
			self::NAMESPACE,
			'/meta/author/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_author_meta' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => __( 'Author ID', 'lw-seo' ),
					],
				],
			]
		);

		// Get schema by post ID.
		register_rest_route(
			self::NAMESPACE,
			'/schema/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_post_schema' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => __( 'Post ID', 'lw-seo' ),
					],
				],
			]
		);

		// Get breadcrumbs by post ID.
		register_rest_route(
			self::NAMESPACE,
			'/breadcrumbs/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_post_breadcrumbs' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => __( 'Post ID', 'lw-seo' ),
					],
				],
			]
		);
	}

	/**
	 * Get SEO meta data for a post.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_post_meta( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'lw-seo' ),
				[ 'status' => 404 ]
			);
		}

		// Check if post is publicly viewable.
		if ( 'publish' !== $post->post_status && ! current_user_can( 'read_post', $post_id ) ) {
			return new WP_Error(
				'post_not_accessible',
				__( 'Post is not accessible.', 'lw-seo' ),
				[ 'status' => 403 ]
			);
		}

		$data = $this->build_post_seo_data( $post );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get SEO meta data for a term.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_term_meta( WP_REST_Request $request ) {
		$term_id  = $request->get_param( 'id' );
		$taxonomy = $request->get_param( 'taxonomy' );
		$term     = get_term( $term_id, $taxonomy );

		if ( ! $term instanceof WP_Term || is_wp_error( $term ) ) {
			return new WP_Error(
				'term_not_found',
				__( 'Term not found.', 'lw-seo' ),
				[ 'status' => 404 ]
			);
		}

		$data = $this->build_term_seo_data( $term );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get SEO meta data for an author.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_author_meta( WP_REST_Request $request ) {
		$author_id = $request->get_param( 'id' );
		$user      = get_user_by( 'id', $author_id );

		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'author_not_found',
				__( 'Author not found.', 'lw-seo' ),
				[ 'status' => 404 ]
			);
		}

		$data = $this->build_author_seo_data( $user );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get schema data for a post.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_post_schema( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'lw-seo' ),
				[ 'status' => 404 ]
			);
		}

		if ( 'publish' !== $post->post_status && ! current_user_can( 'read_post', $post_id ) ) {
			return new WP_Error(
				'post_not_accessible',
				__( 'Post is not accessible.', 'lw-seo' ),
				[ 'status' => 403 ]
			);
		}

		$schema = new Schema\Schema();
		$data   = $schema->build_graph_for_post( $post );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get breadcrumbs for a post.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_post_breadcrumbs( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'lw-seo' ),
				[ 'status' => 404 ]
			);
		}

		if ( 'publish' !== $post->post_status && ! current_user_can( 'read_post', $post_id ) ) {
			return new WP_Error(
				'post_not_accessible',
				__( 'Post is not accessible.', 'lw-seo' ),
				[ 'status' => 403 ]
			);
		}

		$breadcrumbs = new Breadcrumbs();
		$data        = $breadcrumbs->build_for_post( $post );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Build SEO data for a post.
	 *
	 * @param WP_Post $post Post object.
	 * @return array
	 */
	private function build_post_seo_data( WP_Post $post ): array {
		$title       = $this->get_post_title( $post );
		$description = $this->get_post_description( $post );
		$canonical   = $this->get_post_canonical( $post );
		$robots      = $this->get_post_robots( $post );
		$og          = $this->get_post_og( $post, $title, $description );
		$twitter     = $this->get_post_twitter( $post, $title, $description );

		return [
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
			'og'          => $og,
			'twitter'     => $twitter,
		];
	}

	/**
	 * Build SEO data for a term.
	 *
	 * @param WP_Term $term Term object.
	 * @return array
	 */
	private function build_term_seo_data( WP_Term $term ): array {
		$title       = $this->get_term_title( $term );
		$description = $this->get_term_description( $term );
		$canonical   = get_term_link( $term );
		$robots      = $this->get_default_robots();

		return [
			'title'       => $title,
			'description' => $description,
			'canonical'   => is_string( $canonical ) ? $canonical : '',
			'robots'      => $robots,
			'og'          => [
				'locale'      => get_locale(),
				'type'        => 'website',
				'title'       => $title,
				'description' => $description,
				'url'         => is_string( $canonical ) ? $canonical : '',
				'site_name'   => get_bloginfo( 'name' ),
				'image'       => $this->get_default_og_image(),
			],
			'twitter'     => [
				'card'        => 'summary_large_image',
				'title'       => $title,
				'description' => $description,
				'image'       => $this->get_default_og_image(),
			],
		];
	}

	/**
	 * Build SEO data for an author.
	 *
	 * @param WP_User $user User object.
	 * @return array
	 */
	private function build_author_seo_data( WP_User $user ): array {
		$title_template = Options::get( 'author_title', '%%author%% - %%sitename%%' );
		$title          = ReplaceVars::replace( $title_template, null, null, $user );
		$description    = get_the_author_meta( 'description', $user->ID );
		$canonical      = get_author_posts_url( $user->ID );
		$robots         = $this->get_default_robots();

		return [
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'robots'      => $robots,
			'og'          => [
				'locale'      => get_locale(),
				'type'        => 'profile',
				'title'       => $title,
				'description' => $description,
				'url'         => $canonical,
				'site_name'   => get_bloginfo( 'name' ),
				'image'       => get_avatar_url( $user->ID, [ 'size' => 512 ] ),
			],
			'twitter'     => [
				'card'        => 'summary',
				'title'       => $title,
				'description' => $description,
				'image'       => get_avatar_url( $user->ID, [ 'size' => 512 ] ),
			],
		];
	}

	/**
	 * Get SEO title for a post.
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	private function get_post_title( WP_Post $post ): string {
		// Check for custom title.
		$custom_title = Options::get_post_meta( $post->ID, 'title' );
		if ( ! empty( $custom_title ) ) {
			return ReplaceVars::replace( $custom_title, $post, null, null );
		}

		// Use template.
		$template = Options::get( 'single_title', '%%title%% - %%sitename%%' );
		return ReplaceVars::replace( $template, $post, null, null );
	}

	/**
	 * Get meta description for a post.
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	private function get_post_description( WP_Post $post ): string {
		// Check for custom description.
		$custom = Options::get_post_meta( $post->ID, 'description' );
		if ( ! empty( $custom ) ) {
			return wp_strip_all_tags( $custom );
		}

		// Auto-generate from excerpt or content.
		$text = ! empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
		$text = wp_strip_all_tags( strip_shortcodes( $text ) );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		return mb_substr( $text, 0, 160 );
	}

	/**
	 * Get canonical URL for a post.
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	private function get_post_canonical( WP_Post $post ): string {
		$custom = Options::get_post_meta( $post->ID, 'canonical' );
		if ( ! empty( $custom ) ) {
			return $custom;
		}
		return get_permalink( $post );
	}

	/**
	 * Get robots meta for a post.
	 *
	 * @param WP_Post $post Post object.
	 * @return array
	 */
	private function get_post_robots( WP_Post $post ): array {
		$robots = [];

		$noindex  = Options::get_post_meta( $post->ID, 'noindex' );
		$nofollow = Options::get_post_meta( $post->ID, 'nofollow' );

		$robots['index']  = empty( $noindex ) ? 'index' : 'noindex';
		$robots['follow'] = empty( $nofollow ) ? 'follow' : 'nofollow';

		return $robots;
	}

	/**
	 * Get default robots meta.
	 *
	 * @return array
	 */
	private function get_default_robots(): array {
		return [
			'index'  => 'index',
			'follow' => 'follow',
		];
	}

	/**
	 * Get Open Graph data for a post.
	 *
	 * @param WP_Post $post        Post object.
	 * @param string  $title       SEO title.
	 * @param string  $description SEO description.
	 * @return array
	 */
	private function get_post_og( WP_Post $post, string $title, string $description ): array {
		// Check for custom OG data.
		$og_title = Options::get_post_meta( $post->ID, 'og_title' );
		$og_desc  = Options::get_post_meta( $post->ID, 'og_description' );
		$og_image = Options::get_post_meta( $post->ID, 'og_image' );

		// Fallback to SEO title/description.
		if ( empty( $og_title ) ) {
			$og_title = $title;
		}
		if ( empty( $og_desc ) ) {
			$og_desc = $description;
		}

		// Get image.
		if ( empty( $og_image ) ) {
			if ( has_post_thumbnail( $post->ID ) ) {
				$og_image = get_the_post_thumbnail_url( $post->ID, 'large' );
			} else {
				$og_image = $this->get_default_og_image();
			}
		}

		$type = 'post' === $post->post_type ? 'article' : 'website';

		$data = [
			'locale'      => get_locale(),
			'type'        => $type,
			'title'       => $og_title,
			'description' => $og_desc,
			'url'         => get_permalink( $post ),
			'site_name'   => get_bloginfo( 'name' ),
			'image'       => $og_image,
		];

		// Add article specific data.
		if ( 'article' === $type ) {
			$data['article:published_time'] = get_the_date( 'c', $post );
			$data['article:modified_time']  = get_the_modified_date( 'c', $post );

			$author = get_userdata( $post->post_author );
			if ( $author ) {
				$data['article:author'] = $author->display_name;
			}
		}

		return $data;
	}

	/**
	 * Get Twitter Card data for a post.
	 *
	 * @param WP_Post $post        Post object.
	 * @param string  $title       SEO title.
	 * @param string  $description SEO description.
	 * @return array
	 */
	private function get_post_twitter( WP_Post $post, string $title, string $description ): array {
		// Check for custom OG data (Twitter falls back to OG).
		$tw_title = Options::get_post_meta( $post->ID, 'og_title' );
		$tw_desc  = Options::get_post_meta( $post->ID, 'og_description' );
		$tw_image = Options::get_post_meta( $post->ID, 'og_image' );

		if ( empty( $tw_title ) ) {
			$tw_title = $title;
		}
		if ( empty( $tw_desc ) ) {
			$tw_desc = $description;
		}
		if ( empty( $tw_image ) ) {
			if ( has_post_thumbnail( $post->ID ) ) {
				$tw_image = get_the_post_thumbnail_url( $post->ID, 'large' );
			} else {
				$tw_image = $this->get_default_og_image();
			}
		}

		return [
			'card'        => 'summary_large_image',
			'title'       => $tw_title,
			'description' => $tw_desc,
			'image'       => $tw_image,
		];
	}

	/**
	 * Get title for a term.
	 *
	 * @param WP_Term $term Term object.
	 * @return string
	 */
	private function get_term_title( WP_Term $term ): string {
		$taxonomy = $term->taxonomy;

		if ( 'category' === $taxonomy ) {
			$template = Options::get( 'category_title', '%%term_title%% - %%sitename%%' );
		} elseif ( 'post_tag' === $taxonomy ) {
			$template = Options::get( 'tag_title', '%%term_title%% - %%sitename%%' );
		} else {
			$template = Options::get( 'taxonomy_title', '%%term_title%% - %%sitename%%' );
		}

		return ReplaceVars::replace( $template, null, $term, null );
	}

	/**
	 * Get description for a term.
	 *
	 * @param WP_Term $term Term object.
	 * @return string
	 */
	private function get_term_description( WP_Term $term ): string {
		if ( ! empty( $term->description ) ) {
			return wp_strip_all_tags( $term->description );
		}
		return '';
	}

	/**
	 * Get default OG image.
	 *
	 * @return string
	 */
	private function get_default_og_image(): string {
		$image = Options::get( 'default_og_image', '' );
		return is_string( $image ) ? $image : '';
	}
}
