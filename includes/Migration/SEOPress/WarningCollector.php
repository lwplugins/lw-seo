<?php
/**
 * SEOPress migration warnings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

/**
 * Reports SEOPress data the importer inspects but cannot move.
 */
final class WarningCollector {

	/**
	 * Meta source.
	 *
	 * @var MetaSource
	 */
	private MetaSource $source;

	/**
	 * Constructor.
	 *
	 * @param MetaSource $source Meta source.
	 */
	public function __construct( MetaSource $source ) {
		$this->source = $source;
	}

	/**
	 * Collect all warnings.
	 *
	 * @return array<array{code: string, severity: string, message: string}>
	 */
	public function collect(): array {
		return array_values(
			array_filter(
				[
					$this->non_migratable_warning(),
					$this->term_extras_warning(),
				]
			)
		);
	}

	/**
	 * Post meta keys with no LW SEO equivalent.
	 *
	 * @return array{code: string, severity: string, message: string}|null
	 */
	private function non_migratable_warning(): ?array {
		$found = [];
		foreach ( Mappings::NON_MIGRATABLE_META as $key ) {
			$count = $this->source->count( 'post', $key );
			if ( $count > 0 ) {
				$found[] = $key . ' (' . $count . ')';
			}
		}

		if ( empty( $found ) ) {
			return null;
		}

		return [
			'code'     => 'non_migratable',
			'severity' => 'info',
			'message'  => sprintf(
				/* translators: %s: comma-separated list of SEOPress meta keys with counts. */
				__( 'The following SEOPress meta keys have no LW SEO equivalent and will not be migrated: %s', 'lw-seo' ),
				implode( ', ', $found )
			),
		];
	}

	/**
	 * Term canonical URLs and nofollow flags (LW SEO terms have neither field).
	 *
	 * @return array{code: string, severity: string, message: string}|null
	 */
	private function term_extras_warning(): ?array {
		$count = $this->source->count( 'term', '_seopress_robots_canonical' ) + $this->source->count( 'term', '_seopress_robots_follow' );
		if ( 0 === $count ) {
			return null;
		}

		return [
			'code'     => 'term_extras',
			'severity' => 'info',
			'message'  => sprintf(
				/* translators: %d: number of term settings. */
				__( '%d SEOPress term setting(s) (canonical URL or nofollow) have no LW SEO term field and will not be migrated.', 'lw-seo' ),
				$count
			),
		];
	}
}
