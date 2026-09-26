<?php
/**
 * Post/Page Markdown Renderer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

use LightweightPlugins\SEO\Content\PostDescription;
use LightweightPlugins\SEO\Helpers\HtmlToMarkdown;
use LightweightPlugins\SEO\Options;

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
			'author'   => get_the_author_meta( 'display_name', (int) $this->post->post_author ),
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

		// Manual excerpt or SEO description only: an automatic excerpt would
		// run the_content a second time.
		$excerpt = PostDescription::manual_excerpt( $this->post );
		if ( '' === $excerpt ) {
			$excerpt = (string) Options::get_post_meta( (int) $this->post->ID, 'description' );
		}
		$excerpt = PostDescription::filter( wp_strip_all_tags( $excerpt ), $this->post, 'markdown' );
		if ( '' !== $excerpt ) {
			$data['excerpt'] = $excerpt;
		}

		/**
		 * Filter markdown frontmatter for a post, product or term.
		 *
		 * The second argument is a \WP_Term when a category, tag or other
		 * term archive is rendered (TaxonomyRenderer), so don't type-hint it
		 * as \WP_Post.
		 *
		 * @param array<string, mixed> $data   Frontmatter key-value pairs.
		 * @param \WP_Post|\WP_Term    $object The post (or product) or the term being rendered.
		 */
		return apply_filters( 'lw_seo_markdown_frontmatter', $data, $this->post );
	}

	/**
	 * Get markdown body.
	 *
	 * @return string
	 */
	public function body(): string {
		// Custom markdown overrides auto-generated content.
		$custom_md = Options::get_post_meta( $this->post->ID, 'markdown_content' );
		if ( ! empty( $custom_md ) ) {
			/** This filter is documented below. */
			return apply_filters( 'lw_seo_markdown_body', $custom_md, $this->post );
		}

		$content = ContentSource::html( $this->post );
		$body    = '# ' . HtmlToMarkdown::plain_text( get_the_title( $this->post ) ) . "\n\n";
		$body   .= HtmlToMarkdown::convert( $content );

		/**
		 * Filter markdown body for a post, product or term.
		 *
		 * The second argument is a \WP_Term when a category, tag or other
		 * term archive is rendered (TaxonomyRenderer), so don't type-hint it
		 * as \WP_Post.
		 *
		 * @param string            $body   Markdown body content.
		 * @param \WP_Post|\WP_Term $object The post (or product) or the term being rendered.
		 */
		return apply_filters( 'lw_seo_markdown_body', $body, $this->post );
	}
}
