<?php
/**
 * SEOPress social and knowledge-graph settings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

use LightweightPlugins\SEO\Migration\Support\OptionWriter;

/**
 * Maps seopress_social_option_name (src/Services/Options/SocialOption.php)
 * onto LW SEO options. Toggles are '1' when on and ''/absent when off.
 */
final class SocialSettings {

	/**
	 * The seopress_social_option_name option.
	 *
	 * @var array<string, mixed>
	 */
	private array $social;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $social seopress_social_option_name.
	 */
	public function __construct( array $social ) {
		$this->social = $social;
	}

	/**
	 * Apply the social settings.
	 *
	 * @param OptionWriter $writer Option writer.
	 * @return void
	 */
	public function apply( OptionWriter $writer ): void {
		if ( empty( $this->social ) ) {
			return;
		}

		$writer->set( 'opengraph_enabled', '1' === ( $this->social['seopress_social_facebook_og'] ?? '' ), 'seopress_social_facebook_og' );
		$writer->set( 'twitter_enabled', '1' === ( $this->social['seopress_social_twitter_card'] ?? '' ), 'seopress_social_twitter_card' );

		$size = $this->social['seopress_social_twitter_card_img_size'] ?? null;
		if ( in_array( $size, [ 'default', 'large' ], true ) ) {
			$writer->set( 'twitter_card_type', 'large' === $size ? 'summary_large_image' : 'summary', 'seopress_social_twitter_card_img_size' );
		}

		$writer->set( 'default_og_image', $this->text( 'seopress_social_facebook_img' ), 'seopress_social_facebook_img' );

		foreach ( Mappings::PROFILE_MAP as $key => $lw_key ) {
			$writer->set( $lw_key, $this->text( $key ), $key );
		}
		$writer->set( 'social_twitter', self::twitter_url( $this->text( 'seopress_social_accounts_twitter' ) ), 'seopress_social_accounts_twitter' );

		$this->knowledge( $writer );
	}

	/**
	 * X profile URL from SEOPress's "@handle" (LW SEO stores profile URLs).
	 *
	 * @param string|null $handle Stored handle or URL.
	 * @return string|null
	 */
	public static function twitter_url( ?string $handle ): ?string {
		if ( null === $handle ) {
			return null;
		}

		if ( preg_match( '#^https?://#i', $handle ) ) {
			return $handle;
		}

		$handle = ltrim( $handle, '@' );

		return preg_match( '/^[A-Za-z0-9_]{1,15}$/', $handle ) ? 'https://x.com/' . $handle : null;
	}

	/**
	 * Knowledge graph type, name and logo.
	 *
	 * @param OptionWriter $writer Option writer.
	 * @return void
	 */
	private function knowledge( OptionWriter $writer ): void {
		$type = $this->text( 'seopress_social_knowledge_type' );
		if ( null === $type || 'none' === strtolower( $type ) ) {
			return;
		}

		// Every non-Person type (Organization, Corporation, LocalBusiness…) is an organization.
		$writer->set( 'knowledge_type', 'Person' === $type ? 'person' : 'organization', 'seopress_social_knowledge_type' );
		$writer->set( 'knowledge_name', $this->text( 'seopress_social_knowledge_name' ), 'seopress_social_knowledge_name' );
		$writer->set( 'knowledge_logo', $this->text( 'seopress_social_knowledge_img' ), 'seopress_social_knowledge_img' );
	}

	/**
	 * A trimmed non-empty string setting, null otherwise.
	 *
	 * @param string $key Setting key.
	 * @return string|null
	 */
	private function text( string $key ): ?string {
		$value = $this->social[ $key ] ?? null;

		return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : null;
	}
}
