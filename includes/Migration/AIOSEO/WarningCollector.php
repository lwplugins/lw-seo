<?php
/**
 * All in One SEO migration warnings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\AIOSEO;

/**
 * Reports All in One SEO data the importer inspects but cannot move, so the
 * result is honest about what stays behind.
 */
final class WarningCollector {

	/**
	 * Rows with a robots setting LW SEO has no per-post option for.
	 */
	private const UNSUPPORTED_ROBOTS = 'robots_default = 0 AND (robots_noarchive = 1 OR robots_nosnippet = 1 OR robots_noimageindex = 1 OR robots_noodp = 1 OR robots_notranslate = 1)';

	/**
	 * Rows with a focus keyphrase.
	 */
	private const KEYPHRASES = "(focus_keyword <> '' OR keyphrases LIKE '%\"focus\":{\"keyphrase\":\"_%')";

	/**
	 * Rows with schema graphs added in the editor.
	 */
	private const SCHEMA = "(`schema` LIKE '%\"graphs\":[{%' OR `schema` LIKE '%\"customGraphs\":[{%')";

	/**
	 * Row source.
	 *
	 * @var PostsTable
	 */
	private PostsTable $table;

	/**
	 * Constructor.
	 *
	 * @param PostsTable $table Row source.
	 */
	public function __construct( PostsTable $table ) {
		$this->table = $table;
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
					$this->count_warning(
						'robots_extra',
						self::UNSUPPORTED_ROBOTS,
						/* translators: %d: number of posts. */
						__( '%d post(s) use All in One SEO robots settings LW SEO has no per-post option for (noarchive, nosnippet, noimageindex, noodp, notranslate). Only noindex and nofollow are imported.', 'lw-seo' )
					),
					$this->count_warning(
						'keyphrases',
						self::KEYPHRASES,
						/* translators: %d: number of posts. */
						__( '%d post(s) have All in One SEO focus keyphrases. LW SEO has no content analysis, so they are not imported.', 'lw-seo' )
					),
					$this->count_warning(
						'schema',
						self::SCHEMA,
						/* translators: %d: number of posts. */
						__( '%d post(s) have schema added in All in One SEO. LW SEO generates schema automatically and does not import per-post schema.', 'lw-seo' )
					),
					$this->pro_tables_warning(),
				]
			)
		);
	}

	/**
	 * A warning with the number of rows matching a condition, null when none.
	 *
	 * @param string $code      Warning code.
	 * @param string $condition Constant SQL condition.
	 * @param string $message   Translated message with %d.
	 * @return array{code: string, severity: string, message: string}|null
	 */
	private function count_warning( string $code, string $condition, string $message ): ?array {
		$count = $this->table->count_where( $condition );
		if ( 0 === $count ) {
			return null;
		}

		return [
			'code'     => $code,
			'severity' => 'info',
			'message'  => sprintf( $message, $count ),
		];
	}

	/**
	 * Pro tables (term SEO, redirects) are not imported.
	 *
	 * @return array{code: string, severity: string, message: string}|null
	 */
	private function pro_tables_warning(): ?array {
		$found = array_filter( Mappings::PRO_TABLES, [ PostsTable::class, 'exists' ] );
		if ( empty( $found ) ) {
			return null;
		}

		return [
			'code'     => 'pro_tables',
			'severity' => 'warning',
			'message'  => sprintf(
				/* translators: %s: comma-separated table names. */
				__( 'All in One SEO Pro tables were found (%s). Term SEO and redirects from the Pro version are not imported; re-create them in LW SEO (term fields, Redirects tab).', 'lw-seo' ),
				implode( ', ', array_map( [ PostsTable::class, 'name' ], $found ) )
			),
		];
	}
}
