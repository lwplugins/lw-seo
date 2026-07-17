<?php
/**
 * Yoast migration warnings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

/**
 * Detects Yoast features LW SEO has no parity with, and counts data that will
 * NOT be migrated, so users see honest output instead of "0 migrated" panic.
 */
final class WarningCollector {

	/**
	 * Collect all migration warnings.
	 *
	 * @return array<array{code: string, severity: string, message: string}>
	 */
	public function collect(): array {
		return array_filter(
			[
				$this->schema_warning(),
				$this->non_migratable_warning(),
			]
		);
	}

	/**
	 * Detect per-post Yoast schema overrides — no LW SEO equivalent yet.
	 *
	 * @return array{code: string, severity: string, message: string}|null
	 */
	private function schema_warning(): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		$count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
			 WHERE meta_key LIKE '_yoast_wpseo_schema_%'"
		);

		if ( 0 === $count ) {
			return null;
		}

		return [
			'code'     => 'schema',
			'severity' => 'warning',
			'message'  => sprintf(
				/* translators: %d: number of posts with custom Yoast schema settings. */
				__( '%d post(s) have custom Yoast schema settings (page/article type). LW SEO generates schema automatically and does not import per-post schema overrides — you will need to re-create any custom schema in the LW SEO Schema module.', 'lw-seo' ),
				$count
			),
		];
	}

	/**
	 * Count NON_MIGRATABLE_META keys present in the DB.
	 *
	 * @return array{code: string, severity: string, message: string}|null
	 */
	private function non_migratable_warning(): ?array {
		global $wpdb;

		$found = [];
		foreach ( Mappings::NON_MIGRATABLE_META as $key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Key is a class constant.
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", $key ) );
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
				/* translators: %s: comma-separated list of Yoast meta keys with no LW SEO equivalent. */
				__( 'The following Yoast meta keys have no LW SEO equivalent and will not be migrated: %s', 'lw-seo' ),
				implode( ', ', $found )
			),
		];
	}
}
