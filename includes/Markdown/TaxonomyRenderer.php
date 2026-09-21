<?php
/**
 * Taxonomy Markdown Renderer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

use LightweightPlugins\SEO\Content\Eligibility;
use LightweightPlugins\SEO\Helpers\HtmlToMarkdown;
use LightweightPlugins\SEO\Helpers\Markdown\InlineRenderer;
use LightweightPlugins\SEO\Options;

/**
 * Renders taxonomy term content as markdown with YAML frontmatter.
 */
final class TaxonomyRenderer implements RendererInterface {

	/**
	 * The term object.
	 *
	 * @var \WP_Term
	 */
	private \WP_Term $term;

	/**
	 * Constructor.
	 *
	 * @param \WP_Term $term Term object.
	 */
	public function __construct( \WP_Term $term ) {
		$this->term = $term;
	}

	/**
	 * Get frontmatter data.
	 *
	 * @return array<string, mixed>
	 */
	public function frontmatter(): array {
		$data = [
			'title'      => $this->term->name,
			'url'        => get_term_link( $this->term ),
			'type'       => $this->term->taxonomy,
			'post_count' => $this->term->count,
			'language'   => get_locale(),
		];

		if ( $this->term->parent ) {
			$parent = get_term( $this->term->parent, $this->term->taxonomy );
			if ( $parent instanceof \WP_Term ) {
				$data['parent'] = $parent->name;
			}
		}

		/** This filter is documented in includes/Markdown/PostRenderer.php */
		return apply_filters( 'lw_seo_markdown_frontmatter', $data, $this->term );
	}

	/**
	 * Get markdown body.
	 *
	 * @return string
	 */
	public function body(): string {
		// Custom markdown overrides auto-generated content.
		$custom_md = Options::get_term_meta( $this->term->term_id, 'markdown_content' );
		if ( ! empty( $custom_md ) ) {
			/** This filter is documented in includes/Markdown/PostRenderer.php */
			return apply_filters( 'lw_seo_markdown_body', $custom_md, $this->term );
		}

		$body = '# ' . HtmlToMarkdown::plain_text( $this->term->name ) . "\n\n";

		// Term description.
		$description = term_description( $this->term->term_id );
		if ( ! empty( $description ) ) {
			$body .= HtmlToMarkdown::convert( $description ) . "\n";
		}

		// Recent posts in this term. Eligibility::is_ai_visible() filters out
		// noindex, ai-input=no and (via has_password) password-protected posts
		// so their titles/URLs don't leak into the term's Markdown.
		$posts = get_posts(
			[
				'numberposts'  => 20,
				'post_status'  => 'publish',
				'has_password' => false,
				'tax_query'    => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => $this->term->taxonomy,
						'field'    => 'term_id',
						'terms'    => $this->term->term_id,
					],
				],
			]
		);
		$posts = array_filter( $posts, [ Eligibility::class, 'is_ai_visible' ] );

		if ( ! empty( $posts ) ) {
			$body .= "## Posts\n\n";
			foreach ( $posts as $post ) {
				$date  = get_the_date( 'Y-m-d', $post );
				$title = HtmlToMarkdown::plain_text( get_the_title( $post ) );
				$body .= '- [' . $title . '](' . InlineRenderer::url( (string) get_permalink( $post ) ) . ') - ' . $date . "\n";
			}
			$body .= "\n";
		}

		/** This filter is documented in includes/Markdown/PostRenderer.php */
		return apply_filters( 'lw_seo_markdown_body', $body, $this->term );
	}
}
