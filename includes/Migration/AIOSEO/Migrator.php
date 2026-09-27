<?php
/**
 * All in One SEO migrator orchestrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\AIOSEO;

use LightweightPlugins\SEO\Migration\MigratorInterface;
use LightweightPlugins\SEO\Migration\Support\VariableLog;

/**
 * Imports All in One SEO data: settings from aioseo_options(_dynamic) and
 * per-post data from {prefix}aioseo_posts. The free plugin stores no term SEO
 * and has no redirects, so those buckets stay empty. Detection works while
 * All in One SEO is inactive: only its tables and options are read.
 * Output shape matches the Yoast and RankMath migrators.
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
	 * Run the import.
	 *
	 * @return array<string, mixed>
	 */
	public function run(): array {
		$log   = new VariableLog();
		$tags  = new SmartTagConverter( $log );
		$table = new PostsTable();

		$options = ( new OptionsMigrator( SettingsReader::from_site(), $tags, $this->dry_run ) )->migrate();
		$posts   = new PostMetaMigrator( $table, new PostRowMapper( $tags ), $this->dry_run );
		$counts  = $posts->migrate();

		$warnings = ( new WarningCollector( $table ) )->collect();
		$variable = $log->warning( 'All in One SEO' );
		if ( null !== $variable ) {
			$warnings[] = $variable;
		}

		return [
			'dry_run'          => $this->dry_run,
			'options_migrated' => $options['count'],
			'options_details'  => $options['details'],
			'posts'            => $counts,
			'terms'            => self::empty_counts(),
			'users'            => [ 'migrated' => 0 ],
			'primary_terms'    => $posts->primary_terms(),
			'redirects'        => [
				'migrated' => 0,
				'skipped'  => 0,
				'errors'   => [],
			],
			'warnings'         => $warnings,
		];
	}

	/**
	 * Detect All in One SEO data.
	 *
	 * @return array<string, mixed>
	 */
	public function detect(): array {
		$table       = new PostsTable();
		$has_options = SettingsReader::from_site()->has_settings();
		$post_count  = $table->count_with_data();

		return [
			'found'           => $has_options || $post_count > 0,
			'has_options'     => $has_options,
			'post_count'      => $post_count,
			'term_count'      => 0,
			'user_count'      => 0,
			'redirects_count' => 0,
			'warnings'        => ( new WarningCollector( $table ) )->collect(),
		];
	}

	/**
	 * Zero per-object counts.
	 *
	 * @return array{migrated: int, skipped_already_present: int, skipped_no_data: int}
	 */
	private static function empty_counts(): array {
		return [
			'migrated'                => 0,
			'skipped_already_present' => 0,
			'skipped_no_data'         => 0,
		];
	}
}
