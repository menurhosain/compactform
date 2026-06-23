/**
 * Generic controlled/uncontrolled Tabs primitive (context based).
 */
import { createContext, memo, useCallback, useContext, useRef, useState } from '@wordpress/element';

const TabsContext = createContext( null );

export function Tabs( { defaultValue, value, onValueChange, children } ) {
	const [ internalValue, setInternalValue ] = useState( defaultValue ?? '' );
	const active = value !== undefined ? value : internalValue;

	const handleChange = useCallback(
		( val ) => {
			if ( value === undefined ) {
				setInternalValue( val );
			}
			onValueChange?.( val );
		},
		[ value, onValueChange ]
	);

	return (
		<TabsContext.Provider value={ { active, setActive: handleChange } }>
			{ children }
		</TabsContext.Provider>
	);
}

export function TabList( { children } ) {
	const listRef = useRef( null );

	const handleKeyDown = useCallback( ( e ) => {
		const buttons = [ ...listRef.current.querySelectorAll( '[role=tab]:not([disabled])' ) ];
		const idx = buttons.indexOf( document.activeElement );
		if ( e.key === 'ArrowRight' ) {
			buttons[ ( idx + 1 ) % buttons.length ]?.focus();
		}
		if ( e.key === 'ArrowLeft' ) {
			buttons[ ( idx - 1 + buttons.length ) % buttons.length ]?.focus();
		}
		if ( e.key === 'Home' ) {
			e.preventDefault();
			buttons[ 0 ]?.focus();
		}
		if ( e.key === 'End' ) {
			e.preventDefault();
			buttons[ buttons.length - 1 ]?.focus();
		}
	}, [] );

	return (
		<div ref={ listRef } role="tablist" onKeyDown={ handleKeyDown } className="fcf7-tab-list">
			{ children }
		</div>
	);
}

export const Tab = memo( function Tab( { value, disabled, children } ) {
	const { active, setActive } = useContext( TabsContext );
	const isActive = active === value;

	return (
		<button
			role="tab"
			aria-selected={ isActive }
			tabIndex={ isActive ? 0 : -1 }
			disabled={ disabled }
			onClick={ () => ! disabled && setActive( value ) }
			className={ `fcf7-tab-item ${ isActive ? 'fcf7-tab-item-active' : '' }` }
		>
			{ children }
		</button>
	);
} );

export const TabPanel = memo( function TabPanel( { value, unmountOnHide = false, children } ) {
	const { active } = useContext( TabsContext );
	const isActive = active === value;

	if ( unmountOnHide && ! isActive ) {
		return null;
	}

	return (
		<div
			role="tabpanel"
			hidden={ ! isActive }
			style={ { animation: isActive ? 'fcf7TabFadeIn 0.18s ease' : undefined } }
			className="fcf7-tab-panel"
		>
			{ children }
		</div>
	);
} );
