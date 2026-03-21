<?php
/**
 * Post/Page Markdown Renderer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

use LightweightPlugins\SEO\Helpers\HtmlToMarkdown;

/**
 * Renders post/page content as markdown with YAML frontmatter.
 */
final class PostRenderer implements RendererInterface {

	/**
	 * The post object.
	 *
	 * @var \WP_Post
	 */
	private \WP_Post $post;

	/**
	 * Constructor.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function __construct( \WP_Post $post ) {
		$this->post = $post;
	}

	/**
	 * Get frontmatter data.
	 *
	 * @return array<string, mixed>
	 */
	public function frontmatter(): array {
		$data = [
			'title'    => get_the_title( $this->post ),
			'url'      => get_permalink( $this->post ),
			'date'     => get_the_date( 'Y-m-d', $this->post ),
			'modified' => get_the_modified_date( 'Y-m-d', $this->post ),
			'author'   => get_the_author_meta( 'display_name', $this->post->post_author ),
			'language' => get_locale(),
		];

		// Categories.
		$categories = get_the_category( $this->post->ID );
		if ( ! empty( $categories ) ) {
			$data['categories'] = array_map( fn( $cat ) => $cat->name, $categories );
		}

		// Tags.
		$tags = get_the_tags( $this->post->ID );
		if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
			$data['tags'] = array_map( fn( $tag ) => $tag->name, $tags );
		}

		// Featured image.
		$thumbnail = get_the_post_thumbnail_url( $this->post, 'large' );
		if ( $thumbnail ) {
			$data['featured_image'] = $thumbnail;
		}

		// Excerpt.
		$excerpt = get_the_excerpt( $this->post );
		if ( ! empty( $excerpt ) ) {
			$data['excerpt'] = $excerpt;
		}

		/**
		 * Filter markdown frontmatter for a post.
		 *
		 * @param array<string, mixed> $data Frontmatter key-value pairs.
		 * @param \WP_Post             $post The post object.
		 */
		return apply_filters( 'lw_seo_markdown_frontmatter', $data, $this->post );
	}

	/**
	 * Get markdown body.
	 *
	 * @return string
	 */
	public function body(): string {
		$content = apply_filters( 'the_content', $this->post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WP filter.
		$body    = '# ' . get_the_title( $this->post ) . "\n\n";
		$body   .= HtmlToMarkdown::convert( $content );

		/**
		 * Filter markdown body for a post.
		 *
		 * @param string   $body Markdown body content.
		 * @param \WP_Post $post The post object.
		 */
		return apply_filters( 'lw_seo_markdown_body', $body, $this->post );
	}
}
