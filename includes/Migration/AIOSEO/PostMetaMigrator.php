<?php
/**
 * All in One SEO per-post data migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\AIOSEO;

use LightweightPlugins\SEO\Migration\Support\MetaWriter;
use LightweightPlugins\SEO\Migration\Support\PrimaryTermWriter;
use LightweightPlugins\SEO\Migration\Support\ResultTally;
use LightweightPlugins\SEO\Migration\Support\TextResolver;

/**
 * Copies aioseo_posts rows into LW SEO post meta (title, description,
 * canonical, Open Graph with Twitter fallbacks, noindex/nofollow) and the
 * primary terms, never overwriting filled LW SEO values.
 */
final class PostMetaMigrator {

	/**
	 * Row source.
	 *
	 * @var PostsTable
	 */
	private PostsTable $table;

	/**
	 * Row mapper.
	 *
	 * @var PostRowMapper
	 */
	private PostRowMapper $mapper;

	/**
	 * Meta writer.
	 *
	 * @var MetaWriter
	 */
	private MetaWriter $writer;

	/**
	 * Primary-term writer.
	 *
	 * @var PrimaryTermWriter
	 */
	private PrimaryTermWriter $primary;

	/**
	 * Constructor.
	 *
	 * @param PostsTable    $table   Row source.
	 * @param PostRowMapper $mapper  Row mapper.
	 * @param bool          $dry_run Whether to simulate without making changes.
	 */
	public function __construct( PostsTable $table, PostRowMapper $mapper, bool $dry_run = false ) {
		$this->table   = $table;
		$this->mapper  = $mapper;
		$this->writer  = new MetaWriter( 'post', $dry_run );
		$this->primary = new PrimaryTermWriter( Mappings::PRIMARY_TAXONOMIES, $dry_run );
	}

	/**
	 * Run the post import.
	 *
	 * @return array{migrated: int, skipped_already_present: int, skipped_no_data: int}
	 */
	public function migrate(): array {
		$tally    = new ResultTally();
		$resolver = new TextResolver();

		foreach ( $this->table->rows() as $row ) {
			$post = get_post( (int) ( $row['post_id'] ?? 0 ) );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$post_id = (int) $post->ID;
			$tally->add( $this->writer->write( $post_id, $resolver->resolve( $this->mapper->fields( $row ), $post ) ) );

			foreach ( $this->mapper->primary_terms( $row ) as $taxonomy => $term_id ) {
				$this->primary->write( $post_id, $taxonomy, $term_id );
			}
		}

		return $tally->to_array();
	}

	/**
	 * Primary-term result (after migrate()).
	 *
	 * @return array{migrated: int, taxonomies: array<string, int>}
	 */
	public function primary_terms(): array {
		return $this->primary->result();
	}
}
