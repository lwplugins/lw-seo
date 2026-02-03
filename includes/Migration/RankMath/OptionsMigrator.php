<?php
/**
 * RankMath Options Migrator class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\RankMath;

use LightweightPlugins\SEO\Migration\VariableConverter;
use LightweightPlugins\SEO\Options;

/**
 * Migrates RankMath options to LW SEO options.
 */
final class OptionsMigrator {

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
	 * Run options migration.
	 *
	 * @return array{count: int, details: array<string>}
	 */
	public function migrate(): array {
		$lw_options = get_option( Options::OPTION_NAME, [] );
		$rm_titles  = get_option( 'rank-math-options-titles', [] );
		$rm_general = get_option( 'rank-math-options-general', [] );
		$rm_sitemap = get_option( 'rank-math-options-sitemap', [] );

		$count   = 0;
		$details = [];

		$count += $this->migrate_title_options( $rm_titles, $lw_options, $details );
		$count += $this->migrate_general_options( $rm_general, $lw_options, $details );
		$count += $this->migrate_sitemap_options( $rm_sitemap, $lw_options, $details );
		$this->set_feature_flags( $rm_titles, $rm_sitemap, $lw_options );

		if ( ! $this->dry_run ) {
			update_option( Options::OPTION_NAME, $lw_options );
		}

		return [
			'count'   => $count,
			'details' => $details,
		];
	}

	/**
	 * Migrate title and robots options.
	 *
	 * @param array         $rm_titles  RankMath title options.
	 * @param array         $lw_options LW SEO options (by reference).
	 * @param array<string> $details    Details array (by reference).
	 * @return int Number of migrated options.
	 */
	private function migrate_title_options( array $rm_titles, array &$lw_options, array &$details ): int {
		$count = 0;

		foreach ( Mappings::TITLE_OPTIONS_MAP as $rm_key => $lw_key ) {
			if ( ! isset( $rm_titles[ $rm_key ] ) ) {
				continue;
			}

			if ( isset( $lw_options[ $lw_key ] ) && '' !== $lw_options[ $lw_key ] ) {
				continue;
			}

			$value = $rm_titles[ $rm_key ];
			$value = $this->convert_title_value( $lw_key, $value );

			$lw_options[ $lw_key ] = $value;
			$details[]             = $rm_key . ' -> ' . $lw_key;
			++$count;
		}

		return $count;
	}

	/**
	 * Convert a title/robots option value.
	 *
	 * @param string $lw_key LW SEO option key.
	 * @param mixed  $value  Original value.
	 * @return mixed
	 */
	private function convert_title_value( string $lw_key, mixed $value ): mixed {
		if ( str_contains( $lw_key, 'noindex_' ) ) {
			return is_array( $value ) && in_array( 'noindex', $value, true );
		}

		if ( str_starts_with( $lw_key, 'title_' ) || 'desc_home' === $lw_key ) {
			return VariableConverter::convert( $value );
		}

		return $value;
	}

	/**
	 * Migrate general options.
	 *
	 * @param array         $rm_general RankMath general options.
	 * @param array         $lw_options LW SEO options (by reference).
	 * @param array<string> $details    Details array (by reference).
	 * @return int Number of migrated options.
	 */
	private function migrate_general_options( array $rm_general, array &$lw_options, array &$details ): int {
		$count = 0;

		foreach ( Mappings::GENERAL_OPTIONS_MAP as $rm_key => $lw_key ) {
			if ( ! isset( $rm_general[ $rm_key ] ) ) {
				continue;
			}

			if ( isset( $lw_options[ $lw_key ] ) && '' !== $lw_options[ $lw_key ] ) {
				continue;
			}

			$value                 = $this->convert_general_value( $rm_key, $rm_general[ $rm_key ] );
			$lw_options[ $lw_key ] = $value;
			$details[]             = $rm_key . ' -> ' . $lw_key;
			++$count;
		}

		return $count;
	}

	/**
	 * Convert a general option value.
	 *
	 * @param string $rm_key RankMath option key.
	 * @param mixed  $value  Original value.
	 * @return mixed
	 */
	private function convert_general_value( string $rm_key, mixed $value ): mixed {
		if ( 'breadcrumbs' === $rm_key ) {
			return ( 'on' === $value || true === $value || '1' === $value );
		}

		if ( 'knowledgegraph_type' === $rm_key ) {
			return ( 'company' === $value ) ? 'organization' : 'person';
		}

		return $value;
	}

	/**
	 * Migrate sitemap options.
	 *
	 * @param array         $rm_sitemap RankMath sitemap options.
	 * @param array         $lw_options LW SEO options (by reference).
	 * @param array<string> $details    Details array (by reference).
	 * @return int Number of migrated options.
	 */
	private function migrate_sitemap_options( array $rm_sitemap, array &$lw_options, array &$details ): int {
		$count = 0;

		foreach ( Mappings::SITEMAP_OPTIONS_MAP as $rm_key => $lw_key ) {
			if ( ! isset( $rm_sitemap[ $rm_key ] ) ) {
				continue;
			}

			if ( isset( $lw_options[ $lw_key ] ) && '' !== $lw_options[ $lw_key ] ) {
				continue;
			}

			$value                 = ( 'on' === $rm_sitemap[ $rm_key ] || true === $rm_sitemap[ $rm_key ] || '1' === $rm_sitemap[ $rm_key ] );
			$lw_options[ $lw_key ] = $value;
			$details[]             = $rm_key . ' -> ' . $lw_key;
			++$count;
		}

		return $count;
	}

	/**
	 * Set feature flags based on RankMath data.
	 *
	 * @param array $rm_titles  RankMath title options.
	 * @param array $rm_sitemap RankMath sitemap options.
	 * @param array $lw_options LW SEO options (by reference).
	 * @return void
	 */
	private function set_feature_flags( array $rm_titles, array $rm_sitemap, array &$lw_options ): void {
		if ( ! empty( $rm_sitemap ) && ( ! isset( $lw_options['sitemap_enabled'] ) || '' === $lw_options['sitemap_enabled'] ) ) {
			$lw_options['sitemap_enabled'] = true;
		}

		if ( ! isset( $lw_options['opengraph_enabled'] ) || '' === $lw_options['opengraph_enabled'] ) {
			$lw_options['opengraph_enabled'] = true;
		}
	}
}
