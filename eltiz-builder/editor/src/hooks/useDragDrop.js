import { useState, useRef, useCallback } from '@wordpress/element';
import { generateId } from '../store';

export function useDragDrop( elements, onElementsChange ) {
	const [ draggingId, setDraggingId ]   = useState( null );
	const [ dragOver, setDragOver ]       = useState( null );
	const dragDataRef = useRef( null );

	const onDragStart = useCallback( ( e, widgetType, existingId = null ) => {
		const id = existingId || `new::${ widgetType }`;
		dragDataRef.current = { widgetType, existingId };
		setDraggingId( id );
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData( 'text/plain', id );
	}, [] );

	const onDragEnd = useCallback( () => {
		setDraggingId( null );
		setDragOver( null );
		dragDataRef.current = null;
	}, [] );

	const onDrop = useCallback( ( e, containerId, index ) => {
		e.preventDefault();
		e.stopPropagation();

		const data = dragDataRef.current;
		if ( ! data ) return;

		if ( data.existingId ) {
			// Move existing element.
			onElementsChange( prev => {
				const without = removeById( prev, data.existingId );
				const element = findById( elements, data.existingId );
				if ( ! element ) return prev;
				return insertAt( without, element, containerId, index );
			} );
		} else {
			// Drop new widget from panel.
			const newElement = {
				id: generateId(),
				type: 'widget',
				widgetType: data.widgetType,
				settings: {},
				children: [],
			};
			onElementsChange( prev => insertAt( prev, newElement, containerId, index ) );
		}

		setDragOver( null );
	}, [ elements, onElementsChange ] );

	const onDragOver = useCallback( ( e, id ) => {
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
		setDragOver( id );
	}, [] );

	return { draggingId, dragOver, onDragStart, onDragEnd, onDrop, onDragOver };
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function findById( elements, id ) {
	for ( const el of elements ) {
		if ( el.id === id ) return el;
		if ( el.children ) {
			const found = findById( el.children, id );
			if ( found ) return found;
		}
	}
	return null;
}

function removeById( elements, id ) {
	return elements
		.filter( el => el.id !== id )
		.map( el => el.children
			? { ...el, children: removeById( el.children, id ) }
			: el
		);
}

function insertAt( elements, element, containerId, index ) {
	if ( ! containerId ) {
		// Root level insertion.
		const arr = [ ...elements ];
		arr.splice( index, 0, element );
		return arr;
	}

	return elements.map( el => {
		if ( el.id === containerId ) {
			const children = [ ...( el.children || [] ) ];
			children.splice( index, 0, element );
			return { ...el, children };
		}
		if ( el.children ) {
			return { ...el, children: insertAt( el.children, element, containerId, index ) };
		}
		return el;
	} );
}
