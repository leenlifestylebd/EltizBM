import { useState, useCallback, useRef } from '@wordpress/element';

const MAX_HISTORY = 50;

let _state = {
	elements:       [],
	selectedId:     null,
	device:         'desktop',
	isLoading:      false,
	isDirty:        false,
	isSaving:       false,
	history:        [],
	historyIndex:   -1,
};

let _listeners = [];

function setState( patch ) {
	_state = { ..._state, ...( typeof patch === 'function' ? patch( _state ) : patch ) };
	_listeners.forEach( fn => fn( _state ) );
}

function subscribe( fn ) {
	_listeners.push( fn );
	return () => { _listeners = _listeners.filter( l => l !== fn ); };
}

function getState() { return _state; }

// ─── Exported hook ────────────────────────────────────────────────────────────
export function useEditorStore() {
	const [ , rerender ] = useState( 0 );

	useCallback( () => {
		const unsub = subscribe( () => rerender( n => n + 1 ) );
		return unsub;
	}, [] )();

	const pushHistory = useCallback( ( newElements ) => {
		const { history, historyIndex } = getState();
		const truncated = history.slice( 0, historyIndex + 1 );
		const next      = [ ...truncated, newElements ].slice( -MAX_HISTORY );
		setState( { history: next, historyIndex: next.length - 1 } );
	}, [] );

	const setElements = useCallback( ( newElements ) => {
		const elements = typeof newElements === 'function'
			? newElements( getState().elements )
			: newElements;
		pushHistory( elements );
		setState( { elements, isDirty: true } );
	}, [ pushHistory ] );

	const undo = useCallback( () => {
		const { history, historyIndex } = getState();
		if ( historyIndex <= 0 ) return;
		const idx = historyIndex - 1;
		setState( { elements: history[ idx ], historyIndex: idx, isDirty: true } );
	}, [] );

	const redo = useCallback( () => {
		const { history, historyIndex } = getState();
		if ( historyIndex >= history.length - 1 ) return;
		const idx = historyIndex + 1;
		setState( { elements: history[ idx ], historyIndex: idx, isDirty: true } );
	}, [] );

	const s = getState();
	return {
		...s,
		setElements,
		setSelectedId:   ( id ) => setState( { selectedId: id } ),
		setDevice:       ( d )  => setState( { device: d } ),
		setIsLoading:    ( v )  => setState( { isLoading: v } ),
		setIsDirty:      ( v )  => setState( { isDirty: v } ),
		setIsSaving:     ( v )  => setState( { isSaving: v } ),
		undo,
		redo,
	};
}

// ─── Element helpers ──────────────────────────────────────────────────────────
export function generateId() {
	return Math.random().toString( 36 ).substr( 2, 9 );
}

export function findElement( elements, id ) {
	for ( const el of elements ) {
		if ( el.id === id ) return el;
		if ( el.children ) {
			const found = findElement( el.children, id );
			if ( found ) return found;
		}
	}
	return null;
}

export function updateElement( elements, id, patch ) {
	return elements.map( el => {
		if ( el.id === id ) {
			return { ...el, ...( typeof patch === 'function' ? patch( el ) : patch ) };
		}
		if ( el.children ) {
			return { ...el, children: updateElement( el.children, id, patch ) };
		}
		return el;
	} );
}

export function removeElement( elements, id ) {
	return elements
		.filter( el => el.id !== id )
		.map( el => el.children
			? { ...el, children: removeElement( el.children, id ) }
			: el
		);
}

export function duplicateElement( elements, id ) {
	const clone = ( el ) => ( {
		...el,
		id: generateId(),
		children: el.children ? el.children.map( clone ) : undefined,
	} );

	const result = [];
	for ( const el of elements ) {
		result.push( el );
		if ( el.id === id ) {
			result.push( clone( el ) );
		} else if ( el.children ) {
			result[ result.length - 1 ] = {
				...el,
				children: duplicateElement( el.children, id ),
			};
		}
	}
	return result;
}
