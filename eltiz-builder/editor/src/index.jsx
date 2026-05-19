import { createRoot } from '@wordpress/element';
import App from './App';
import './store';

const root = document.getElementById( 'eltiz-editor-root' );
if ( root ) {
	createRoot( root ).render( <App /> );
}
