<?php
/**
 * Title template and noindex options for every public taxonomy.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Meta;

use LightweightPlugins\SEO\Content\PostTypes;

/**
 * Categories and tags have fixed option defaults; every other public
 * taxonomy (WooCommerce product categories, tags and attributes, custom
 * taxonomies) gets a `title_<taxonomy>` and a `noindex_<taxonomy>` default
 * here, so the settings screen, the CLI and the migrations keep them.
 *
 * The list is read once taxonomies are registered (late on init); before
 * that it is empty.
 */
final class TaxonomyTemplates {

	/**
	 * Default title template, the same as for categories.
	 */
	public const TITLE = '%%term_title%% %%sep%% %%sitename%%';

	/**
	 * Taxonomies whose options are fixed defaults in Options.
	 */
	private const BUILT_IN = [ 'category', 'post_tag' ];

	/**
	 * Taxonomy names with dynamic options.
	 *
	 * @var array<int, string>
	 */
	private static array $taxonomies = [];

	/**
	 * Read the taxonomies after every plugin has registered its own.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', [ self::class, 'load' ], PHP_INT_MAX );
	}

	/**
	 * Read the public taxonomies.
	 *
	 * @return void
	 */
	public static function load(): void {
		self::set( array_keys( PostTypes::taxonomies() ) );
	}

	/**
	 * Set the taxonomy names (categories and tags are left out).
	 *
	 * @param array<int, string> $names Taxonomy names.
	 * @return void
	 */
	public static function set( array $names ): void {
		self::$taxonomies = array_values( array_diff( array_map( 'strval', $names ), self::BUILT_IN ) );
	}

	/**
	 * Taxonomy names with dynamic options.
	 *
	 * @return array<int, string>
	 */
	public static function taxonomies(): array {
		return self::$taxonomies;
	}

	/**
	 * Option defaults for the taxonomies.
	 *
	 * @return array<string, string|bool>
	 */
	public static function defaults(): array {
		$defaults = [];

		foreach ( self::$taxonomies as $taxonomy ) {
			$defaults[ 'title_' . $taxonomy ]   = self::TITLE;
			$defaults[ 'noindex_' . $taxonomy ] = false;
		}

		return $defaults;
	}
}
