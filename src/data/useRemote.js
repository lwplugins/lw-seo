/**
 * WordPress dependencies
 */
import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * Minimal fetch-state hook for the read-only demo endpoints.
 *
 * @param {Function} fetcher Returns a promise.
 * @param {boolean}  auto    Fetch on mount.
 */
export default function useRemote( fetcher, auto = true ) {
	const [ state, setState ] = useState( {
		data: null,
		error: null,
		isLoading: auto,
	} );

	const reload = useCallback( () => {
		setState( ( prev ) => ( { ...prev, error: null, isLoading: true } ) );
		return fetcher().then(
			( data ) => setState( { data, error: null, isLoading: false } ),
			( error ) => setState( { data: null, error, isLoading: false } )
		);
	}, [ fetcher ] );

	useEffect( () => {
		if ( auto ) {
			reload();
		}
	}, [ auto, reload ] );

	return { ...state, reload };
}
