<?php
/**
 * All in One SEO settings migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\AIOSEO;

use LightweightPlugins\SEO\Migration\Support\OptionWriter;
use LightweightPlugins\SEO\Migration\Support\Separator;
use LightweightPlugins\SEO\Migration\Support\TextResolver;
use LightweightPlugins\SEO\Options;

/**
 * Imports All in One SEO's global settings into lw_seo_options: separator,
 * home / post type / taxonomy / archive title templates, per-type noindex,
 * Open Graph and Twitter defaults, social profiles, knowledge graph and
 * sitemap inclusion. Settings paths: app/Common/Options/Options.php:27-528 and
 * app/Common/Options/DynamicOptions.php:192-358.
 */
final class OptionsMigrator {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Settings reader.
	 *
	 * @var SettingsReader
	 */
	private SettingsReader $settings;

	/**
	 * Smart tag converter.
	 *
	 * @var SmartTagConverter
	 */
	private SmartTagConverter $tags;

	/**
	 * Constructor.
	 *
	 * @param SettingsReader    $settings Settings reader.
	 * @param SmartTagConverter $tags     Smart tag converter.
	 * @param bool              $dry_run  Whether to simulate without making changes.
	 */
	public function __construct( SettingsReader $settings, SmartTagConverter $tags, bool $dry_run = false ) {
		$this->settings = $settings;
		$this->tags     = $tags;
		$this->dry_run  = $dry_run;
	}

	/**
	 * Run the settings import.
	 *
	 * @return array{count: int, details: array<string>}
	 */
	public function migrate(): array {
		$stored = get_option( Options::OPTION_NAME, [] );
		$writer = new OptionWriter( is_array( $stored ) ? $stored : [], Options::get_defaults() );

		if ( $this->settings->has_settings() ) {
			$this->titles( $writer );
			$this->robots( $writer );
			( new SocialSettings( $this->settings ) )->apply( $writer );
			( new SitemapSettings( $this->settings ) )->apply( $writer );
		}

		if ( ! $this->dry_run && $writer->count() > 0 ) {
			update_option( Options::OPTION_NAME, $writer->options() );
			Options::clear_cache();
		}

		return [
			'count'   => $writer->count(),
			'details' => $writer->details(),
		];
	}

	/**
	 * Separator and title / description templates.
	 *
	 * @param OptionWriter $writer Option writer.
	 * @return void
	 */
	private function titles( OptionWriter $writer ): void {
		$writer->set( 'separator', Separator::normalize( $this->settings->get( 'searchAppearance.global.separator' ) ), 'searchAppearance.global.separator' );
		$writer->set( 'title_home', $this->template( 'searchAppearance.global.siteTitle', false ), 'searchAppearance.global.siteTitle' );
		$writer->set( 'desc_home', ( new TextResolver() )->resolve_site_text( $this->template( 'searchAppearance.global.metaDescription', false ), (string) ( $writer->options()['separator'] ?? '-' ) ), 'searchAppearance.global.metaDescription' );

		foreach ( Mappings::POST_TYPES as $type ) {
			$path = 'searchAppearance.postTypes.' . $type . '.title';
			$writer->set( 'title_' . $type, $this->template( $path, true ), $path );
		}

		foreach ( Mappings::TAXONOMIES as $taxonomy ) {
			$path = 'searchAppearance.taxonomies.' . $taxonomy . '.title';
			$writer->set( 'title_' . $taxonomy, $this->template( $path, true ), $path );
		}

		foreach ( Mappings::ARCHIVES as $archive => $suffix ) {
			$path = 'searchAppearance.archives.' . $archive . '.title';
			$writer->set( 'title_' . $suffix, $this->template( $path, false ), $path );
		}

		$path = 'searchAppearance.archives.product.title';
		$writer->set( 'title_ptarchive_product', $this->template( $path, true ), $path );
	}

	/**
	 * Per-type noindex settings.
	 *
	 * @param OptionWriter $writer Option writer.
	 * @return void
	 */
	private function robots( OptionWriter $writer ): void {
		foreach ( Mappings::POST_TYPES as $type ) {
			$writer->set( 'noindex_' . $type, $this->settings->noindex( 'searchAppearance.postTypes.' . $type ), 'searchAppearance.postTypes.' . $type . '.advanced.robotsMeta' );
		}

		foreach ( Mappings::TAXONOMIES as $taxonomy ) {
			$writer->set( 'noindex_' . $taxonomy, $this->settings->noindex( 'searchAppearance.taxonomies.' . $taxonomy ), 'searchAppearance.taxonomies.' . $taxonomy . '.advanced.robotsMeta' );
		}

		foreach ( [ 'author', 'date' ] as $archive ) {
			$writer->set( 'noindex_' . $archive, $this->settings->noindex( 'searchAppearance.archives.' . $archive, false ), 'searchAppearance.archives.' . $archive . '.advanced.robotsMeta' );
		}
	}

	/**
	 * A converted title / description template, null when not stored.
	 *
	 * @param string $path    Dot path.
	 * @param bool   $dynamic Whether the path is in aioseo_options_dynamic.
	 * @return string|null
	 */
	private function template( string $path, bool $dynamic ): ?string {
		$value = $this->tags->convert( $this->settings->text( $path, $dynamic ) );

		return '' === $value ? null : $value;
	}
}
