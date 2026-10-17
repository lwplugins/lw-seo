/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SwitchRow, TextRow } from '../components/Fields';
import Section from '../components/Section';
import NotFoundLog from './notfound/NotFoundLog';

export default function NotFoundTab( { store } ) {
	const logEnabled = !! store.data.options.not_found_log_enabled;

	return (
		<>
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
					onText={ __(
						'Redirect every 404 to the homepage',
						'lw-seo'
					) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
			</Section>

			<Section
				title={ __( '404 Log', 'lw-seo' ) }
				description={ __(
					'Keep a short list of missing URLs so you can redirect them. Off by default.',
					'lw-seo'
				) }
			>
				<SwitchRow
					title={ __( 'Log 404 errors', 'lw-seo' ) }
					help={ __(
						'Stores the requested path, a hit count and the referrer without its query string. Static files such as images are skipped.',
						'lw-seo'
					) }
					store={ store }
					name="not_found_log_enabled"
					onText={ __( 'Logging missing URLs', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
				{ logEnabled && (
					<>
						<TextRow
							title={ __( 'Keep entries for (days)', 'lw-seo' ) }
							help={ __(
								'Older entries are removed daily.',
								'lw-seo'
							) }
							store={ store }
							name="not_found_log_retention_days"
							type="number"
						/>
						<TextRow
							title={ __( 'Maximum entries', 'lw-seo' ) }
							help={ __(
								'When the log is full, the oldest one-off entries make room. Recurring entries are kept.',
								'lw-seo'
							) }
							store={ store }
							name="not_found_log_max_rows"
							type="number"
						/>
						<SwitchRow
							title={ __( 'Store user agent', 'lw-seo' ) }
							help={ __(
								'Also keeps the visitor browser or bot name.',
								'lw-seo'
							) }
							store={ store }
							name="not_found_log_store_ua"
							onText={ __( 'Storing user agents', 'lw-seo' ) }
							offText={ __( 'Off', 'lw-seo' ) }
						/>
					</>
				) }
			</Section>

			{ logEnabled && <NotFoundLog /> }
		</>
	);
}
