/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { NAMESPACE } from './boot';

const path = ( route ) => `/${ NAMESPACE }/admin${ route }`;
const send = ( route, method, data ) =>
	apiFetch( { path: path( route ), method, data } );

export const api = {
	settings: () => apiFetch( { path: path( '/settings' ) } ),
	saveSettings: ( patch ) => send( '/settings', 'POST', patch ),

	redirects: () => apiFetch( { path: path( '/redirects' ) } ),
	addRedirect: ( data ) => send( '/redirects', 'POST', data ),
	updateRedirect: ( id, data ) =>
		send( `/redirects/${ encodeURIComponent( id ) }`, 'PUT', data ),
	deleteRedirect: ( id ) =>
		send( `/redirects/${ encodeURIComponent( id ) }`, 'DELETE' ),
	exportRedirects: () => apiFetch( { path: path( '/redirects/export' ) } ),
	importRedirects: ( csv ) => send( '/redirects/import', 'POST', { csv } ),

	detectMigration: ( provider ) =>
		apiFetch( { path: path( `/migration/${ provider }` ) } ),
	runMigration: ( provider, dryRun ) =>
		send( `/migration/${ provider }`, 'POST', { dry_run: dryRun } ),
};

export const errorMessage = ( error ) =>
	error?.message ||
	'That did not work. Please reload the page and try again.';
