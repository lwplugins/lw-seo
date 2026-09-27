<?php
/**
 * SEOPress term meta migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

use LightweightPlugins\SEO\Migration\Support\MetaWriter;
use LightweightPlugins\SEO\Migration\Support\ResultTally;
use LightweightPlugins\SEO\Migration\Support\TextResolver;

/**
 * Copies SEOPress term meta (same `_seopress_*` keys as posts) into LW SEO
 * term meta: title, description, Open Graph with Twitter fallbacks and
 * noindex. LW SEO terms have no canonical or nofollow field.
 */
final class TermMetaMigrator {

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
	 * Constructor.
	 *
	 * @param MetaSource $source  Meta source.
	 * @param MetaMapper $mapper  Meta mapper.
	 * @param bool       $dry_run Whether to simulate without making changes.
	 */
	public function __construct( MetaSource $source, MetaMapper $mapper, bool $dry_run = false ) {
		$this->source = $source;
		$this->mapper = $mapper;
		$this->writer = new MetaWriter( 'term', $dry_run );
	}

	/**
	 * Run the term import.
	 *
	 * @return array{migrated: int, skipped_already_present: int, skipped_no_data: int}
	 */
	public function migrate(): array {
		$tally = new ResultTally();
		$keys  = MetaMapper::keys();

		foreach ( $this->source->term_ids( $keys ) as $term_id ) {
			$term = get_term( $term_id );
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$meta   = $this->source->values( 'term', $term_id, $keys );
			$fields = ( new TextResolver() )->resolve( $this->mapper->fields( $meta, true ), null, $term );
			$tally->add( $this->writer->write( $term_id, $fields ) );
		}

		return $tally->to_array();
	}
}
