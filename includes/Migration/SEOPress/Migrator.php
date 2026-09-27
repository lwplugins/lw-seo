<?php
/**
 * SEOPress migrator orchestrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

use LightweightPlugins\SEO\Migration\MigratorInterface;
use LightweightPlugins\SEO\Migration\Support\VariableLog;

/**
 * Imports SEOPress data: settings, post and term meta, primary categories and
 * redirects (SEOPress PRO redirect posts and the free per-post / per-term
 * redirects). Detection works while SEOPress is inactive: its meta and options
 * stay in the database. Output shape matches the Yoast and RankMath migrators.
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
		$log       = new VariableLog();
		$variables = new VariableConverter( $log );
		$mapper    = new MetaMapper( $variables );
		$source    = new MetaSource();

		$options   = ( new OptionsMigrator( $variables, $this->dry_run ) )->migrate();
		$posts     = new PostMetaMigrator( $source, $mapper, $this->dry_run );
		$counts    = $posts->migrate();
		$terms     = ( new TermMetaMigrator( $source, $mapper, $this->dry_run ) )->migrate();
		$redirects = ( new RedirectsMigrator( $this->dry_run ) )->migrate( ( new RedirectSource() )->entries() );

		$warnings = ( new WarningCollector( $source ) )->collect();
		$variable = $log->warning( 'SEOPress' );
		if ( null !== $variable ) {
			$warnings[] = $variable;
		}

		return [
			'dry_run'          => $this->dry_run,
			'options_migrated' => $options['count'],
			'options_details'  => $options['details'],
			'posts'            => $counts,
			'terms'            => $terms,
			'users'            => [ 'migrated' => 0 ],
			'primary_terms'    => $posts->primary_terms(),
			'redirects'        => $redirects,
			'warnings'         => $warnings,
		];
	}

	/**
	 * Detect SEOPress data.
	 *
	 * @return array<string, mixed>
	 */
	public function detect(): array {
		$source      = new MetaSource();
		$keys        = array_merge( MetaMapper::keys(), [ '_seopress_robots_primary_cat' ] );
		$has_options = ! empty( OptionsMigrator::option( 'seopress_titles_option_name' ) ) || ! empty( OptionsMigrator::option( 'seopress_social_option_name' ) );
		$post_count  = count( $source->post_ids( $keys ) );
		$term_count  = count( $source->term_ids( MetaMapper::keys() ) );
		$redirects   = ( new RedirectSource() )->count();

		return [
			'found'           => $has_options || $post_count > 0 || $term_count > 0 || $redirects > 0,
			'has_options'     => $has_options,
			'post_count'      => $post_count,
			'term_count'      => $term_count,
			'user_count'      => 0,
			'redirects_count' => $redirects,
			'warnings'        => ( new WarningCollector( $source ) )->collect(),
		];
	}
}
