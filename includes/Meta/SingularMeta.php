<?php
/**
 * Head meta for singular posts, pages and custom post types.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Meta;

use LightweightPlugins\SEO\Content\PostDescription;
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
		$descriptions = PostDescription::for_head( $post );
		$canonical    = Canonical::for_post( $post );

		// Get OG specific values.
		$custom_og_title = Options::get_post_meta( $post->ID, 'og_title' );
		$og_title        = ! empty( $custom_og_title ) ? $custom_og_title : $title;
		$og_image        = $this->og_image( $post );

		$this->renderer->render( $title, $descriptions['meta'], $canonical, $og_title, $descriptions['og'], $og_image, 'article', $descriptions['twitter'] );
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
