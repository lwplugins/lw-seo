<?php
/**
 * Head meta for singular posts, pages and custom post types.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Meta;

use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\Options;

/**
 * Builds and prints the head meta of a single post object.
 */
final class SingularMeta {

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
	 * Output meta tags for the queried singular object.
	 *
	 * @return void
	 */
	public function output(): void {
		$post = get_queried_object();

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$this->render_robots( $post );

		// Get meta values.
		$custom_title = Options::get_post_meta( $post->ID, 'title' );
		$title        = ! empty( $custom_title ) ? $custom_title : get_the_title( $post );
		$description  = $this->description( $post );
		$custom_canon = Options::get_post_meta( $post->ID, 'canonical' );
		$canonical    = ! empty( $custom_canon ) ? $custom_canon : get_permalink( $post );

		// Get OG specific values.
		$custom_og_title = Options::get_post_meta( $post->ID, 'og_title' );
		$og_title        = ! empty( $custom_og_title ) ? $custom_og_title : $title;
		$custom_og_desc  = Options::get_post_meta( $post->ID, 'og_description' );
		$og_description  = ! empty( $custom_og_desc ) ? $custom_og_desc : $description;
		$og_image        = $this->og_image( $post );

		$this->renderer->render( $title, $description, $canonical, $og_title, $og_description, $og_image, 'article' );
	}

	/**
	 * Print the robots meta tag for a post.
	 *
	 * @param \WP_Post $post The post object.
	 * @return void
	 */
	private function render_robots( \WP_Post $post ): void {
		$noindex  = Options::get_post_meta( $post->ID, 'noindex' ) || Options::get( 'noindex_' . $post->post_type );
		$nofollow = Options::get_post_meta( $post->ID, 'nofollow' );

		$robots = [];
		if ( $noindex ) {
			$robots[] = 'noindex';
		}
		if ( $nofollow ) {
			$robots[] = 'nofollow';
		}

		$this->renderer->render_robots( $robots );
	}

	/**
	 * Get meta description for a post.
	 *
	 * @param \WP_Post $post The post object.
	 * @return string
	 */
	public function description( \WP_Post $post ): string {
		$description = Options::get_post_meta( $post->ID, 'description' );

		if ( empty( $description ) ) {
			$description = ! empty( $post->post_excerpt ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
		}

		return wp_strip_all_tags( $description );
	}

	/**
	 * Get Open Graph image for a post.
	 *
	 * @param \WP_Post $post The post object.
	 * @return string Image URL.
	 */
	public function og_image( \WP_Post $post ): string {
		$og_image = MetaCoerce::as_url( Options::get_post_meta( $post->ID, 'og_image' ) );

		if ( '' !== $og_image ) {
			return $og_image;
		}

		if ( has_post_thumbnail( $post ) ) {
			$thumbnail = get_the_post_thumbnail_url( $post, 'large' );
			if ( $thumbnail ) {
				return $thumbnail;
			}
		}

		// Fallback to default OG image.
		$default_image = Options::get( 'default_og_image' );
		if ( is_string( $default_image ) && '' !== $default_image ) {
			return $default_image;
		}

		return '';
	}
}
