/**
 * WordPress dependencies
 */
import { Button, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../components/Section';
import { api, errorMessage } from '../../data/api';

const verdictLabel = () => ( {
	matched: __( 'Matched', 'lw-seo' ),
	no: __( 'No match', 'lw-seo' ),
	invalid_regex: __( 'Invalid regex', 'lw-seo' ),
} );

/**
 * Which redirect a URL would hit, and how every rule judged it.
 */
export default function TestUrl() {
	const [ url, setUrl ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const [ result, setResult ] = useState( null );
	const [ error, setError ] = useState( '' );

	const run = async ( event ) => {
		event.preventDefault();
		if ( ! url.trim() ) {
			return;
		}
		setBusy( true );
		setError( '' );
		try {
			setResult( await api.testRedirect( url.trim() ) );
		} catch ( e ) {
			setResult( null );
			setError( errorMessage( e ) );
		}
		setBusy( false );
	};

	const match = result?.match;
	const labels = verdictLabel();

	return (
		<Section
			title={ __( 'Test a URL', 'lw-seo' ) }
			description={ __(
				'Check which redirect a path or URL would hit. The query string is ignored.',
				'lw-seo'
			) }
		>
			<form className="lw-admin-inline" onSubmit={ run }>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'URL or path', 'lw-seo' ) }
					hideLabelFromVision
					placeholder="/old-page/"
					value={ url }
					onChange={ setUrl }
				/>
				<Button
					__next40pxDefaultSize
					variant="secondary"
					type="submit"
					isBusy={ busy }
					disabled={ busy || ! url.trim() }
				>
					{ __( 'Test', 'lw-seo' ) }
				</Button>
			</form>
			{ error && (
				<div className="lw-admin-testresult is-error" role="alert">
					<p>{ error }</p>
				</div>
			) }
			{ result && (
				<div
					className={ `lw-admin-testresult is-${
						match ? 'ok' : 'error'
					}` }
					role="status"
				>
					<p>
						{ match ? (
							<>
								<code className="lw-admin-code">
									{ result.path }
								</code>
								{ ' → ' }
								{ match.destination ? (
									<code className="lw-admin-code">
										{ match.destination }
									</code>
								) : null }
								{ sprintf(
									/* translators: %d: HTTP status code. */
									__( ' (%d)', 'lw-seo' ),
									match.type
								) }
							</>
						) : (
							__( 'No redirect matches', 'lw-seo' )
						) }
					</p>
					<details>
						<summary>
							{ sprintf(
								/* translators: %d: number of redirect rules. */
								__( 'Rules checked (%d)', 'lw-seo' ),
								result.rules.length
							) }
						</summary>
						<ul>
							{ result.rules.map( ( rule ) => (
								<li key={ rule.id }>
									<code className="lw-admin-code">
										{ rule.source }
									</code>
									{ ' — ' }
									{ labels[ rule.result ] || rule.result }
								</li>
							) ) }
						</ul>
					</details>
				</div>
			) }
		</Section>
	);
}
