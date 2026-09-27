<?php
/**
 * SEOPress post meta migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

use LightweightPlugins\SEO\Migration\Support\MetaWriter;
use LightweightPlugins\SEO\Migration\Support\PrimaryTermWriter;
use LightweightPlugins\SEO\Migration\Support\ResultTally;
use LightweightPlugins\SEO\Migration\Support\TextResolver;

/**
 * Copies SEOPress post meta into LW SEO post meta (title, description,
 * canonical, Open Graph with Twitter fallbacks, noindex/nofollow) and the
 * primary category, never overwriting filled LW SEO values.
 */
final class PostMetaMigrator {

	/**
	 * Primary category meta key (term ID; '', 'none' or '0' = unset).
	 */
	private const PRIMARY_KEY = '_seopress_robots_primary_cat';

	/**
	 * Meta source.
	 *
	 * @var MetaSource
	 */
	private MetaSource $source;

	/**
	 * Meta mapper.
	 *
	 * @var MetaMapper
	 */
	private MetaMapper $mapper;

	/**
	 * Meta writer.
	 *
	 * @var MetaWriter
	 */
	private MetaWriter $writer;

	/**
	 * Variable resolver for per-post text.
	 *
	 * @var TextResolver
	 */
	private TextResolver $resolver;

	/**
	 * Primary-term writer.
	 *
	 * @var PrimaryTermWriter
	 */
	private PrimaryTermWriter $primary;

	/**
	 * Constructor.
	 *
	 * @param MetaSource $source  Meta source.
	 * @param MetaMapper $mapper  Meta mapper.
	 * @param bool       $dry_run Whether to simulate without making changes.
	 */
	public function __construct( MetaSource $source, MetaMapper $mapper, bool $dry_run = false ) {
		$this->source   = $source;
		$this->mapper   = $mapper;
		$this->writer   = new MetaWriter( 'post', $dry_run );
		$this->resolver = new TextResolver();
		$this->primary  = new PrimaryTermWriter( [ 'category', 'product_cat' ], $dry_run );
	}

	/**
	 * Run the post import.
	 *
	 * @return array{migrated: int, skipped_already_present: int, skipped_no_data: int}
	 */
	public function migrate(): array {
		$tally = new ResultTally();
		$keys  = MetaMapper::keys();

		foreach ( $this->source->post_ids( array_merge( $keys, [ self::PRIMARY_KEY ] ) ) as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$meta   = $this->source->values( 'post', $post_id, $keys );
			$fields = $this->resolver->resolve( $this->mapper->fields( $meta ), $post );
			$tally->add( $this->writer->write( $post_id, $fields ) );

			$taxonomy = 'product' === $post->post_type ? 'product_cat' : 'category';
			$this->primary->write( $post_id, $taxonomy, get_post_meta( $post_id, self::PRIMARY_KEY, true ) );
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
