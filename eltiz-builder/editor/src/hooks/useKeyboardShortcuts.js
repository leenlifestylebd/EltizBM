import { useEffect } from '@wordpress/element';

export function useKeyboardShortcuts( shortcuts ) {
	useEffect( () => {
		const handler = ( e ) => {
			const key = [
				e.ctrlKey || e.metaKey ? 'ctrl' : '',
				e.shiftKey ? 'shift' : '',
				e.altKey ? 'alt' : '',
				e.key,
			].filter( Boolean ).join( '+' );

			const callback = shortcuts[ key ] || shortcuts[ e.key ];
			if ( callback ) {
				callback( e );
			}
		};

		document.addEventListener( 'keydown', handler );
		return () => document.removeEventListener( 'keydown', handler );
	}, [ shortcuts ] );
}
