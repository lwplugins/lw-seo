<?php
/**
 * All in One SEO social and knowledge-graph settings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\AIOSEO;

use LightweightPlugins\SEO\Migration\Support\OptionWriter;

/**
 * Maps All in One SEO's social settings (app/Common/Options/Options.php:212-285)
 * and knowledge graph (Options.php:293-311) onto LW SEO options.
 */
final class SocialSettings {

	/**
	 * Settings reader.
	 *
	 * @var SettingsReader
	 */
	private SettingsReader $settings;

	/**
	 * Constructor.
	 *
	 * @param SettingsReader $settings Settings reader.
	 */
	public function __construct( SettingsReader $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Apply the social settings.
	 *
	 * @param OptionWriter $writer Option writer.
	 * @return void
	 */
	public function apply( OptionWriter $writer ): void {
		$this->flag( $writer, 'opengraph_enabled', 'social.facebook.general.enable' );
		$this->flag( $writer, 'twitter_enabled', 'social.twitter.general.enable' );

		$card = $this->settings->get( 'social.twitter.general.defaultCardType' );
		if ( in_array( $card, [ 'summary', 'summary_large_image' ], true ) ) {
			$writer->set( 'twitter_card_type', $card, 'social.twitter.general.defaultCardType' );
		}

		// The default image only applies when the default source is the image itself.
		if ( 'default' === $this->settings->get( 'social.facebook.general.defaultImageSourcePosts', 'default' ) ) {
			$writer->set( 'default_og_image', $this->url( $this->settings->get( 'social.facebook.general.defaultImagePosts' ) ), 'social.facebook.general.defaultImagePosts' );
		}

		foreach ( Mappings::PROFILE_MAP as $key => $lw_key ) {
			$path = 'social.profiles.urls.' . $key;
			$writer->set( $lw_key, $this->url( $this->settings->get( $path ) ), $path );
		}

		$this->knowledge( $writer );
	}

	/**
	 * Knowledge graph identity (organization or person, name, logo).
	 *
	 * @param OptionWriter $writer Option writer.
	 * @return void
	 */
	private function knowledge( OptionWriter $writer ): void {
		$base = 'searchAppearance.global.schema.';
		$type = $this->settings->get( $base . 'siteRepresents' );
		if ( ! in_array( $type, [ 'organization', 'person' ], true ) ) {
			return;
		}

		$writer->set( 'knowledge_type', $type, $base . 'siteRepresents' );

		$name_key = 'person' === $type ? 'personName' : 'organizationName';
		$logo_key = 'person' === $type ? 'personLogo' : 'organizationLogo';

		$name = $this->settings->text( $base . $name_key );
		// "#site_title" (the default) means "the site name", which is LW SEO's default too.
		if ( is_string( $name ) && ! str_contains( $name, '#' ) ) {
			$writer->set( 'knowledge_name', trim( $name ), $base . $name_key );
		}

		$writer->set( 'knowledge_logo', $this->url( $this->settings->get( $base . $logo_key ) ), $base . $logo_key );
	}

	/**
	 * Import a boolean setting when stored.
	 *
	 * @param OptionWriter $writer Option writer.
	 * @param string       $key    LW SEO option key.
	 * @param string       $path   Source dot path.
	 * @return void
	 */
	private function flag( OptionWriter $writer, string $key, string $path ): void {
		$value = $this->settings->get( $path );
		if ( null !== $value ) {
			$writer->set( $key, (bool) $value, $path );
		}
	}

	/**
	 * A stored URL, null when empty or not a string.
	 *
	 * @param mixed $value Stored value.
	 * @return string|null
	 */
	private function url( mixed $value ): ?string {
		return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : null;
	}
}
