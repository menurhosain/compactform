import { createRoot } from '@wordpress/element';
import './index.scss';
import App from './App';
import { hooks } from './dashboard-hooks';

window.FCF7Dashboard = window.FCF7Dashboard || {};
window.FCF7Dashboard.hooks = hooks;

const initApp = () => {
	const container = document.getElementById( 'fcf7-dashboard' );
	if ( ! container ) {
		return;
	}

	const root = createRoot( container );
	root.render( <App /> );
};

document.addEventListener( 'DOMContentLoaded', initApp );
