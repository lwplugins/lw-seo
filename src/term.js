/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TermFields from './editor/TermFields';
import './editor/editor.scss';

const root = document.getElementById( 'lw-seo-term-root' );

if ( root ) {
	createRoot( root ).render( <TermFields /> );
}
