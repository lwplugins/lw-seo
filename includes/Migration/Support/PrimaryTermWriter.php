<?php
/**
 * Writes imported primary-term selections.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Support;

use LightweightPlugins\SEO\Options;

/**
 * Stores a post's primary term as `_lw_seo_primary_{taxonomy}` (the same key
 * the Yoast and RankMath importers use), never overwriting an existing one,
 * and keeps the per-taxonomy counts for the result.
 */
final class PrimaryTermWriter {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * LW field (primary_{taxonomy}) => migrated count.
	 *
	 * @var array<string, int>
	 */
	private array $taxonomies = [];

	/**
	 * Constructor.
	 *
	 * @param array<string> $taxonomies Taxonomies to report (all start at 0).
	 * @param bool          $dry_run    Whether to simulate without making changes.
	 */
	public function __construct( array $taxonomies, bool $dry_run = false ) {
		$this->dry_run = $dry_run;
		foreach ( $taxonomies as $taxonomy ) {
			$this->taxonomies[ 'primary_' . $taxonomy ] = 0;
		}
	}

	/**
	 * Store one primary term.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @param mixed  $term_id  Source term ID ('', 'none', '0' and non-numbers are ignored).
	 * @return bool Whether a value was (or would be) written.
	 */
	public function write( int $post_id, string $taxonomy, mixed $term_id ): bool {
		$field = 'primary_' . $taxonomy;
		if ( ! array_key_exists( $field, $this->taxonomies ) || ! is_numeric( $term_id ) || (int) $term_id <= 0 ) {
			return false;
		}

		$key      = Options::META_PREFIX . $field;
		$existing = get_post_meta( $post_id, $key, true );
		if ( '' !== $existing && false !== $existing ) {
			return false;
		}

		if ( ! $this->dry_run ) {
			update_post_meta( $post_id, $key, (int) $term_id );
		}
		++$this->taxonomies[ $field ];

		return true;
	}

	/**
	 * Result in the shape the migration UI expects.
	 *
	 * @return array{migrated: int, taxonomies: array<string, int>}
	 */
	public function result(): array {
		return [
			'migrated'   => array_sum( $this->taxonomies ),
			'taxonomies' => $this->taxonomies,
		];
	}
}
