<?php
/**
 * Reads SEOPress redirect data.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

/**
 * Collects SEOPress redirects as neutral entries: SEOPress PRO redirect posts
 * (post type seopress_404) and the free per-post / per-term redirects.
 * Read-only; works while SEOPress is inactive.
 */
final class RedirectSource {

	/**
	 * Per-object redirect meta keys.
	 */
	private const ENABLED = '_seopress_redirections_enabled';

	/**
	 * All redirect entries.
	 *
	 * @return array<array{source: string, destination: string, type: int, regex: bool, enabled: bool, logged_status: string, label: string}>
	 */
	public function entries(): array {
		return array_merge( $this->pro_entries(), $this->post_entries(), $this->term_entries() );
	}

	/**
	 * Number of redirect entries (for detection).
	 *
	 * @return int
	 */
	public function count(): int {
		return count( array_filter( $this->entries(), [ RedirectsMigrator::class, 'importable' ] ) );
	}

	/**
	 * SEOPress PRO redirect posts that carry a redirect type (404 log rows have none).
	 *
	 * @return array<array{source: string, destination: string, type: int, regex: bool, enabled: bool, logged_status: string, label: string}>
	 */
	private function pro_entries(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query; works without SEOPress PRO registering its post type.
		$rows = (array) $wpdb->get_results(
			"SELECT p.ID, p.post_title FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = '_seopress_redirections_type' AND t.meta_value <> ''
			 WHERE p.post_type = 'seopress_404' AND p.post_status = 'publish' ORDER BY p.ID",
			ARRAY_A
		);

		$entries = [];
		foreach ( $rows as $row ) {
			$id        = (int) $row['ID'];
			$regex     = get_post_meta( $id, '_seopress_redirections_enabled_regex', true );
			$entries[] = $this->entry( 'post', $id, (string) $row['post_title'], 'yes' === $regex, sprintf( 'seopress_404 #%d', $id ) );
		}

		return $entries;
	}

	/**
	 * Free per-post redirects (published posts only; their URL is the source).
	 *
	 * @return array<array{source: string, destination: string, type: int, regex: bool, enabled: bool, logged_status: string, label: string}>
	 */
	private function post_entries(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		$ids = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s AND m.meta_value = 'yes'
				 WHERE p.post_type <> 'seopress_404' AND p.post_status = 'publish' ORDER BY p.ID",
				self::ENABLED
			)
		);

		$entries = [];
		foreach ( $ids as $id ) {
			$url       = get_permalink( (int) $id );
			$entries[] = $this->entry( 'post', (int) $id, is_string( $url ) ? $url : '', false, sprintf( 'post #%d', (int) $id ) );
		}

		return $entries;
	}

	/**
	 * Free per-term redirects (the term archive URL is the source).
	 *
	 * @return array<array{source: string, destination: string, type: int, regex: bool, enabled: bool, logged_status: string, label: string}>
	 */
	private function term_entries(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		$ids = (array) $wpdb->get_col( $wpdb->prepare( "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = %s AND meta_value = 'yes' ORDER BY term_id", self::ENABLED ) );

		$entries = [];
		foreach ( $ids as $id ) {
			$url       = get_term_link( (int) $id );
			$entries[] = $this->entry( 'term', (int) $id, is_string( $url ) ? $url : '', false, sprintf( 'term #%d', (int) $id ) );
		}

		return $entries;
	}

	/**
	 * Build one entry from an object's redirect meta.
	 *
	 * @param string $type   'post' or 'term'.
	 * @param int    $id     Object ID.
	 * @param string $source Source path, URL or pattern.
	 * @param bool   $regex  Whether the source is a pattern.
	 * @param string $label  Label for error reporting.
	 * @return array{source: string, destination: string, type: int, regex: bool, enabled: bool, logged_status: string, label: string}
	 */
	private function entry( string $type, int $id, string $source, bool $regex, string $label ): array {
		$get = 'term' === $type ? 'get_term_meta' : 'get_post_meta';

		return [
			'source'        => $regex ? $source : self::decode( $source ),
			'destination'   => self::decode( (string) $get( $id, '_seopress_redirections_value', true ) ),
			'type'          => (int) $get( $id, '_seopress_redirections_type', true ),
			'regex'         => $regex,
			'enabled'       => 'yes' === $get( $id, self::ENABLED, true ),
			'logged_status' => (string) $get( $id, '_seopress_redirections_logged_status', true ),
			'label'         => $label,
		];
	}

	/**
	 * SEOPress stores some URLs HTML-escaped (it decodes them before redirecting).
	 *
	 * @param string $value Stored value.
	 * @return string
	 */
	private static function decode( string $value ): string {
		return trim( html_entity_decode( $value, ENT_QUOTES | ENT_HTML401, 'UTF-8' ) );
	}
}
