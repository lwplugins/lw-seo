/**
 * WordPress dependencies
 */
import {
	CheckboxControl,
	ExternalLink,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AreaRow, SelectRow, SwitchRow, TextRow } from '../components/Fields';
import Section from '../components/Section';
import SettingRow from '../components/SettingRow';

const DAYS = [
	[ 'monday', __( 'Monday', 'lw-seo' ) ],
	[ 'tuesday', __( 'Tuesday', 'lw-seo' ) ],
	[ 'wednesday', __( 'Wednesday', 'lw-seo' ) ],
	[ 'thursday', __( 'Thursday', 'lw-seo' ) ],
	[ 'friday', __( 'Friday', 'lw-seo' ) ],
	[ 'saturday', __( 'Saturday', 'lw-seo' ) ],
	[ 'sunday', __( 'Sunday', 'lw-seo' ) ],
];

function Hours( { store } ) {
	const o = store.data.options;
	return (
		<div className="lw-admin-hours">
			{ DAYS.map( ( [ day, label ] ) => {
				const closed = !! o[ `local_hours_${ day }_closed` ];
				return (
					<div key={ day } className="lw-admin-hours__row">
						<strong>{ label }</strong>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Closed', 'lw-seo' ) }
							checked={ closed }
							onChange={ ( v ) =>
								store.set( `local_hours_${ day }_closed`, v )
							}
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							type="time"
							label={ __( 'Open', 'lw-seo' ) }
							disabled={ closed }
							value={ o[ `local_hours_${ day }_open` ] || '' }
							onChange={ ( v ) =>
								store.set( `local_hours_${ day }_open`, v )
							}
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							type="time"
							label={ __( 'Close', 'lw-seo' ) }
							disabled={ closed }
							value={ o[ `local_hours_${ day }_close` ] || '' }
							onChange={ ( v ) =>
								store.set( `local_hours_${ day }_close`, v )
							}
						/>
					</div>
				);
			} ) }
		</div>
	);
}

export default function LocalTab( { store } ) {
	const { options, meta } = store.data;

	return (
		<>
			<Section
				title={ __( 'Business Information', 'lw-seo' ) }
				description={ __(
					'Add LocalBusiness structured data to help your business appear in local search results.',
					'lw-seo'
				) }
			>
				<SwitchRow
					title={ __( 'Local SEO', 'lw-seo' ) }
					store={ store }
					name="local_enabled"
					onText={ __( 'Enable LocalBusiness schema', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
				<SettingRow
					title={ __( 'Business Type', 'lw-seo' ) }
					help={ __(
						'Select the type that best describes your business.',
						'lw-seo'
					) }
				>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Business Type', 'lw-seo' ) }
						hideLabelFromVision
						value={ options.local_business_type }
						onChange={ ( v ) =>
							store.set( 'local_business_type', v )
						}
					>
						{ meta.business_types.map( ( group ) => (
							<optgroup key={ group.group } label={ group.group }>
								{ group.options.map( ( o ) => (
									<option key={ o.value } value={ o.value }>
										{ o.label }
									</option>
								) ) }
							</optgroup>
						) ) }
					</SelectControl>
				</SettingRow>
				<TextRow
					title={ __( 'Business Name', 'lw-seo' ) }
					help={ __( 'Leave empty to use the site name.', 'lw-seo' ) }
					store={ store }
					name="local_business_name"
				/>
				<AreaRow
					title={ __( 'Business Description', 'lw-seo' ) }
					store={ store }
					name="local_description"
				/>
				<SelectRow
					title={ __( 'Price Range', 'lw-seo' ) }
					store={ store }
					name="local_price_range"
					options={ [
						{ value: '', label: __( 'Not specified', 'lw-seo' ) },
						...[ '$', '$$', '$$$', '$$$$' ].map( ( v ) => ( {
							value: v,
							label: v,
						} ) ),
					] }
				/>
			</Section>
			<Section title={ __( 'Address and contact', 'lw-seo' ) }>
				<TextRow
					title={ __( 'Street Address', 'lw-seo' ) }
					store={ store }
					name="local_street"
				/>
				<TextRow
					title={ __( 'Address Line 2', 'lw-seo' ) }
					store={ store }
					name="local_street_2"
				/>
				<TextRow
					title={ __( 'City', 'lw-seo' ) }
					store={ store }
					name="local_city"
				/>
				<TextRow
					title={ __( 'State / Region', 'lw-seo' ) }
					store={ store }
					name="local_state"
				/>
				<TextRow
					title={ __( 'Postal Code', 'lw-seo' ) }
					store={ store }
					name="local_zip"
				/>
				<TextRow
					title={ __( 'Country', 'lw-seo' ) }
					help={ __(
						'Use ISO 3166–1 alpha-2 code (e.g., HU, US, DE).',
						'lw-seo'
					) }
					store={ store }
					name="local_country"
					placeholder="HU"
				/>
				<TextRow
					title={ __( 'Phone', 'lw-seo' ) }
					store={ store }
					name="local_phone"
					type="tel"
					placeholder="+36 1 234 5678"
				/>
				<TextRow
					title={ __( 'Email', 'lw-seo' ) }
					store={ store }
					name="local_email"
					type="email"
					placeholder="info@example.com"
				/>
			</Section>
			<Section title={ __( 'Opening Hours', 'lw-seo' ) }>
				<SwitchRow
					title={ __( 'Opening hours', 'lw-seo' ) }
					store={ store }
					name="local_hours_enabled"
					onText={ __( 'Include opening hours in schema', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
				{ options.local_hours_enabled && <Hours store={ store } /> }
			</Section>
			<Section
				title={ __( 'Coordinates', 'lw-seo' ) }
				description={
					<>
						{ __( 'Find coordinates on', 'lw-seo' ) }{ ' ' }
						<ExternalLink href="https://maps.google.com">
							Google Maps
						</ExternalLink>{ ' ' }
						{ __( '(right-click the location).', 'lw-seo' ) }
					</>
				}
			>
				<TextRow
					title={ __( 'Latitude', 'lw-seo' ) }
					store={ store }
					name="local_lat"
					placeholder="47.4979"
				/>
				<TextRow
					title={ __( 'Longitude', 'lw-seo' ) }
					store={ store }
					name="local_lng"
					placeholder="19.0402"
				/>
			</Section>
		</>
	);
}
