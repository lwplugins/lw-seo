<?php
/**
 * FAQ Schema Generator.
 *
 * @package LightweightPlugins\SEO\Blocks\FAQ
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Blocks\FAQ;

/**
 * Generates FAQPage schema from FAQ blocks.
 */
final class Schema {

	/**
	 * Extract FAQ schema from post content.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array<string, mixed>|null FAQPage schema or null if no FAQs.
	 */
	public static function get_schema_for_post( \WP_Post $post ): ?array {
		$questions = self::extract_questions( $post );

		if ( empty( $questions ) ) {
			return null;
		}

		return self::build_faq_schema( $questions, get_permalink( $post ) );
	}

	/**
	 * Extract questions from post content.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array<array{title: string, content: string, id: string}>
	 */
	private static function extract_questions( \WP_Post $post ): array {
		$questions = [];

		if ( ! has_blocks( $post->post_content ) ) {
			return $questions;
		}

		$blocks = parse_blocks( $post->post_content );

		foreach ( $blocks as $block ) {
			if ( Block::NAME === $block['blockName'] ) {
				$block_questions = $block['attrs']['questions'] ?? [];

				foreach ( $block_questions as $question ) {
					if ( ! empty( $question['visible'] ) && ! empty( $question['title'] ) ) {
						$questions[] = [
							'title'   => $question['title'],
							'content' => $question['content'] ?? '',
							'id'      => $question['id'] ?? '',
						];
					}
				}
			}
		}

		return $questions;
	}

	/**
	 * Build FAQPage schema.
	 *
	 * @param array<array{title: string, content: string, id: string}> $questions Questions array.
	 * @param string                                                   $permalink Post permalink.
	 * @return array<string, mixed>
	 */
	private static function build_faq_schema( array $questions, string $permalink ): array {
		$main_entity = [];

		foreach ( $questions as $question ) {
			$url = $permalink;
			if ( ! empty( $question['id'] ) ) {
				$url .= '#' . $question['id'];
			}

			$main_entity[] = [
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $question['title'] ),
				'url'            => esc_url( $url ),
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => self::clean_answer_text( $question['content'] ),
				],
			];
		}

		return [
			'@type'      => 'FAQPage',
			'@id'        => $permalink . '#faq',
			'mainEntity' => $main_entity,
		];
	}

	/**
	 * Clean answer text for schema.
	 *
	 * @param string $text Raw text with possible HTML.
	 * @return string Cleaned text.
	 */
	private static function clean_answer_text( string $text ): string {
		// Allow basic formatting tags.
		$allowed_tags = '<p><br><strong><b><em><i><ul><ol><li><a>';
		$text         = strip_tags( $text, $allowed_tags );

		// Clean up whitespace.
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		return $text;
	}
}
