<?php
/**
 * Head meta for non-singular views.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Meta;

use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\ReplaceVars;

/**
 * Builds and prints the head meta of the front page, the posts page and archives.
 */
final class ArchiveMeta {

	/**
	 * Tag renderer.
	 *
	 * @var TagRenderer
	 */
	private TagRenderer $renderer;

	/**
	 * Constructor.
	 *
	 * @param TagRenderer $renderer Tag renderer.
	 */
	public function __construct( TagRenderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Output meta tags for the front page.
	 *
	 * @return void
	 */
	public function output_home(): void {
		$title_template = Options::get( 'title_home' );
		$title          = ! empty( $title_template ) ? ReplaceVars::replace( $title_template ) : get_bloginfo( 'name' );
		$custom_desc    = Options::get( 'desc_home' );
		$description    = ! empty( $custom_desc ) ? $custom_desc : get_bloginfo( 'description' );
		$url            = home_url( '/' );
		$og_image       = (string) Options::get( 'default_og_image' );

		$this->renderer->render( $title, $description, $url, $title, $description, $og_image, 'website' );
	}

	/**
	 * Output meta tags for the blog posts page.
	 *
	 * The posts page is a real page, so it uses that page's own SEO meta, but
	 * its canonical must point at the posts page URL - never at the front page.
	 *
	 * @return void
	 */
	public function output_posts_page(): void {
		$page_id = ArchiveContext::posts_page_id();

		if ( $page_id <= 0 ) {
			$this->output_home();
			return;
		}

		$post = get_post( $page_id );

		if ( ! $post instanceof \WP_Post ) {
			$this->output_home();
			return;
		}

		$singular     = new SingularMeta( $this->renderer );
		$custom_title = Options::get_post_meta( $page_id, 'title' );
		$title        = ! empty( $custom_title ) ? $custom_title : get_the_title( $post );
		$description  = $singular->description( $post );

		$custom_canon = Options::get_post_meta( $page_id, 'canonical' );
		$canonical    = ! empty( $custom_canon )
			? (string) $custom_canon
			: ArchiveContext::paged_url( (string) get_permalink( $post ) );

		$custom_og_title = Options::get_post_meta( $page_id, 'og_title' );
		$og_title        = ! empty( $custom_og_title ) ? $custom_og_title : $title;
		$custom_og_desc  = Options::get_post_meta( $page_id, 'og_description' );
		$og_description  = ! empty( $custom_og_desc ) ? $custom_og_desc : $description;

		$this->renderer->render( $title, $description, $canonical, $og_title, $og_description, $singular->og_image( $post ), 'website' );
	}

	/**
	 * Output meta tags for post type archives (including the WooCommerce shop page).
	 *
	 * @return void
	 */
	public function output_post_type_archive(): void {
		$link = ArchiveContext::post_type_archive_link();

		if ( '' === $link ) {
			return;
		}

		$queried     = get_queried_object();
		$title       = $queried instanceof \WP_Post_Type ? (string) $queried->label : '';
		$description = $queried instanceof \WP_Post_Type ? (string) $queried->description : '';

		if ( '' === $title ) {
			$title = get_bloginfo( 'name' );
		}

		$og_image = (string) Options::get( 'default_og_image' );

		$this->renderer->render( $title, $description, ArchiveContext::paged_url( $link ), $title, $description, $og_image, 'website' );
	}

	/**
	 * Output meta tags for taxonomy archives.
	 *
	 * @return void
	 */
	public function output_taxonomy(): void {
		$term = get_queried_object();

		if ( ! $term instanceof \WP_Term ) {
			return;
		}

		// Check noindex: per-term meta overrides global setting.
		$noindex = Options::get_term_meta( $term->term_id, 'noindex' );
		if ( ! $noindex ) {
			$noindex = Options::get( 'noindex_' . $term->taxonomy );
		}
		if ( $noindex ) {
			$this->renderer->render_robots( [ 'noindex', 'follow' ] );
		}

		// Title: per-term meta > template > term name.
		$custom_title = Options::get_term_meta( $term->term_id, 'title' );
		$term_title   = single_term_title( '', false );
		$title        = ! empty( $custom_title ) ? $custom_title : ( ! empty( $term_title ) ? $term_title : $term->name );

		// Description: per-term meta > term description.
		$custom_desc = Options::get_term_meta( $term->term_id, 'description' );
		$term_desc   = term_description( $term->term_id );
		$description = ! empty( $custom_desc ) ? $custom_desc : ( ! empty( $term_desc ) ? wp_strip_all_tags( $term_desc ) : '' );

		$url = get_term_link( $term );

		// Social: per-term meta > defaults.
		$og_title = Options::get_term_meta( $term->term_id, 'og_title' );
		$og_title = ! empty( $og_title ) ? $og_title : $title;
		$og_desc  = Options::get_term_meta( $term->term_id, 'og_description' );
		$og_desc  = ! empty( $og_desc ) ? $og_desc : $description;
		$og_image = MetaCoerce::as_url( Options::get_term_meta( $term->term_id, 'og_image' ) );
		if ( '' === $og_image ) {
			$og_image = (string) Options::get( 'default_og_image' );
		}

		if ( is_string( $url ) ) {
			$this->renderer->render( $title, $description, $url, $og_title, $og_desc, $og_image, 'website' );
		}
	}

	/**
	 * Output meta tags for author archives.
	 *
	 * @return void
	 */
	public function output_author(): void {
		$author = get_queried_object();

		if ( ! $author instanceof \WP_User ) {
			return;
		}

		// Check noindex setting.
		if ( Options::get( 'noindex_author' ) ) {
			$this->renderer->render_robots( [ 'noindex', 'follow' ] );
		}

		$title       = $author->display_name;
		$description = get_the_author_meta( 'description', $author->ID );
		$url         = get_author_posts_url( $author->ID );
		$og_image    = (string) Options::get( 'default_og_image' );

		$this->renderer->render( $title, $description, $url, $title, $description, $og_image, 'profile' );
	}

	/**
	 * Output the robots meta tag for date archives.
	 *
	 * @return void
	 */
	public function output_date(): void {
		if ( Options::get( 'noindex_date' ) ) {
			$this->renderer->render_robots( [ 'noindex', 'follow' ] );
		}
	}
}
