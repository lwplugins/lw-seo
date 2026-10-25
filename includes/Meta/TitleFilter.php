<?php
/**
 * Document title filtering.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Meta;

use LightweightPlugins\SEO\Content\ObjectText;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\ReplaceVars;

/**
 * Applies the configured title templates to wp_get_document_title().
 */
final class TitleFilter {

	/**
	 * Register the title filters.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'document_title_parts', [ $this, 'filter_title' ], 10, 1 );
		add_filter( 'document_title_separator', [ $this, 'filter_separator' ], 10, 1 );
	}

	/**
	 * Filter document title.
	 *
	 * @param array<string, string> $title_parts Title parts.
	 * @return array<string, string>
	 */
	public function filter_title( array $title_parts ): array {
		if ( is_front_page() ) {
			return $this->front_page_title( $title_parts );
		}

		if ( is_home() ) {
			return $this->posts_page_title( $title_parts );
		}

		if ( is_singular() ) {
			return $this->singular_title( $title_parts );
		}

		if ( is_post_type_archive() ) {
			return $this->apply_template( $title_parts, (string) Options::get( 'title_ptarchive_' . ArchiveContext::post_type() ) );
		}

		if ( is_category() || is_tag() || is_tax() ) {
			return $this->term_title( $title_parts );
		}

		if ( is_author() ) {
			$user = get_queried_object();

			if ( $user instanceof \WP_User ) {
				return $this->apply_template( $title_parts, (string) Options::get( 'title_author' ), null, null, $user );
			}

			return $title_parts;
		}

		if ( is_search() ) {
			return $this->apply_template( $title_parts, (string) Options::get( 'title_search' ) );
		}

		if ( is_404() ) {
			return $this->apply_template( $title_parts, (string) Options::get( 'title_404' ) );
		}

		return $title_parts;
	}

	/**
	 * Title for the front page: a static front page's own SEO title, else
	 * the home template.
	 *
	 * @param array<string, string> $title_parts Title parts.
	 * @return array<string, string>
	 */
	private function front_page_title( array $title_parts ): array {
		$page         = FrontPageMeta::page();
		$saved        = null !== $page ? ObjectText::saved_post( $page, 'title' ) : '';
		$custom_title = null !== $page ? ObjectText::resolve( $saved, $page ) : '';

		if ( '' !== $custom_title ) {
			return $this->custom_title( $title_parts, $custom_title, $saved );
		}

		return $this->apply_template( $title_parts, (string) Options::get( 'title_home' ) );
	}

	/**
	 * Title for the blog posts page: its own SEO title, never the front page one.
	 *
	 * @param array<string, string> $title_parts Title parts.
	 * @return array<string, string>
	 */
	private function posts_page_title( array $title_parts ): array {
		$page_id = ArchiveContext::posts_page_id();

		if ( $page_id <= 0 ) {
			return $this->apply_template( $title_parts, (string) Options::get( 'title_home' ) );
		}

		$post = get_post( $page_id );

		if ( ! $post instanceof \WP_Post ) {
			return $title_parts;
		}

		$saved        = ObjectText::saved_post( $post, 'title' );
		$custom_title = ObjectText::resolve( $saved, $post );

		if ( ! empty( $custom_title ) ) {
			return $this->custom_title( $title_parts, $custom_title, $saved );
		}

		return $this->apply_template( $title_parts, (string) Options::get( 'title_page' ), $post );
	}

	/**
	 * Title for singular views.
	 *
	 * @param array<string, string> $title_parts Title parts.
	 * @return array<string, string>
	 */
	private function singular_title( array $title_parts ): array {
		$post = get_queried_object();

		if ( ! $post instanceof \WP_Post ) {
			return $title_parts;
		}

		$saved        = ObjectText::saved_post( $post, 'title' );
		$custom_title = ObjectText::resolve( $saved, $post );

		if ( ! empty( $custom_title ) ) {
			return $this->custom_title( $title_parts, $custom_title, $saved );
		}

		return $this->apply_template( $title_parts, (string) Options::get( 'title_' . $post->post_type ), $post );
	}

	/**
	 * Title for taxonomy archives.
	 *
	 * @param array<string, string> $title_parts Title parts.
	 * @return array<string, string>
	 */
	private function term_title( array $title_parts ): array {
		$term = get_queried_object();

		if ( ! $term instanceof \WP_Term ) {
			return $title_parts;
		}

		$saved        = ObjectText::saved_term( $term, 'title' );
		$custom_title = ObjectText::resolve( $saved, null, $term );

		if ( ! empty( $custom_title ) ) {
			return $this->custom_title( $title_parts, $custom_title, $saved );
		}

		return $this->apply_template( $title_parts, (string) Options::get( 'title_' . $term->taxonomy ), null, $term );
	}

	/**
	 * Replace the title with a custom SEO title: it is the whole title, so the
	 * site name / tagline suffix is dropped, the same way a template drops it.
	 *
	 * @param array<string, string> $title_parts Title parts.
	 * @param string                $title       The custom title, variables filled in.
	 * @param string                $saved       The custom title as saved.
	 * @return array<string, string>
	 */
	private function custom_title( array $title_parts, string $title, string $saved ): array {
		$title_parts['title'] = $title;

		return $this->drop_suffixes( $title_parts, $saved );
	}

	/**
	 * Replace the title with a rendered template, dropping the site suffix.
	 *
	 * @param array<string, string> $title_parts Title parts.
	 * @param string                $template    The configured template, possibly empty.
	 * @param \WP_Post|null         $post        Post context for the replacement vars.
	 * @param \WP_Term|null         $term        Term context for the replacement vars.
	 * @param \WP_User|null         $user        User context for the replacement vars.
	 * @return array<string, string>
	 */
	private function apply_template(
		array $title_parts,
		string $template,
		?\WP_Post $post = null,
		?\WP_Term $term = null,
		?\WP_User $user = null
	): array {
		if ( '' === $template ) {
			return $title_parts;
		}

		$title_parts['title'] = ReplaceVars::replace( $template, $post, $term, $user );

		return $this->drop_suffixes( $title_parts, $template );
	}

	/**
	 * Drop the parts WordPress adds after the title: the site name and
	 * tagline always, the "Page 2" part when the title prints the page number
	 * itself.
	 *
	 * @param array<string, string> $title_parts Title parts.
	 * @param string                $source      The title as written, variables unresolved.
	 * @return array<string, string>
	 */
	private function drop_suffixes( array $title_parts, string $source ): array {
		unset( $title_parts['site'], $title_parts['tagline'] );

		if ( 1 === preg_match( '/%%page(number)?%%/', $source ) ) {
			unset( $title_parts['page'] );
		}

		return $title_parts;
	}

	/**
	 * Filter document title separator.
	 *
	 * @param string $sep Default separator.
	 * @return string
	 */
	public function filter_separator( string $sep ): string {
		$custom_sep = Options::get( 'separator' );

		if ( ! empty( $custom_sep ) ) {
			return $custom_sep;
		}

		return $sep;
	}
}
