/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SelectRow, SwitchRow, TextRow } from '../../components/Fields';
import Section from '../../components/Section';

const off = __( 'Off', 'lw-seo' );
const on = __( 'Enabled', 'lw-seo' );

function ShippingSection( { store } ) {
	const options = store.data.options;
	const currency = store.data.meta.woo_currency;
	const flat = options.woo_shipping_rate_type !== 'free';

	return (
		<Section
			title={ __( 'Shipping', 'lw-seo' ) }
			description={ __(
				'Adds shipping cost and delivery time to the product offers in the structured data.',
				'lw-seo'
			) }
		>
			<SwitchRow
				title={ __( 'Shipping details', 'lw-seo' ) }
				store={ store }
				name="woo_shipping_enabled"
				onText={ on }
				offText={ off }
			/>
			{ options.woo_shipping_enabled && (
				<>
					<TextRow
						title={ __( 'Countries', 'lw-seo' ) }
						help={ __(
							'Two-letter country codes you ship to, separated by commas (for example HU, DE, AT).',
							'lw-seo'
						) }
						store={ store }
						name="woo_shipping_countries"
						placeholder="HU, DE, AT"
						mono
					/>
					<SelectRow
						title={ __( 'Shipping cost', 'lw-seo' ) }
						store={ store }
						name="woo_shipping_rate_type"
						options={ [
							{
								label: __( 'Flat rate', 'lw-seo' ),
								value: 'flat',
							},
							{
								label: __( 'Free shipping', 'lw-seo' ),
								value: 'free',
							},
						] }
					/>
					{ flat && (
						<TextRow
							title={
								currency
									? sprintf(
											/* translators: %s: shop currency code, e.g. EUR */
											__( 'Rate (%s)', 'lw-seo' ),
											currency
										)
									: __( 'Rate', 'lw-seo' )
							}
							help={ __(
								'The shipping price in the shop currency, for example 1490 or 4.90.',
								'lw-seo'
							) }
							store={ store }
							name="woo_shipping_rate"
						/>
					) }
					<TextRow
						title={ __( 'Handling time, from (days)', 'lw-seo' ) }
						store={ store }
						name="woo_handling_min"
						type="number"
					/>
					<TextRow
						title={ __( 'Handling time, to (days)', 'lw-seo' ) }
						help={ __(
							'Days from the order until the parcel is handed to the carrier.',
							'lw-seo'
						) }
						store={ store }
						name="woo_handling_max"
						type="number"
					/>
					<TextRow
						title={ __( 'Transit time, from (days)', 'lw-seo' ) }
						store={ store }
						name="woo_transit_min"
						type="number"
					/>
					<TextRow
						title={ __( 'Transit time, to (days)', 'lw-seo' ) }
						help={ __(
							'Days the carrier needs to deliver the parcel.',
							'lw-seo'
						) }
						store={ store }
						name="woo_transit_max"
						type="number"
					/>
				</>
			) }
		</Section>
	);
}

function ReturnsSection( { store } ) {
	const options = store.data.options;
	const category = options.woo_return_category;

	return (
		<Section
			title={ __( 'Returns', 'lw-seo' ) }
			description={ __(
				'Adds your return policy to the product offers in the structured data.',
				'lw-seo'
			) }
		>
			<SwitchRow
				title={ __( 'Return policy', 'lw-seo' ) }
				store={ store }
				name="woo_returns_enabled"
				onText={ on }
				offText={ off }
			/>
			{ options.woo_returns_enabled && (
				<>
					<TextRow
						title={ __( 'Country', 'lw-seo' ) }
						help={ __(
							'Two-letter code of the country the policy applies to (for example HU).',
							'lw-seo'
						) }
						store={ store }
						name="woo_return_country"
						placeholder="HU"
						mono
					/>
					<SelectRow
						title={ __( 'Policy', 'lw-seo' ) }
						store={ store }
						name="woo_return_category"
						options={ [
							{
								label: __(
									'Returns within a set number of days',
									'lw-seo'
								),
								value: 'finite',
							},
							{
								label: __(
									'Returns without a time limit',
									'lw-seo'
								),
								value: 'unlimited',
							},
							{
								label: __( 'No returns', 'lw-seo' ),
								value: 'not_permitted',
							},
						] }
					/>
					{ category === 'finite' && (
						<TextRow
							title={ __( 'Return window (days)', 'lw-seo' ) }
							store={ store }
							name="woo_return_days"
							type="number"
						/>
					) }
					{ category !== 'not_permitted' && (
						<>
							<SelectRow
								title={ __( 'Return method', 'lw-seo' ) }
								store={ store }
								name="woo_return_method"
								options={ [
									{
										label: __( 'By mail', 'lw-seo' ),
										value: 'by_mail',
									},
									{
										label: __( 'In store', 'lw-seo' ),
										value: 'in_store',
									},
								] }
							/>
							<SelectRow
								title={ __( 'Return costs', 'lw-seo' ) }
								store={ store }
								name="woo_return_fees"
								options={ [
									{
										label: __( 'Free return', 'lw-seo' ),
										value: 'free',
									},
									{
										label: __( 'Customer pays', 'lw-seo' ),
										value: 'customer_pays',
									},
								] }
							/>
						</>
					) }
				</>
			) }
		</Section>
	);
}

export default function PoliciesSection( { store } ) {
	return (
		<>
			<ShippingSection store={ store } />
			<ReturnsSection store={ store } />
		</>
	);
}
