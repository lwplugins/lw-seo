<?php
/**
 * LLMS.txt section collector.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\LlmsTxt;

use LightweightPlugins\SEO\Content\Eligibility;
use LightweightPlugins\SEO\Content\PostTypes;
use LightweightPlugins\SEO\Markdown\Url;
use LightweightPlugins\SEO\Options;

/**
 * Collects the posts listed in llms.txt, one section per post type.
 */
final class SectionCollector {

	/**
	 * Default items per section.
	 */
	public const DEFAULT_LIMIT = 100;

	/**
	 * Hard cap on items per section.
	 */
	private const MAX_LIMIT = 500;

	/**
	 * AI-visible posts grouped by section heading.
	 *
	 * @return array<string, array<int, \WP_Post>>
	 */
	public function posts(): array {
		$limit    = self::limit( Options::get( 'llms_txt_max_items' ) );
		$sections = [];

		foreach ( self::post_types() as $post_type => $heading ) {
			$posts = array_values( array_filter( $this->query( (string) $post_type, $limit ), [ Eligibility::class, 'is_ai_visible' ] ) );
			if ( [] !== $posts ) {
				$sections[ (string) $heading ] = $posts;
			}
		}

		return $sections;
	}

	/**
	 * Post types listed in llms.txt.
	 *
	 * @return array<string, string> Post type name => section heading.
	 */
	public static function post_types(): array {
		$map   = Options::get( 'llms_txt_post_types' );
		$map   = is_array( $map ) ? $map : [];
		$types = [];

		foreach ( PostTypes::post_types() as $name => $label ) {
			if ( ! array_key_exists( $name, $map ) || $map[ $name ] ) {
				$types[ $name ] = $label;
			}
		}

		/**
		 * Filter the post types listed in llms.txt.
		 *
		 * @param array<string, string> $types Post type name => section heading.
		 */
		return (array) apply_filters( 'lw_seo_llms_txt_post_types', $types );
	}

	/**
	 * Clamp the per-section limit.
	 *
	 * @param mixed $value Stored value.
	 * @return int
	 */
	public static function limit( mixed $value ): int {
		$limit = (int) $value;

		return $limit < 1 ? self::DEFAULT_LIMIT : min( $limit, self::MAX_LIMIT );
	}

	/**
	 * LLMS.txt link for a post.
	 *
	 * @param \WP_Post $post     Post object.
	 * @param bool     $markdown Link the Markdown version.
	 * @return array{title: string, url: string, description: string}
	 */
	public static function link( \WP_Post $post, bool $markdown ): array {
		$description = (string) Options::get_post_meta( (int) $post->ID, 'description' );
		if ( '' === $description ) {
			$description = (string) $post->post_excerpt;
		}

		return [
			'title'       => get_the_title( $post ),
			'url'         => $markdown ? Url::for_post( $post ) : (string) get_permalink( $post ),
			'description' => wp_trim_words( $description, 30, '…' ),
		];
	}

	/**
	 * Parse "Title | URL | optional description" lines.
	 *
	 * @param string $text Textarea content.
	 * @return array<int, array{title: string, url: string, description: string}>
	 */
	public static function parse_links( string $text ): array {
		$lines = preg_split( '/\R/', $text );
		if ( false === $lines ) {
			return [];
		}

		$links = [];
		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( '|', $line ) );
			if ( count( $parts ) < 2 || '' === $parts[0] || ! preg_match( '#^https?://#i', $parts[1] ) ) {
				continue;
			}
			$links[] = [
				'title'       => $parts[0],
				'url'         => $parts[1],
				'description' => $parts[2] ?? '',
			];
		}

		return $links;
	}

	/**
	 * Query published posts of one type (pages in menu order, others newest first).
	 *
	 * @param string $post_type Post type.
	 * @param int    $limit     Max posts.
	 * @return array<int, \WP_Post>
	 */
	private function query( string $post_type, int $limit ): array {
		return get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'has_password'   => false,
				'posts_per_page' => $limit,
				'orderby'        => is_post_type_hierarchical( $post_type )
					? [
						'menu_order' => 'ASC',
						'title'      => 'ASC',
					]
					: [ 'date' => 'DESC' ],
				'no_found_rows'  => true,
				'meta_query'     => Eligibility::noindex_meta_query(),
			]
		);
	}
}
