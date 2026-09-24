/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SwitchRow } from '../components/Fields';
import Section from '../components/Section';

export default function NotFoundTab( { store } ) {
	return (
		<Section
			title={ __( '404 Error Handling', 'lw-seo' ) }
			description={ __(
				'Configure how 404 (Not Found) errors are handled on your site.',
				'lw-seo'
			) }
		>
			<SwitchRow
				title={ __( 'Redirect 404 to Homepage', 'lw-seo' ) }
				help={ __(
					'When enabled, visitors who land on a non-existent page will be redirected to your homepage with a 302 (temporary) redirect.',
					'lw-seo'
				) }
				store={ store }
				name="redirect_404_to_home"
				onText={ __( 'Redirect every 404 to the homepage', 'lw-seo' ) }
				offText={ __( 'Off', 'lw-seo' ) }
			/>
		</Section>
	);
}
