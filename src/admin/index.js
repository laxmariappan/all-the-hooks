/**
 * All The Hooks - Admin React App
 */
import { render } from '@wordpress/element';
import AdminApp from './App';

// Render the app
const rootElement = document.getElementById( 'ath-admin-root' );
if ( rootElement ) {
	render( <AdminApp />, rootElement );
}
