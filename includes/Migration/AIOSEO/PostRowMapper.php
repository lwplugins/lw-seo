<?php
/**
 * Maps one aioseo_posts row to LW SEO fields.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\AIOSEO;

/**
 * Pure mapping of an {prefix}aioseo_posts row to LW SEO post fields.
 *
 * Rules (All in One SEO 5.0.2):
 *   - empty text columns are NULL or '' (Models/Post.php:765); both mean "not set"
 *   - the user-chosen image is og_image_custom_url only when og_image_type is
 *     'custom_image'; og_image_url is a computed cache (Social/Image.php:98-110,
 *     Models/Post.php:885-907), so it is never imported
 *   - twitter_use_og = 1 means Twitter reuses the Open Graph data
 *     (Social/Twitter.php:111-113); otherwise its own values act as fallbacks
 *     for the og_* targets, because LW SEO renders Twitter Cards from them
 *   - robots_* columns only apply when robots_default is 0 (Meta/Robots.php:205-219)
 *   - primary_term is JSON {"taxonomy": term_id}
 */
final class PostRowMapper {

	/**
	 * Smart tag converter.
	 *
	 * @var SmartTagConverter
	 */
	private SmartTagConverter $tags;

	/**
	 * Constructor.
	 *
	 * @param SmartTagConverter $tags Smart tag converter.
	 */
	public function __construct( SmartTagConverter $tags ) {
		$this->tags = $tags;
	}

	/**
	 * LW SEO fields (without prefix) of a row, in write order.
	 *
	 * @param array<string, mixed> $row Table row.
	 * @return array<string, string>
	 */
	public function fields( array $row ): array {
		$fields = [
			'title'          => $this->tags->convert( $row['title'] ?? '' ),
			'description'    => $this->tags->convert( $row['description'] ?? '' ),
			'canonical'      => $this->text( $row['canonical_url'] ?? '' ),
			'og_title'       => $this->tags->convert( $row['og_title'] ?? '' ),
			'og_description' => $this->tags->convert( $row['og_description'] ?? '' ),
			'og_image'       => $this->image( $row, 'og' ),
		];

		if ( empty( $row['twitter_use_og'] ) ) {
			$fallbacks = [
				'og_title'       => $this->tags->convert( $row['twitter_title'] ?? '' ),
				'og_description' => $this->tags->convert( $row['twitter_description'] ?? '' ),
				'og_image'       => $this->image( $row, 'twitter' ),
			];
			foreach ( $fallbacks as $field => $value ) {
				if ( '' === $fields[ $field ] ) {
					$fields[ $field ] = $value;
				}
			}
		}

		return array_merge( $fields, $this->robots( $row ) );
	}

	/**
	 * Explicit noindex / nofollow flags ('1'), only when the row overrides the defaults.
	 *
	 * @param array<string, mixed> $row Table row.
	 * @return array<string, string>
	 */
	public function robots( array $row ): array {
		if ( ! array_key_exists( 'robots_default', $row ) || ! empty( $row['robots_default'] ) ) {
			return [];
		}

		return array_filter(
			[
				'noindex'  => empty( $row['robots_noindex'] ) ? '' : '1',
				'nofollow' => empty( $row['robots_nofollow'] ) ? '' : '1',
			]
		);
	}

	/**
	 * Primary terms: taxonomy => term ID.
	 *
	 * @param array<string, mixed> $row Table row.
	 * @return array<string, int>
	 */
	public function primary_terms( array $row ): array {
		$raw = $row['primary_term'] ?? '';
		if ( ! is_string( $raw ) || '' === $raw ) {
			return [];
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return [];
		}

		$terms = [];
		foreach ( $decoded as $taxonomy => $term_id ) {
			if ( is_string( $taxonomy ) && is_numeric( $term_id ) && (int) $term_id > 0 ) {
				$terms[ $taxonomy ] = (int) $term_id;
			}
		}

		return $terms;
	}

	/**
	 * User-chosen image URL of the OG or Twitter block.
	 *
	 * @param array<string, mixed> $row    Table row.
	 * @param string               $prefix 'og' or 'twitter'.
	 * @return string
	 */
	private function image( array $row, string $prefix ): string {
		if ( 'custom_image' !== ( $row[ $prefix . '_image_type' ] ?? '' ) ) {
			return '';
		}

		return $this->text( $row[ $prefix . '_image_custom_url' ] ?? '' );
	}

	/**
	 * A trimmed string, '' for NULL and non-strings.
	 *
	 * @param mixed $value Column value.
	 * @return string
	 */
	private function text( mixed $value ): string {
		return is_string( $value ) ? trim( $value ) : '';
	}
}
