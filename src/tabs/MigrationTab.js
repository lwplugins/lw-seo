/**
 * WordPress dependencies
 */
import {
	Button,
	__experimentalConfirmDialog as ConfirmDialog,
	Notice,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import Section from '../components/Section';
import StatusBadge from '../components/StatusBadge';
import { api, errorMessage } from '../data/api';
import { formatNumber } from '../data/format';

const PROVIDERS = [
	{
		id: 'rankmath',
		name: 'RankMath SEO',
		help: __(
			'Detect RankMath SEO data in your database for migration.',
			'lw-seo'
		),
	},
	{
		id: 'yoast',
		name: 'Yoast SEO',
		help: __(
			'Detect Yoast SEO data in your database for migration.',
			'lw-seo'
		),
	},
];

const SEVERITY = { error: 'critical', warning: 'warning', info: 'info' };

function Rows( { rows } ) {
	return (
		<ul className="lw-admin-checks">
			{ rows.map( ( [ label, value ] ) => (
				<li key={ label }>
					<span className="lw-admin-checks__label">{ label }</span>
					<span className="lw-admin-checks__detail" />
					<strong>{ value }</strong>
				</li>
			) ) }
		</ul>
	);
}

function Warnings( { warnings = [] } ) {
	return warnings.map( ( w ) => (
		<Notice
			key={ w.code + w.message }
			status={ w.severity === 'error' ? 'error' : w.severity }
			isDismissible={ false }
		>
			<StatusBadge status={ SEVERITY[ w.severity ] || 'info' }>
				{ w.severity }
			</StatusBadge>{ ' ' }
			{ w.message }
		</Notice>
	) );
}

function Provider( { provider } ) {
	const [ detect, setDetect ] = useState( null );
	const [ result, setResult ] = useState( null );
	const [ busy, setBusy ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ confirm, setConfirm ] = useState( false );

	const call = async ( id, fn, done ) => {
		setBusy( id );
		setError( '' );
		try {
			done( await fn() );
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
		setBusy( '' );
	};

	const n = formatNumber;
	return (
		<Section title={ provider.name } description={ provider.help }>
			<div className="lw-admin-inline">
				<Button
					__next40pxDefaultSize
					variant="secondary"
					isBusy={ busy === 'detect' }
					disabled={ !! busy }
					onClick={ () =>
						call(
							'detect',
							() => api.detectMigration( provider.id ),
							( d ) => {
								setDetect( d );
								setResult( null );
							}
						)
					}
				>
					{ __( 'Detect Data', 'lw-seo' ) }
				</Button>
				{ detect?.found && (
					<>
						<Button
							__next40pxDefaultSize
							variant="secondary"
							isBusy={ busy === 'preview' }
							disabled={ !! busy }
							onClick={ () =>
								call(
									'preview',
									() => api.runMigration( provider.id, true ),
									setResult
								)
							}
						>
							{ __( 'Preview Migration', 'lw-seo' ) }
						</Button>
						<Button
							__next40pxDefaultSize
							variant="primary"
							isBusy={ busy === 'run' }
							disabled={ !! busy }
							onClick={ () => setConfirm( true ) }
						>
							{ __( 'Run Migration', 'lw-seo' ) }
						</Button>
					</>
				) }
			</div>
			{ error && (
				<p className="lw-admin-testresult is-error" role="alert">
					{ error }
				</p>
			) }
			{ detect && ! detect.found && (
				<Callout>
					{ __(
						'No data found in the database for this plugin.',
						'lw-seo'
					) }
				</Callout>
			) }
			{ detect?.found && ! result && (
				<>
					<Rows
						rows={ [
							[
								__( 'Global Options', 'lw-seo' ),
								detect.has_options
									? __( 'Found', 'lw-seo' )
									: __( 'Not found', 'lw-seo' ),
							],
							[
								__( 'Posts with SEO meta', 'lw-seo' ),
								n( detect.post_count ),
							],
							[
								__( 'Terms with SEO meta', 'lw-seo' ),
								n( detect.term_count ),
							],
							[
								__( 'Users with SEO meta', 'lw-seo' ),
								n( detect.user_count ),
							],
							[
								__( 'Redirects (DB table)', 'lw-seo' ),
								n( detect.redirects_count ),
							],
						] }
					/>
					<Warnings warnings={ detect.warnings } />
				</>
			) }
			{ result && (
				<>
					<span className="lw-admin-label">
						{ result.dry_run
							? __( 'Preview Results (Dry Run)', 'lw-seo' )
							: __( 'Migration Results', 'lw-seo' ) }
					</span>
					{ result.dry_run && (
						<Callout>
							{ __(
								'This was a preview. No data was modified.',
								'lw-seo'
							) }
						</Callout>
					) }
					<Rows
						rows={ [
							[
								__( 'Options migrated', 'lw-seo' ),
								n( result.options_migrated ),
							],
							[
								__( 'Posts migrated', 'lw-seo' ),
								n( result.posts?.migrated ),
							],
							[
								__(
									'Posts skipped (LW SEO data already present)',
									'lw-seo'
								),
								n( result.posts?.skipped_already_present ),
							],
							[
								__(
									'Posts skipped (no actionable data)',
									'lw-seo'
								),
								n( result.posts?.skipped_no_data ),
							],
							[
								__( 'Terms migrated', 'lw-seo' ),
								n( result.terms?.migrated ),
							],
							[
								__(
									'Terms skipped (LW SEO data already present)',
									'lw-seo'
								),
								n( result.terms?.skipped_already_present ),
							],
							[
								__(
									'Terms skipped (no actionable data)',
									'lw-seo'
								),
								n( result.terms?.skipped_no_data ),
							],
							[
								__( 'Users migrated', 'lw-seo' ),
								n( result.users?.migrated ),
							],
							[
								__( 'Primary terms migrated', 'lw-seo' ),
								n( result.primary_terms?.migrated ),
							],
							[
								__( 'Redirects migrated', 'lw-seo' ),
								n( result.redirects?.migrated ),
							],
							[
								__( 'Redirects skipped', 'lw-seo' ),
								n( result.redirects?.skipped ),
							],
						] }
					/>
					{ result.options_details?.length > 0 && (
						<details className="lw-admin-details">
							<summary>
								{ __( 'Migrated options', 'lw-seo' ) }
							</summary>
							<pre className="lw-admin-pre">
								{ result.options_details.join( '\n' ) }
							</pre>
						</details>
					) }
					<Warnings warnings={ result.warnings } />
				</>
			) }
			<ConfirmDialog
				isOpen={ confirm }
				confirmButtonText={ __( 'Run Migration', 'lw-seo' ) }
				onConfirm={ () => {
					setConfirm( false );
					call(
						'run',
						() => api.runMigration( provider.id, false ),
						setResult
					);
				} }
				onCancel={ () => setConfirm( false ) }
			>
				{ __(
					'Are you sure you want to run the migration? Existing LW SEO data will not be overwritten.',
					'lw-seo'
				) }
			</ConfirmDialog>
		</Section>
	);
}

export default function MigrationTab() {
	return (
		<>
			<Callout>
				{ __(
					'Import SEO data from other SEO plugins. Existing LW SEO data will not be overwritten.',
					'lw-seo'
				) }
			</Callout>
			{ PROVIDERS.map( ( provider ) => (
				<Provider key={ provider.id } provider={ provider } />
			) ) }
		</>
	);
}
