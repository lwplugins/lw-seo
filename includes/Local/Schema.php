<?php
/**
 * Local Business Schema class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Local;

use LightweightPlugins\SEO\Options;

/**
 * Handles LocalBusiness Schema.org JSON-LD output.
 */
final class Schema {

	/**
	 * Days mapping for Schema.org.
	 *
	 * @var array<string, string>
	 */
	private const DAYS_MAP = [
		'monday'    => 'Monday',
		'tuesday'   => 'Tuesday',
		'wednesday' => 'Wednesday',
		'thursday'  => 'Thursday',
		'friday'    => 'Friday',
		'saturday'  => 'Saturday',
		'sunday'    => 'Sunday',
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', [ $this, 'output_schema' ], 98 );
	}

	/**
	 * Output LocalBusiness Schema.org JSON-LD.
	 *
	 * @return void
	 */
	public function output_schema(): void {
		if ( ! Options::get( 'local_enabled' ) ) {
			return;
		}

		if ( ! Options::get( 'schema_enabled' ) ) {
			return;
		}

		// Only on front page or home.
		if ( ! is_front_page() && ! is_home() ) {
			return;
		}

		$schema = $this->build_schema();

		if ( empty( $schema ) ) {
			return;
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		echo "\n<!-- LW SEO Local Business Schema -->\n";
		echo '<script type="application/ld+json">' . "\n";
		echo $json . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</script>' . "\n";
		echo "<!-- /LW SEO Local Business Schema -->\n";
	}

	/**
	 * Build LocalBusiness schema data.
	 *
	 * @return array<string, mixed>
	 */
	private function build_schema(): array {
		$business_type = Options::get( 'local_business_type', 'LocalBusiness' );
		$business_name = Options::get( 'local_business_name' );

		if ( empty( $business_name ) ) {
			$business_name = get_bloginfo( 'name' );
		}

		$schema = [
			'@context' => 'https://schema.org',
			'@type'    => $business_type,
			'@id'      => home_url( '/#localbusiness' ),
			'name'     => $business_name,
			'url'      => home_url( '/' ),
		];

		// Description.
		$description = Options::get( 'local_description' );
		if ( ! empty( $description ) ) {
			$schema['description'] = $description;
		}

		// Logo/image.
		$logo = Options::get( 'knowledge_logo' );
		if ( ! empty( $logo ) ) {
			$schema['image'] = $logo;
			$schema['logo']  = $logo;
		}

		// Price range.
		$price_range = Options::get( 'local_price_range' );
		if ( ! empty( $price_range ) ) {
			$schema['priceRange'] = $price_range;
		}

		// Address.
		$address = $this->get_address_schema();
		if ( ! empty( $address ) ) {
			$schema['address'] = $address;
		}

		// Contact.
		$phone = Options::get( 'local_phone' );
		if ( ! empty( $phone ) ) {
			$schema['telephone'] = $phone;
		}

		$email = Options::get( 'local_email' );
		if ( ! empty( $email ) ) {
			$schema['email'] = $email;
		}

		// Geo coordinates.
		$geo = $this->get_geo_schema();
		if ( ! empty( $geo ) ) {
			$schema['geo'] = $geo;
		}

		// Opening hours.
		if ( Options::get( 'local_hours_enabled' ) ) {
			$hours = $this->get_opening_hours_schema();
			if ( ! empty( $hours ) ) {
				$schema['openingHoursSpecification'] = $hours;
			}
		}

		// Social profiles.
		$same_as = $this->get_social_profiles();
		if ( ! empty( $same_as ) ) {
			$schema['sameAs'] = $same_as;
		}

		return $schema;
	}

	/**
	 * Get PostalAddress schema.
	 *
	 * @return array<string, string>|null
	 */
	private function get_address_schema(): ?array {
		$street  = Options::get( 'local_street' );
		$city    = Options::get( 'local_city' );
		$country = Options::get( 'local_country' );

		// Minimum required fields.
		if ( empty( $street ) || empty( $city ) ) {
			return null;
		}

		$address = [
			'@type' => 'PostalAddress',
		];

		// Street address.
		$street_parts = [ $street ];
		$street_2     = Options::get( 'local_street_2' );
		if ( ! empty( $street_2 ) ) {
			$street_parts[] = $street_2;
		}
		$address['streetAddress'] = implode( ', ', $street_parts );

		// City.
		$address['addressLocality'] = $city;

		// State/region.
		$state = Options::get( 'local_state' );
		if ( ! empty( $state ) ) {
			$address['addressRegion'] = $state;
		}

		// Postal code.
		$zip = Options::get( 'local_zip' );
		if ( ! empty( $zip ) ) {
			$address['postalCode'] = $zip;
		}

		// Country.
		if ( ! empty( $country ) ) {
			$address['addressCountry'] = $country;
		}

		return $address;
	}

	/**
	 * Get GeoCoordinates schema.
	 *
	 * @return array<string, mixed>|null
	 */
	private function get_geo_schema(): ?array {
		$lat = Options::get( 'local_lat' );
		$lng = Options::get( 'local_lng' );

		if ( empty( $lat ) || empty( $lng ) ) {
			return null;
		}

		return [
			'@type'     => 'GeoCoordinates',
			'latitude'  => floatval( $lat ),
			'longitude' => floatval( $lng ),
		];
	}

	/**
	 * Get OpeningHoursSpecification schema.
	 *
	 * @return array<array<string, mixed>>
	 */
	private function get_opening_hours_schema(): array {
		$hours = [];

		foreach ( self::DAYS_MAP as $day_key => $day_schema ) {
			$is_closed = Options::get( "local_hours_{$day_key}_closed", false );

			if ( $is_closed ) {
				continue;
			}

			$open  = Options::get( "local_hours_{$day_key}_open" );
			$close = Options::get( "local_hours_{$day_key}_close" );

			if ( empty( $open ) || empty( $close ) ) {
				continue;
			}

			$hours[] = [
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => $day_schema,
				'opens'     => $open,
				'closes'    => $close,
			];
		}

		return $hours;
	}

	/**
	 * Get social profile URLs.
	 *
	 * @return array<string>
	 */
	private function get_social_profiles(): array {
		$profiles = [];
		$keys     = [ 'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin', 'social_youtube' ];

		foreach ( $keys as $key ) {
			$url = Options::get( $key );
			if ( ! empty( $url ) ) {
				$profiles[] = $url;
			}
		}

		return $profiles;
	}
}
