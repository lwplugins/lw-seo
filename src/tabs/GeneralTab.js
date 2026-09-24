/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AreaRow, SelectRow, TextRow } from '../components/Fields';
import MediaPicker from '../components/MediaPicker';
import Section from '../components/Section';
import SettingRow from '../components/SettingRow';
import Vars from '../components/Vars';

export default function GeneralTab( { store } ) {
	const { options, meta } = store.data;

	return (
		<>
			<Section
				title={ __( 'Site', 'lw-seo' ) }
				description={ __(
					'Basic SEO settings for your site.',
					'lw-seo'
				) }
			>
				<SelectRow
					title={ __( 'Title Separator', 'lw-seo' ) }
					store={ store }
					name="separator"
					options={ Object.entries( meta.separators ).map(
						( [ value, label ] ) => ( { value, label } )
					) }
				/>
				<TextRow
					title={ __( 'Homepage Title', 'lw-seo' ) }
					help={
						<Vars names={ [ 'sitename', 'sitedesc', 'sep' ] } />
					}
					store={ store }
					name="title_home"
					mono
				/>
				<AreaRow
					title={ __( 'Homepage Description', 'lw-seo' ) }
					help={ __(
						'Leave empty to use the site tagline.',
						'lw-seo'
					) }
					store={ store }
					name="desc_home"
				/>
			</Section>
			<Section
				title={ __( 'Knowledge Graph', 'lw-seo' ) }
				description={ __(
					'Who the site represents in Schema.org markup.',
					'lw-seo'
				) }
			>
				<SelectRow
					title={ __( 'Site Represents', 'lw-seo' ) }
					store={ store }
					name="knowledge_type"
					options={ [
						{
							value: 'organization',
							label: __( 'Organization', 'lw-seo' ),
						},
						{ value: 'person', label: __( 'Person', 'lw-seo' ) },
					] }
				/>
				<TextRow
					title={ __( 'Organization/Person Name', 'lw-seo' ) }
					help={ __( 'Used in Schema.org markup.', 'lw-seo' ) }
					store={ store }
					name="knowledge_name"
				/>
				<SettingRow
					title={ __( 'Logo', 'lw-seo' ) }
					help={ __(
						'Organization logo for Schema.org markup.',
						'lw-seo'
					) }
				>
					<MediaPicker
						label={ __( 'Logo', 'lw-seo' ) }
						value={ options.knowledge_logo }
						onChange={ ( url ) =>
							store.set( 'knowledge_logo', url )
						}
					/>
				</SettingRow>
			</Section>
		</>
	);
}
