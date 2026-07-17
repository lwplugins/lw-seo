<?php
/**
 * RankMath Migrator orchestrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\RankMath;

use LightweightPlugins\SEO\Migration\MigratorInterface;
use LightweightPlugins\SEO\WooCommerce\SlugCollisionDetector;

/**
 * Wires together all the per-area RankMath sub-migrators and surfaces
 * detection counts + warnings for the migration UI.
 */
final class Migrator implements MigratorInterface {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Constructor.
	 *
	 * @param bool $dry_run Whether to simulate without making changes.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->dry_run = $dry_run;
	}

	/**
	 * Run the full migration.
	 *
	 * @return array<string, mixed>
	 */
	public function run(): array {
		$options       = ( new OptionsMigrator( $this->dry_run ) )->migrate();
		$posts         = ( new PostMetaMigrator( $this->dry_run ) )->migrate();
		$terms         = ( new TermMetaMigrator( $this->dry_run ) )->migrate();
		$users         = ( new UserMetaMigrator( $this->dry_run ) )->migrate();
		$primary_terms = ( new PrimaryTermMigrator( $this->dry_run ) )->migrate();
		$redirects     = ( new RedirectsMigrator( $this->dry_run ) )->migrate();
		$warnings      = ( new WarningCollector() )->collect();

		if ( ! $this->dry_run ) {
			SlugCollisionDetector::invalidate();
			flush_rewrite_rules( false );
		}

		return [
			'dry_run'          => $this->dry_run,
			'options_migrated' => $options['count'],
			'options_details'  => $options['details'],
			'posts'            => $posts,
			'terms'            => $terms,
			'users'            => $users,
			'primary_terms'    => $primary_terms,
			'redirects'        => $redirects,
			'warnings'         => $warnings,
		];
	}

	/**
	 * Detect available RankMath data for migration.
	 *
	 * @return array<string, mixed>
	 */
	public function detect(): array {
		global $wpdb;

		$has_options = $this->has_any_options();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration detection.
		$post_count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
			 WHERE meta_key LIKE 'rank_math_%'
			 AND meta_key NOT LIKE 'rank_math_internal%'
			 AND meta_key NOT LIKE 'rank_math_seo_score%'
			 AND meta_key NOT LIKE 'rank_math_analytic%'"
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration detection.
		$term_count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT term_id) FROM {$wpdb->termmeta}
			 WHERE meta_key LIKE 'rank_math_%'"
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration detection.
		$user_count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta}
			 WHERE meta_key LIKE 'rank_math_%'"
		);

		$redirects_count = ( new RedirectsMigrator() )->count();
		$warnings        = ( new WarningCollector() )->collect();

		return [
			'found'           => $has_options || $post_count > 0 || $term_count > 0 || $user_count > 0 || $redirects_count > 0,
			'has_options'     => $has_options,
			'post_count'      => $post_count,
			'term_count'      => $term_count,
			'user_count'      => $user_count,
			'redirects_count' => $redirects_count,
			'warnings'        => $warnings,
		];
	}

	/**
	 * Whether any of the recognized RankMath option blobs exist.
	 *
	 * @return bool
	 */
	private function has_any_options(): bool {
		foreach ( [ 'rank-math-options-titles', 'rank-math-options-general', 'rank-math-options-sitemap' ] as $key ) {
			if ( ! empty( get_option( $key, [] ) ) ) {
				return true;
			}
		}
		return false;
	}
}
