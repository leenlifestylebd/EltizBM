import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Sidebar from './components/Sidebar';
import Canvas from './components/Canvas';
import Toolbar from './components/Toolbar';
import Navigator from './components/Navigator';
import ContextMenu from './components/ContextMenu';
import { useEditorStore } from './store';
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

	const [ widgets, setWidgets ]           = useState( {} );
	const [ categories, setCategories ]     = useState( {} );
	const [ globalStyles, setGlobalStyles ] = useState( {} );
	const [ showNavigator, setShowNavigator ] = useState( false );
	const [ contextMenu, setContextMenu ]   = useState( null );
	const [ activeTab, setActiveTab ]       = useState( 'widgets' );
	const [ panelView, setPanelView ]       = useState( 'widgets' ); // 'widgets' | 'settings'
	const [ notification, setNotification ] = useState( null );

	// ── Load initial data ──────────────────────────────────────────────────────
	useEffect( () => {
		setIsLoading( true );

		Promise.all( [
			apiFetch( { path: '/eltiz/v1/widgets' } ),
			apiFetch( { path: `/eltiz/v1/page/${ config.postId }` } ),
			apiFetch( { path: '/eltiz/v1/global-styles' } ),
		] ).then( ( [ widgetsData, pageData, stylesData ] ) => {
			setWidgets( widgetsData.widgets || {} );
			setCategories( widgetsData.categories || {} );
			if ( pageData.data && pageData.data.length ) {
				setElements( pageData.data );
			}
			setGlobalStyles( stylesData || {} );
		} ).catch( console.error ).finally( () => {
			setIsLoading( false );
		} );
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
	}, [ elements ] );

	const notify = ( message, type = 'info' ) => {
		setNotification( { message, type } );
		setTimeout( () => setNotification( null ), 3000 );
	};

	// ── Keyboard shortcuts ─────────────────────────────────────────────────────
	useKeyboardShortcuts( {
		'ctrl+s': ( e ) => { e.preventDefault(); save( 'publish' ); },
		'ctrl+z': ( e ) => { e.preventDefault(); undo(); },
		'ctrl+y': ( e ) => { e.preventDefault(); redo(); },
		'ctrl+shift+z': ( e ) => { e.preventDefault(); redo(); },
		'ctrl+shift+n': () => setShowNavigator( v => ! v ),
		'Escape': () => { setSelectedId( null ); setContextMenu( null ); },
	} );

	// ── Auto-save ─────────────────────────────────────────────────────────────
	useAutoSave( isDirty, () => save( 'draft' ), 60000 );

	const handleContextMenu = ( e, elementId ) => {
		e.preventDefault();
		setContextMenu( { x: e.clientX, y: e.clientY, elementId } );
	};

	if ( isLoading ) {
		return (
			<div className="eltiz-editor-loading">
				<div className="eltiz-editor-loading__spinner" />
				<p>{ __( 'Loading editor…', 'eltiz-builder' ) }</p>
			</div>
		);
	}

	return (
		<div className="eltiz-editor" data-device={ device }>
			<Toolbar
				onSave={ save }
				isSaving={ isSaving }
				isDirty={ isDirty }
				device={ device }
				onDeviceChange={ setDevice }
				canUndo={ historyIndex > 0 }
				canRedo={ historyIndex < history.length - 1 }
				onUndo={ undo }
				onRedo={ redo }
				onToggleNavigator={ () => setShowNavigator( v => ! v ) }
				postId={ config.postId }
				postTitle={ config.postTitle }
			/>

			<div className="eltiz-editor-main">
				<Sidebar
					widgets={ widgets }
					categories={ categories }
					globalStyles={ globalStyles }
					selectedId={ selectedId }
					elements={ elements }
					onElementsChange={ setElements }
					activeTab={ activeTab }
					onTabChange={ setActiveTab }
					panelView={ panelView }
					onPanelViewChange={ setPanelView }
				/>

				<Canvas
					elements={ elements }
					onElementsChange={ setElements }
					selectedId={ selectedId }
					onSelect={ setSelectedId }
					device={ device }
					onContextMenu={ handleContextMenu }
				/>
			</div>

			{ showNavigator && (
				<Navigator
					elements={ elements }
					selectedId={ selectedId }
					onSelect={ setSelectedId }
					onClose={ () => setShowNavigator( false ) }
					onElementsChange={ setElements }
				/>
			) }

			{ contextMenu && (
				<ContextMenu
					x={ contextMenu.x }
					y={ contextMenu.y }
					elementId={ contextMenu.elementId }
					elements={ elements }
					onElementsChange={ setElements }
					onClose={ () => setContextMenu( null ) }
					onSelect={ setSelectedId }
				/>
			) }

			{ notification && (
				<div className={ `eltiz-notification eltiz-notification--${ notification.type }` } role="alert">
					{ notification.message }
				</div>
			) }
		</div>
	);
}
