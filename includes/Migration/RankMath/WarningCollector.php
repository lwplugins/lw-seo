<?php
/**
 * RankMath migration warnings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\RankMath;

/**
 * Detects RankMath features that LW SEO does not (yet) have parity with, and
 * counts data that will NOT be migrated, so users see honest output instead
 * of "0 migrated" panic.
 *
 * Detected:
 *   - wc_remove_category_base / wc_remove_category_parent_slugs / wc_remove_product_base
 *     (Woo permalink rewrites — disabling RankMath breaks indexed URLs)
 *   - rank_math_schema_* post meta (custom Schema markup, no LW SEO target)
 *   - NON_MIGRATABLE_META keys (counted, reported)
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
				$this->woo_permalink_warning(),
				$this->schema_warning(),
				$this->non_migratable_warning(),
			]
		);
	}

	/**
	 * Detect breaking Woo permalink flags.
	 *
	 * @return array{code: string, severity: string, message: string}|null
	 */
	private function woo_permalink_warning(): ?array {
		$general = get_option( 'rank-math-options-general', [] );
		if ( ! is_array( $general ) ) {
			return null;
		}

		$flags = [
			'wc_remove_category_base'         => 'Remove product category base (/product-category/)',
			'wc_remove_category_parent_slugs' => 'Remove product category parent slugs',
			'wc_remove_product_base'          => 'Remove product base (/product/)',
		];

		$lw_options       = get_option( 'lw_seo_options', [] );
		$active_unmatched = [];

		foreach ( $flags as $key => $label ) {
			$rm_on = isset( $general[ $key ] ) && 'on' === $general[ $key ];
			$lw_on = is_array( $lw_options ) && ! empty( $lw_options[ $key ] );
			if ( $rm_on && ! $lw_on ) {
				$active_unmatched[] = $label;
			}
		}

		if ( empty( $active_unmatched ) ) {
			return null;
		}

		return [
			'code'     => 'woo_permalink',
			'severity' => 'warning',
			'message'  => sprintf(
				/* translators: %s: comma-separated list of RankMath WooCommerce permalink features without an LW SEO equivalent enabled. */
				__( 'RankMath has active WooCommerce permalink rewrites that LW SEO is not (yet) mirroring (%s). The migrator will copy these flags into LW SEO; running the migration applies the equivalent slug-only rules. Categories whose root slug collides with a page/reserved slug are auto-skipped to avoid 404s.', 'lw-seo' ),
				implode( ', ', $active_unmatched )
			),
		];
	}

	/**
	 * Detect rank_math_schema_* post meta — no LW SEO equivalent yet.
	 *
	 * @return array{code: string, severity: string, message: string}|null
	 */
	private function schema_warning(): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		$count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
			 WHERE meta_key LIKE 'rank_math_schema_%'"
		);

		if ( 0 === $count ) {
			return null;
		}

		return [
			'code'     => 'schema',
			'severity' => 'warning',
			'message'  => sprintf(
				/* translators: %d: number of posts with custom RankMath schema markup. */
				__( '%d post(s) have custom RankMath Schema markup (rank_math_schema_*). LW SEO generates schema automatically and does not import per-post schema overrides — you will need to re-create any custom schema in the LW SEO Schema module.', 'lw-seo' ),
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

		$keys  = Mappings::NON_MIGRATABLE_META;
		$found = [];
		foreach ( $keys as $key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Key list is a class constant.
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
				/* translators: %s: comma-separated list of RankMath meta keys with no LW SEO equivalent. */
				__( 'The following RankMath meta keys have no LW SEO equivalent and will not be migrated: %s', 'lw-seo' ),
				implode( ', ', $found )
			),
		];
	}
}
