/**
 * WordPress dependencies
 */
import { ExternalLink, Notice } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SwitchRow } from '../components/Fields';
import Section from '../components/Section';
import SettingRow from '../components/SettingRow';

export default function AdvancedTab( { store } ) {
	const { meta } = store.data;
	const onOff = [ __( 'Enabled', 'lw-seo' ), __( 'Off', 'lw-seo' ) ];

	return (
		<>
			<Section title={ __( 'Features', 'lw-seo' ) }>
				<SwitchRow
					title={ __( 'Breadcrumbs', 'lw-seo' ) }
					help={ __(
						'Use shortcode [lw_breadcrumbs] or function lw_seo_breadcrumbs().',
						'lw-seo'
					) }
					store={ store }
					name="breadcrumbs_enabled"
					onText={ onOff[ 0 ] }
					offText={ onOff[ 1 ] }
				/>
				<SwitchRow
					title={ __( 'Schema.org', 'lw-seo' ) }
					store={ store }
					name="schema_enabled"
					onText={ __( 'Output Schema.org JSON-LD', 'lw-seo' ) }
					offText={ onOff[ 1 ] }
				/>
				<SwitchRow
					title={ __( 'robots.txt', 'lw-seo' ) }
					store={ store }
					name="robots_txt_enabled"
					onText={ __(
						'Add sitemap URL and AI rules to robots.txt',
						'lw-seo'
					) }
					offText={ onOff[ 1 ] }
				/>
				<SettingRow
					title={ __( 'Your robots.txt', 'lw-seo' ) }
					help={
						<ExternalLink href={ meta.urls.robots }>
							{ meta.urls.robots }
						</ExternalLink>
					}
					stacked
				>
					{ meta.robots.physical_file ? (
						<Notice status="warning" isDismissible={ false }>
							{ sprintf(
								/* translators: %s: path of the robots.txt file. */
								__(
									'A physical robots.txt file exists (%s). The web server serves it directly, so these settings have no effect. Delete the file to let LW SEO manage robots.txt.',
									'lw-seo'
								),
								meta.robots.physical_file
							) }
						</Notice>
					) : (
						<pre className="lw-admin-pre">
							{ meta.robots.preview }
						</pre>
					) }
				</SettingRow>
			</Section>
			<Section title={ __( 'Head Cleanup', 'lw-seo' ) }>
				<SwitchRow
					title={ __( 'Shortlinks', 'lw-seo' ) }
					store={ store }
					name="remove_shortlinks"
					onText={ __( 'Removed from head', 'lw-seo' ) }
					offText={ __( 'Kept', 'lw-seo' ) }
				/>
				<SwitchRow
					title={ __( 'RSD Link', 'lw-seo' ) }
					store={ store }
					name="remove_rsd"
					onText={ __( 'Removed from head', 'lw-seo' ) }
					offText={ __( 'Kept', 'lw-seo' ) }
				/>
				<SwitchRow
					title={ __( 'WLW Manifest', 'lw-seo' ) }
					store={ store }
					name="remove_wlw"
					onText={ __( 'Removed from head', 'lw-seo' ) }
					offText={ __( 'Kept', 'lw-seo' ) }
				/>
			</Section>
		</>
	);
}
