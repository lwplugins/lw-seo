/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SelectRow, SwitchRow, TextRow } from '../components/Fields';
import MediaPicker from '../components/MediaPicker';
import Section from '../components/Section';
import SettingRow from '../components/SettingRow';

const PROFILES = [
	[ 'social_facebook', 'Facebook' ],
	[ 'social_twitter', 'Twitter / X' ],
	[ 'social_instagram', 'Instagram' ],
	[ 'social_linkedin', 'LinkedIn' ],
	[ 'social_youtube', 'YouTube' ],
];

export default function SocialTab( { store } ) {
	return (
		<>
			<Section
				title={ __( 'Meta Tags', 'lw-seo' ) }
				description={ __(
					'Settings for Open Graph and Twitter Cards.',
					'lw-seo'
				) }
			>
				<SwitchRow
					title={ __( 'Open Graph', 'lw-seo' ) }
					store={ store }
					name="opengraph_enabled"
					onText={ __( 'Enable Open Graph meta tags', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
				<SwitchRow
					title={ __( 'Twitter Cards', 'lw-seo' ) }
					store={ store }
					name="twitter_enabled"
					onText={ __( 'Enable Twitter Card meta tags', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
				<SelectRow
					title={ __( 'Default Card Type', 'lw-seo' ) }
					store={ store }
					name="twitter_card_type"
					options={ [
						{
							value: 'summary_large_image',
							label: __( 'Summary with large image', 'lw-seo' ),
						},
						{ value: 'summary', label: __( 'Summary', 'lw-seo' ) },
					] }
				/>
				<SettingRow
					title={ __( 'Default Social Image', 'lw-seo' ) }
					help={ __(
						'Used when a post has no featured image. Recommended: 1200x630px.',
						'lw-seo'
					) }
				>
					<MediaPicker
						label={ __( 'Default Social Image', 'lw-seo' ) }
						value={ store.data.options.default_og_image }
						onChange={ ( url ) =>
							store.set( 'default_og_image', url )
						}
					/>
				</SettingRow>
			</Section>
			<Section title={ __( 'Social Profiles', 'lw-seo' ) }>
				{ PROFILES.map( ( [ name, label ] ) => (
					<TextRow
						key={ name }
						title={ label }
						store={ store }
						name={ name }
						type="url"
						placeholder="https://"
					/>
				) ) }
			</Section>
		</>
	);
}
