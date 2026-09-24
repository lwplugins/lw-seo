/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { errorMessage } from './api';

const same = ( a, b ) => JSON.stringify( a ) === JSON.stringify( b );

/**
 * Loaded server values + a local draft. Save sends only the keys that differ
 * from the server copy, so one tab can never overwrite another tab's fields.
 *
 * @param {Function} load     () => Promise<data>.
 * @param {Function} persist  ( patch ) => Promise<data>.
 * @param {string[]} editable Keys the user can change.
 * @return {Object} Store.
 */
export default function useDraftStore( load, persist, editable ) {
	const [ server, setServer ] = useState( null );
	const [ draft, setDraft ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const reload = useCallback( () => {
		setError( null );
		return load().then(
			( data ) => {
				setServer( data );
				setDraft( data );
			},
			( e ) => setError( errorMessage( e ) )
		);
	}, [ load ] );

	useEffect( () => {
		reload();
	}, [ reload ] );

	const patch = draft
		? Object.fromEntries(
				editable
					.filter( ( key ) => ! same( draft[ key ], server[ key ] ) )
					.map( ( key ) => [ key, draft[ key ] ] )
			)
		: {};
	const hasEdits = Object.keys( patch ).length > 0;

	const save = async () => {
		if ( ! hasEdits ) {
			return;
		}
		setIsSaving( true );
		try {
			const data = await persist( patch );
			setServer( data );
			setDraft( data );
			createSuccessNotice( __( 'Settings saved.', 'lw-seo' ), {
				type: 'snackbar',
			} );
		} catch ( e ) {
			createErrorNotice( errorMessage( e ), { type: 'snackbar' } );
		}
		setIsSaving( false );
	};

	return {
		data: draft,
		server,
		isLoading: ! draft && ! error,
		error,
		reload,
		update: ( values ) =>
			setDraft( ( prev ) => ( { ...prev, ...values } ) ),
		replace: ( data ) => {
			setServer( data );
			setDraft( data );
		},
		hasEdits,
		isSaving,
		save,
		discard: () => setDraft( server ),
	};
}
