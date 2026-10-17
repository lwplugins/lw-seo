/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

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
	testRedirect: ( url ) => send( '/redirects-test', 'POST', { url } ),

	notFoundLog: ( args, signal ) =>
		apiFetch( { path: addQueryArgs( path( '/404-log' ), args ), signal } ),
	deleteNotFound: ( id ) => send( `/404-log/${ Number( id ) }`, 'DELETE' ),
	clearNotFound: () => send( '/404-log', 'DELETE' ),

	indexNowSubmit: ( urls ) => send( '/indexnow/submit', 'POST', { urls } ),
	indexNowRegenerateKey: () => send( '/indexnow/regenerate-key', 'POST' ),

	detectMigration: ( provider ) =>
		apiFetch( { path: path( `/migration/${ provider }` ) } ),
	runMigration: ( provider, dryRun ) =>
		send( `/migration/${ provider }`, 'POST', { dry_run: dryRun } ),
};

export const errorMessage = ( error ) =>
	error?.message ||
	'That did not work. Please reload the page and try again.';
