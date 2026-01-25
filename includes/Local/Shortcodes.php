<?php
/**
 * Local SEO Shortcodes class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Local;

use LightweightPlugins\SEO\Options;

/**
 * Handles Local SEO shortcodes.
 */
final class Shortcodes {

	/**
	 * Days of the week (lazy loaded).
	 *
	 * @var array<string, string>|null
	 */
	private ?array $days = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'lw_address', [ $this, 'address_shortcode' ] );
		add_shortcode( 'lw_phone', [ $this, 'phone_shortcode' ] );
		add_shortcode( 'lw_email', [ $this, 'email_shortcode' ] );
		add_shortcode( 'lw_hours', [ $this, 'hours_shortcode' ] );
		add_shortcode( 'lw_map', [ $this, 'map_shortcode' ] );
	}

	/**
	 * Get days of the week with translations.
	 *
	 * @return array<string, string>
	 */
	private function get_days(): array {
		if ( null === $this->days ) {
			$this->days = [
				'monday'    => __( 'Monday', 'lw-seo' ),
				'tuesday'   => __( 'Tuesday', 'lw-seo' ),
				'wednesday' => __( 'Wednesday', 'lw-seo' ),
				'thursday'  => __( 'Thursday', 'lw-seo' ),
				'friday'    => __( 'Friday', 'lw-seo' ),
				'saturday'  => __( 'Saturday', 'lw-seo' ),
				'sunday'    => __( 'Sunday', 'lw-seo' ),
			];
		}
		return $this->days;
	}

	/**
	 * Address shortcode.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function address_shortcode( $atts ): string {
		$atts = shortcode_atts(
			[
				'format' => 'full',
				'schema' => 'true',
			],
			$atts,
			'lw_address'
		);

		$street   = Options::get( 'local_street' );
		$street_2 = Options::get( 'local_street_2' );
		$city     = Options::get( 'local_city' );
		$state    = Options::get( 'local_state' );
		$zip      = Options::get( 'local_zip' );
		$country  = Options::get( 'local_country' );

		if ( empty( $street ) && empty( $city ) ) {
			return '';
		}

		$use_schema = 'true' === $atts['schema'];
		$output     = '';

		if ( $use_schema ) {
			$output .= '<address class="lw-seo-address" itemscope itemtype="https://schema.org/PostalAddress">';
		} else {
			$output .= '<address class="lw-seo-address">';
		}

		// Street.
		if ( ! empty( $street ) ) {
			if ( $use_schema ) {
				$output .= '<span itemprop="streetAddress">' . esc_html( $street );
				if ( ! empty( $street_2 ) ) {
					$output .= ', ' . esc_html( $street_2 );
				}
				$output .= '</span><br>';
			} else {
				$output .= esc_html( $street );
				if ( ! empty( $street_2 ) ) {
					$output .= ', ' . esc_html( $street_2 );
				}
				$output .= '<br>';
			}
		}

		// City, state, zip.
		$city_line = [];

		if ( ! empty( $zip ) ) {
			if ( $use_schema ) {
				$city_line[] = '<span itemprop="postalCode">' . esc_html( $zip ) . '</span>';
			} else {
				$city_line[] = esc_html( $zip );
			}
		}

		if ( ! empty( $city ) ) {
			if ( $use_schema ) {
				$city_line[] = '<span itemprop="addressLocality">' . esc_html( $city ) . '</span>';
			} else {
				$city_line[] = esc_html( $city );
			}
		}

		if ( ! empty( $city_line ) ) {
			$output .= implode( ' ', $city_line );

			if ( ! empty( $state ) ) {
				if ( $use_schema ) {
					$output .= ', <span itemprop="addressRegion">' . esc_html( $state ) . '</span>';
				} else {
					$output .= ', ' . esc_html( $state );
				}
			}

			$output .= '<br>';
		}

		// Country.
		if ( ! empty( $country ) && 'full' === $atts['format'] ) {
			if ( $use_schema ) {
				$output .= '<span itemprop="addressCountry">' . esc_html( $country ) . '</span>';
			} else {
				$output .= esc_html( $country );
			}
		}

		$output .= '</address>';

		return $output;
	}

	/**
	 * Phone shortcode.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function phone_shortcode( $atts ): string {
		$atts = shortcode_atts(
			[
				'link'   => 'true',
				'schema' => 'true',
			],
			$atts,
			'lw_phone'
		);

		$phone = Options::get( 'local_phone' );

		if ( empty( $phone ) ) {
			return '';
		}

		$use_link   = 'true' === $atts['link'];
		$use_schema = 'true' === $atts['schema'];

		// Clean phone for tel: link.
		$phone_clean = preg_replace( '/[^0-9+]/', '', $phone );

		$output = '<span class="lw-seo-phone"';

		if ( $use_schema ) {
			$output .= ' itemprop="telephone"';
		}

		$output .= '>';

		if ( $use_link ) {
			$output .= '<a href="tel:' . esc_attr( $phone_clean ) . '">';
		}

		$output .= esc_html( $phone );

		if ( $use_link ) {
			$output .= '</a>';
		}

		$output .= '</span>';

		return $output;
	}

	/**
	 * Email shortcode.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function email_shortcode( $atts ): string {
		$atts = shortcode_atts(
			[
				'link'   => 'true',
				'schema' => 'true',
			],
			$atts,
			'lw_email'
		);

		$email = Options::get( 'local_email' );

		if ( empty( $email ) ) {
			return '';
		}

		$use_link   = 'true' === $atts['link'];
		$use_schema = 'true' === $atts['schema'];

		$output = '<span class="lw-seo-email"';

		if ( $use_schema ) {
			$output .= ' itemprop="email"';
		}

		$output .= '>';

		if ( $use_link ) {
			$output .= '<a href="mailto:' . esc_attr( $email ) . '">';
		}

		$output .= esc_html( $email );

		if ( $use_link ) {
			$output .= '</a>';
		}

		$output .= '</span>';

		return $output;
	}

	/**
	 * Opening hours shortcode.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function hours_shortcode( $atts ): string {
		$atts = shortcode_atts(
			[
				'format' => 'table',
				'schema' => 'true',
			],
			$atts,
			'lw_hours'
		);

		if ( ! Options::get( 'local_hours_enabled' ) ) {
			return '';
		}

		$use_schema = 'true' === $atts['schema'];

		$output = '<div class="lw-seo-hours">';

		if ( 'table' === $atts['format'] ) {
			$output .= '<table class="lw-seo-hours-table">';
			$output .= '<tbody>';

			foreach ( $this->get_days() as $day_key => $day_label ) {
				$is_closed = Options::get( "local_hours_{$day_key}_closed", false );
				$open      = Options::get( "local_hours_{$day_key}_open" );
				$close     = Options::get( "local_hours_{$day_key}_close" );

				$output .= '<tr>';
				$output .= '<th>' . esc_html( $day_label ) . '</th>';
				$output .= '<td>';

				if ( $is_closed ) {
					$output .= esc_html__( 'Closed', 'lw-seo' );
				} elseif ( ! empty( $open ) && ! empty( $close ) ) {
					if ( $use_schema ) {
						$day_schema = ucfirst( $day_key );
						$output    .= '<time itemprop="openingHours" datetime="' . esc_attr( "{$day_schema} {$open}-{$close}" ) . '">';
					}
					$output .= esc_html( $open ) . ' - ' . esc_html( $close );
					if ( $use_schema ) {
						$output .= '</time>';
					}
				} else {
					$output .= '-';
				}

				$output .= '</td>';
				$output .= '</tr>';
			}

			$output .= '</tbody>';
			$output .= '</table>';
		} else {
			$output .= '<ul class="lw-seo-hours-list">';

			foreach ( $this->get_days() as $day_key => $day_label ) {
				$is_closed = Options::get( "local_hours_{$day_key}_closed", false );
				$open      = Options::get( "local_hours_{$day_key}_open" );
				$close     = Options::get( "local_hours_{$day_key}_close" );

				$output .= '<li>';
				$output .= '<strong>' . esc_html( $day_label ) . ':</strong> ';

				if ( $is_closed ) {
					$output .= esc_html__( 'Closed', 'lw-seo' );
				} elseif ( ! empty( $open ) && ! empty( $close ) ) {
					$output .= esc_html( $open ) . ' - ' . esc_html( $close );
				} else {
					$output .= '-';
				}

				$output .= '</li>';
			}

			$output .= '</ul>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Map shortcode (Google Maps embed).
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function map_shortcode( $atts ): string {
		$atts = shortcode_atts(
			[
				'width'  => '100%',
				'height' => '400',
				'zoom'   => '15',
			],
			$atts,
			'lw_map'
		);

		$lat = Options::get( 'local_lat' );
		$lng = Options::get( 'local_lng' );

		if ( empty( $lat ) || empty( $lng ) ) {
			return '';
		}

		$business_name = Options::get( 'local_business_name' );
		if ( empty( $business_name ) ) {
			$business_name = get_bloginfo( 'name' );
		}

		// Build address for query.
		$address_parts = [];
		$street        = Options::get( 'local_street' );
		$city          = Options::get( 'local_city' );
		$country       = Options::get( 'local_country' );

		if ( ! empty( $street ) ) {
			$address_parts[] = $street;
		}
		if ( ! empty( $city ) ) {
			$address_parts[] = $city;
		}
		if ( ! empty( $country ) ) {
			$address_parts[] = $country;
		}

		$query = ! empty( $address_parts )
			? implode( ', ', $address_parts )
			: "{$lat},{$lng}";

		$embed_url = add_query_arg(
			[
				'q'      => rawurlencode( $query ),
				'output' => 'embed',
				'z'      => intval( $atts['zoom'] ),
			],
			'https://maps.google.com/maps'
		);

		$width  = is_numeric( $atts['width'] ) ? $atts['width'] . 'px' : $atts['width'];
		$height = is_numeric( $atts['height'] ) ? $atts['height'] . 'px' : $atts['height'];

		$output  = '<div class="lw-seo-map" style="width:' . esc_attr( $width ) . ';height:' . esc_attr( $height ) . ';">';
		$output .= '<iframe ';
		$output .= 'src="' . esc_url( $embed_url ) . '" ';
		$output .= 'width="100%" height="100%" ';
		$output .= 'style="border:0;" allowfullscreen="" loading="lazy" ';
		$output .= 'referrerpolicy="no-referrer-when-downgrade" ';
		$output .= 'title="' . esc_attr( $business_name ) . '">';
		$output .= '</iframe>';
		$output .= '</div>';

		return $output;
	}
}
