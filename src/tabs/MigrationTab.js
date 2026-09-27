/**
 * WordPress dependencies
 */
import {
	Button,
	__experimentalConfirmDialog as ConfirmDialog,
	Notice,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import LoadError from '../components/LoadError';
import Section from '../components/Section';
import {
	SkeletonRegion,
	SkeletonRows,
	SkeletonSection,
} from '../components/skeleton';
import StatusBadge from '../components/StatusBadge';
import { api, errorMessage } from '../data/api';
import { formatNumber } from '../data/format';

const PROVIDERS = [
	{
		id: 'rankmath',
		name: 'RankMath SEO',
		help: __( 'RankMath SEO data was found in your database.', 'lw-seo' ),
	},
	{
		id: 'yoast',
		name: 'Yoast SEO',
		help: __( 'Yoast SEO data was found in your database.', 'lw-seo' ),
	},
	{
		id: 'seopress',
		name: 'SEOPress',
		help: __( 'SEOPress data was found in your database.', 'lw-seo' ),
	},
	{
		id: 'aioseo',
		name: 'All in One SEO',
		help: __( 'All in One SEO data was found in your database.', 'lw-seo' ),
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

function Provider( { provider, detected } ) {
	const [ detect, setDetect ] = useState( detected );
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
					{ __( 'Detect Again', 'lw-seo' ) }
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
								__( 'Redirects', 'lw-seo' ),
								n( detect.redirects_count ),
							],
						].filter(
							( [ label ] ) =>
								detect.user_count > 0 ||
								label !== __( 'Users with SEO meta', 'lw-seo' )
						) }
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

function DetectSkeleton() {
	return (
		<SkeletonRegion label={ __( 'Looking for SEO plugin data', 'lw-seo' ) }>
			<SkeletonSection>
				<SkeletonRows count={ 4 } />
			</SkeletonSection>
		</SkeletonRegion>
	);
}

/**
 * Detection result of every provider; only providers with data are listed.
 *
 * @return {{found: Array|null, error: string, reload: () => void}} State.
 */
function useDetectedProviders() {
	const [ found, setFound ] = useState( null );
	const [ error, setError ] = useState( '' );
	const [ attempt, setAttempt ] = useState( 0 );

	useEffect( () => {
		let active = true;
		setFound( null );
		setError( '' );
		Promise.all(
			PROVIDERS.map( ( provider ) =>
				api
					.detectMigration( provider.id )
					.then( ( detected ) => ( { provider, detected } ) )
			)
		)
			.then( ( results ) => {
				if ( active ) {
					setFound( results.filter( ( r ) => r.detected?.found ) );
				}
			} )
			.catch( ( e ) => active && setError( errorMessage( e ) ) );
		return () => {
			active = false;
		};
	}, [ attempt ] );

	return { found, error, reload: () => setAttempt( ( n ) => n + 1 ) };
}

export default function MigrationTab() {
	const { found, error, reload } = useDetectedProviders();

	let content;
	if ( error ) {
		content = <LoadError message={ error } onRetry={ reload } />;
	} else if ( null === found ) {
		content = <DetectSkeleton />;
	} else if ( ! found.length ) {
		content = (
			<Callout>
				{ __(
					'No data from Yoast SEO, RankMath, SEOPress or All in One SEO was found in the database, so there is nothing to import.',
					'lw-seo'
				) }
			</Callout>
		);
	} else {
		content = found.map( ( { provider, detected } ) => (
			<Provider
				key={ provider.id }
				provider={ provider }
				detected={ detected }
			/>
		) );
	}

	return (
		<>
			<Callout>
				{ __(
					'Import SEO data from other SEO plugins. Existing LW SEO data will not be overwritten.',
					'lw-seo'
				) }
			</Callout>
			{ content }
		</>
	);
}
