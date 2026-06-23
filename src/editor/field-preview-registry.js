import { createHooks } from '@wordpress/hooks';

export const hooks = createHooks();

const components = {};
const handlerPreviews = {};

export const registry = {
	/** @param {string} type @param {Function} Component */
	registerComponent( type, Component ) {
		if ( ! type || typeof Component !== 'function' ) { return; }
		components[ type ] = Component;
	},

	/** @param {string} type @return {Function|null} */
	getComponent( type ) {
		return components[ type ] || null;
	},

	/** @param {string} type @param {FieldPreviewHandlers} handlers */
	register( type, handlers ) {
		if ( ! type || ! handlers || typeof handlers.mount !== 'function' ) { return; }
		handlerPreviews[ type ] = handlers;
	},

	/** @param {string} type @return {FieldPreviewHandlers|null} */
	get( type ) {
		return handlerPreviews[ type ] || null;
	},
};

export function collectThirdPartyFieldPreviews() {
	hooks.doAction( 'fcf7b.registerFieldPreviews', registry );
}
