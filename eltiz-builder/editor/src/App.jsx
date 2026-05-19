import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Sidebar from './components/Sidebar';
import Canvas from './components/Canvas';
import Toolbar from './components/Toolbar';
import Navigator from './components/Navigator';
import ContextMenu from './components/ContextMenu';
import { useEditorStore } from './store';
import { duplicateElement, removeElement } from './store';
import { useKeyboardShortcuts } from './hooks/useKeyboardShortcuts';
import { useAutoSave } from './hooks/useAutoSave';

const config = window.eltizEditorConfig || {};

export default function App() {
	const {
		elements, setElements,
		selectedId, setSelectedId,
		device, setDevice,
		isLoading, setIsLoading,
		isDirty, setIsDirty,
		isSaving, setIsSaving,
		history, undo, redo,
		historyIndex,
	} = useEditorStore();

	const [ widgets, setWidgets ]             = useState( [] );
	const [ globalStyles, setGlobalStyles ]   = useState( {} );
	const [ showNavigator, setShowNavigator ] = useState( false );
	const [ contextMenu, setContextMenu ]     = useState( null );
	const [ sidebarTab, setSidebarTab ]       = useState( 'widgets' );
	const [ notification, setNotification ]   = useState( null );

	// ── Load initial data ──────────────────────────────────────────────────────
	useEffect( () => {
		setIsLoading( true );

		Promise.all( [
			apiFetch( { path: '/eltiz/v1/widgets' } ),
			apiFetch( { path: `/eltiz/v1/page/${ config.postId }` } ),
			apiFetch( { path: '/eltiz/v1/global-styles' } ),
		] ).then( ( [ widgetsData, pageData, stylesData ] ) => {
			// widgets endpoint returns an object keyed by name — flatten to array
			const widgetList = widgetsData.widgets
				? Object.values( widgetsData.widgets )
				: [];
			setWidgets( widgetList );
			if ( pageData.data && pageData.data.length ) {
				setElements( pageData.data );
			}
			setGlobalStyles( stylesData || {} );
		} ).catch( console.error ).finally( () => {
			setIsLoading( false );
		} );
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	// ── Notify helper ─────────────────────────────────────────────────────────
	const notify = useCallback( ( message, type = 'info' ) => {
		setNotification( { message, type } );
		setTimeout( () => setNotification( null ), 3000 );
	}, [] );

	// ── Save ──────────────────────────────────────────────────────────────────
	const save = useCallback( async ( status = 'draft' ) => {
		setIsSaving( true );
		try {
			await apiFetch( {
				path:   `/eltiz/v1/page/${ config.postId }`,
				method: 'POST',
				data:   { data: JSON.stringify( elements ), status },
			} );
			setIsDirty( false );
			notify( __( 'Saved!', 'eltiz-builder' ), 'success' );
		} catch ( err ) {
			notify( __( 'Save failed.', 'eltiz-builder' ), 'error' );
		} finally {
			setIsSaving( false );
		}
	}, [ elements, notify, setIsDirty, setIsSaving ] );

	// ── Keyboard shortcuts — memoized so the object ref is stable ─────────────
	const shortcuts = useMemo( () => ( {
		'ctrl+s':       ( e ) => { e.preventDefault(); save( 'publish' ); },
		'ctrl+z':       ( e ) => { e.preventDefault(); undo(); },
		'ctrl+y':       ( e ) => { e.preventDefault(); redo(); },
		'ctrl+shift+z': ( e ) => { e.preventDefault(); redo(); },
		'ctrl+shift+n': () => setShowNavigator( v => ! v ),
		'Escape':       () => { setSelectedId( null ); setContextMenu( null ); },
	} ), [ save, undo, redo ] ); // eslint-disable-line react-hooks/exhaustive-deps

	useKeyboardShortcuts( shortcuts );

	// ── Auto-save ─────────────────────────────────────────────────────────────
	useAutoSave( isDirty, () => save( 'draft' ), 60000 );

	// ── Context menu ──────────────────────────────────────────────────────────
	const handleContextMenu = useCallback( ( e, elementId ) => {
		e.preventDefault();
		setContextMenu( { x: e.clientX, y: e.clientY, elementId } );
	}, [] );

	const handleDuplicate = useCallback( ( id ) => {
		setElements( prev => duplicateElement( prev, id ) );
	}, [ setElements ] );

	const handleDelete = useCallback( ( id ) => {
		setElements( prev => removeElement( prev, id ) );
		if ( selectedId === id ) setSelectedId( null );
	}, [ setElements, selectedId, setSelectedId ] );

	// ── Update a single element's properties ──────────────────────────────────
	const handleUpdateElement = useCallback( ( id, patch ) => {
		setElements( prev => updateElementInTree( prev, id, patch ) );
	}, [ setElements ] );

	if ( isLoading ) {
		return (
			<div className="eltiz-editor-loading">
				<div className="eltiz-editor-loading__spinner" />
				<p>{ __( 'Loading editor…', 'eltiz-builder' ) }</p>
			</div>
		);
	}

	const selectedElement = selectedId
		? findElementInTree( elements, selectedId )
		: null;

	return (
		<div className="eltiz-editor" data-device={ device }>
			<Toolbar
				onSave={ save }
				onPublish={ () => save( 'publish' ) }
				isSaving={ isSaving }
				isDirty={ isDirty }
				deviceView={ device }
				onDeviceChange={ setDevice }
				canUndo={ historyIndex > 0 }
				canRedo={ historyIndex < history.length - 1 }
				onUndo={ undo }
				onRedo={ redo }
				postId={ config.postId }
			/>

			<div className="eltiz-editor-main">
				<Sidebar
					widgets={ widgets }
					selectedElement={ selectedElement }
					onDragStart={ ( e, widgetType ) => e.dataTransfer.setData( 'text/plain', widgetType ) }
					onUpdateElement={ handleUpdateElement }
					sidebarTab={ sidebarTab }
					onSidebarTabChange={ tab => {
						setSidebarTab( tab );
					} }
				/>

				<Canvas
					elements={ elements || [] }
					selectedId={ selectedId }
					widgets={ widgets }
					deviceView={ device }
					dragOver={ null }
					draggingId={ null }
					onSelect={ setSelectedId }
					onDuplicate={ handleDuplicate }
					onDelete={ handleDelete }
					onDragStart={ () => {} }
					onDragEnd={ () => {} }
					onDrop={ () => {} }
					onDragOver={ () => {} }
					onContextMenu={ handleContextMenu }
				/>

				{ showNavigator && (
					<Navigator
						elements={ elements || [] }
						selectedId={ selectedId }
						onSelect={ setSelectedId }
						widgets={ widgets }
					/>
				) }
			</div>

			{ contextMenu && (
				<ContextMenu
					x={ contextMenu.x }
					y={ contextMenu.y }
					elementId={ contextMenu.elementId }
					onClose={ () => setContextMenu( null ) }
					onEdit={ ( id ) => {
						setSelectedId( id );
						setSidebarTab( 'settings' );
						setContextMenu( null );
					} }
					onDuplicate={ handleDuplicate }
					onDelete={ handleDelete }
				/>
			) }

			{ notification && (
				<div
					className={ `eltiz-notifications` }
					role="alert"
					aria-live="polite"
				>
					<div className={ `eltiz-notification eltiz-notification--${ notification.type }` }>
						{ notification.message }
					</div>
				</div>
			) }
		</div>
	);
}

// ── Tree helpers ───────────────────────────────────────────────────────────────

function findElementInTree( elements, id ) {
	for ( const el of elements ) {
		if ( el.id === id ) return el;
		if ( el.children?.length ) {
			const found = findElementInTree( el.children, id );
			if ( found ) return found;
		}
	}
	return null;
}

function updateElementInTree( elements, id, patch ) {
	return elements.map( el => {
		if ( el.id === id ) return { ...el, ...patch };
		if ( el.children?.length ) {
			return { ...el, children: updateElementInTree( el.children, id, patch ) };
		}
		return el;
	} );
}
