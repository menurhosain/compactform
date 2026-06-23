import { hooks } from './field-preview-registry';

const components = {};

export const controls = {
	/** @param {string} type @param {Function} Component */
	registerComponent( type, Component ) {
		if ( ! type || typeof Component !== 'function' ) { return; }
		components[ type ] = Component;
	},

	/** @param {string} type @return {Function|null} */
	getComponent( type ) {
		return components[ type ] || null;
	},

	/** @return {string[]} Every registered control type. */
	getTypes() {
		return Object.keys( components );
	},
};

/**
 * Fired once at boot, after every enqueued script has run its top-level
 * hooks.addAction() calls. Safe to call more than once.
 */
export function collectThirdPartyControls() {
	hooks.doAction( 'fcf7b.registerControls', controls );
}
