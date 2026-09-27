<?php
/**
 * SEOPress settings migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

use LightweightPlugins\SEO\Migration\Support\OptionWriter;
use LightweightPlugins\SEO\Migration\Support\Separator;
use LightweightPlugins\SEO\Migration\Support\TextResolver;
use LightweightPlugins\SEO\Options;

/**
 * Imports SEOPress's global settings into lw_seo_options: from
 * seopress_titles_option_name the separator, home / post type / taxonomy /
 * archive title templates and per-type noindex (src/Services/Options/TitleOption.php);
 * social and sitemap settings through SocialSettings and SitemapSettings.
 */
final class OptionsMigrator {

	/**
	 * Whether this is a dry run.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Variable converter.
	 *
	 * @var VariableConverter
	 */
	private VariableConverter $variables;

	/**
	 * Constructor.
	 *
	 * @param VariableConverter $variables Variable converter.
	 * @param bool              $dry_run   Whether to simulate without making changes.
	 */
	public function __construct( VariableConverter $variables, bool $dry_run = false ) {
		$this->variables = $variables;
		$this->dry_run   = $dry_run;
	}

	/**
	 * Run the settings import.
	 *
	 * @return array{count: int, details: array<string>}
	 */
	public function migrate(): array {
		$stored = get_option( Options::OPTION_NAME, [] );
		$writer = new OptionWriter( is_array( $stored ) ? $stored : [], Options::get_defaults() );

		$this->titles( self::option( 'seopress_titles_option_name' ), $writer );
		( new SocialSettings( self::option( 'seopress_social_option_name' ) ) )->apply( $writer );
		( new SitemapSettings( self::option( 'seopress_xml_sitemap_option_name' ) ) )->apply( $writer );

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
	 * A stored SEOPress option array.
	 *
	 * @param string $name Option name.
	 * @return array<string, mixed>
	 */
	public static function option( string $name ): array {
		$value = get_option( $name, [] );
		return is_array( $value ) ? $value : [];
	}

	/**
	 * Separator, title templates and noindex settings.
	 *
	 * @param array<string, mixed> $titles seopress_titles_option_name.
	 * @param OptionWriter         $writer Option writer.
	 * @return void
	 */
	private function titles( array $titles, OptionWriter $writer ): void {
		if ( empty( $titles ) ) {
			return;
		}

		$writer->set( 'separator', Separator::normalize( $titles['seopress_titles_sep'] ?? null ), 'seopress_titles_sep' );
		$writer->set( 'title_home', $this->template( $titles['seopress_titles_home_site_title'] ?? null ), 'seopress_titles_home_site_title' );
		$writer->set( 'desc_home', ( new TextResolver() )->resolve_site_text( $this->template( $titles['seopress_titles_home_site_desc'] ?? null ), (string) ( $writer->options()['separator'] ?? '-' ) ), 'seopress_titles_home_site_desc' );

		$this->typed( $titles, 'seopress_titles_single_titles', Mappings::POST_TYPES, $writer );
		$this->typed( $titles, 'seopress_titles_tax_titles', Mappings::TAXONOMIES, $writer );

		$archive = $titles['seopress_titles_archive_titles']['product']['title'] ?? null;
		$writer->set( 'title_ptarchive_product', $this->template( $archive ), 'seopress_titles_archive_titles[product]' );

		foreach ( Mappings::ARCHIVE_OPTIONS as $key => $lw_key ) {
			$value = str_starts_with( $lw_key, 'noindex_' ) ? ! empty( $titles[ $key ] ) : $this->template( $titles[ $key ] ?? null );
			$writer->set( $lw_key, $value, $key );
		}
	}

	/**
	 * Per post type / taxonomy title template and noindex.
	 *
	 * @param array<string, mixed> $titles seopress_titles_option_name.
	 * @param string               $group  Settings group key.
	 * @param array<string>        $types  Types LW SEO has options for.
	 * @param OptionWriter         $writer Option writer.
	 * @return void
	 */
	private function typed( array $titles, string $group, array $types, OptionWriter $writer ): void {
		$settings = is_array( $titles[ $group ] ?? null ) ? $titles[ $group ] : [];

		foreach ( $types as $type ) {
			if ( ! is_array( $settings[ $type ] ?? null ) ) {
				continue;
			}
			$label = $group . '[' . $type . ']';
			$writer->set( 'title_' . $type, $this->template( $settings[ $type ]['title'] ?? null ), $label . '[title]' );
			$writer->set( 'noindex_' . $type, ! empty( $settings[ $type ]['noindex'] ), $label . '[noindex]' );
		}
	}

	/**
	 * A converted template, null when empty.
	 *
	 * @param mixed $value Stored template.
	 * @return string|null
	 */
	private function template( mixed $value ): ?string {
		$converted = $this->variables->convert( $value );
		return '' === $converted ? null : $converted;
	}
}
