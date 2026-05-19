import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ControlRenderer from './controls/ControlRenderer';

const PANEL_WIDGETS  = 'widgets';
const PANEL_SETTINGS = 'settings';
const PANEL_STYLE    = 'style';
const PANEL_ADVANCED = 'advanced';

function WidgetPanel( { widgets, onDragStart } ) {
	const [ search, setSearch ] = useState( '' );
	const [ openCats, setOpenCats ] = useState( {} );

	const categories = [
		{ key: 'general',       label: __( 'General', 'eltiz-builder' ) },
		{ key: 'layout',        label: __( 'Layout', 'eltiz-builder' ) },
		{ key: 'media',         label: __( 'Media', 'eltiz-builder' ) },
		{ key: 'interactive',   label: __( 'Interactive', 'eltiz-builder' ) },
		{ key: 'forms',         label: __( 'Forms', 'eltiz-builder' ) },
		{ key: 'woocommerce',   label: __( 'WooCommerce', 'eltiz-builder' ) },
		{ key: 'theme-elements',label: __( 'Theme Elements', 'eltiz-builder' ) },
	];

	const filtered = search
		? widgets.filter( w =>
			w.title.toLowerCase().includes( search.toLowerCase() ) ||
			( w.keywords || [] ).some( k => k.toLowerCase().includes( search.toLowerCase() ) )
		  )
		: null;

	const toggleCat = key => setOpenCats( prev => ( { ...prev, [ key ]: ! prev[ key ] } ) );

	return (
		<div className="eltiz-widget-panel">
			<div className="eltiz-widget-panel__search">
				<input
					type="search"
					placeholder={ __( 'Search widgets…', 'eltiz-builder' ) }
					value={ search }
					onChange={ e => setSearch( e.target.value ) }
					className="eltiz-widget-panel__search-input"
				/>
			</div>

			{ filtered ? (
				<div className="eltiz-widget-panel__grid">
					{ filtered.map( w => (
						<WidgetItem key={ w.name } widget={ w } onDragStart={ onDragStart } />
					) ) }
					{ filtered.length === 0 && (
						<p className="eltiz-widget-panel__empty">{ __( 'No widgets found.', 'eltiz-builder' ) }</p>
					) }
				</div>
			) : (
				categories.map( cat => {
					const catWidgets = widgets.filter( w => ( w.categories || [] ).includes( cat.key ) );
					if ( catWidgets.length === 0 ) return null;
					const isOpen = openCats[ cat.key ] !== false;
					return (
						<div key={ cat.key } className="eltiz-widget-category">
							<button
								className={ `eltiz-widget-category__header${ isOpen ? ' is-open' : '' }` }
								onClick={ () => toggleCat( cat.key ) }
							>
								<span>{ cat.label }</span>
								<span className="eltiz-widget-category__count">{ catWidgets.length }</span>
								<span className="eltiz-widget-category__arrow">▼</span>
							</button>
							{ isOpen && (
								<div className="eltiz-widget-panel__grid">
									{ catWidgets.map( w => (
										<WidgetItem key={ w.name } widget={ w } onDragStart={ onDragStart } />
									) ) }
								</div>
							) }
						</div>
					);
				} )
			) }
		</div>
	);
}

function WidgetItem( { widget, onDragStart } ) {
	return (
		<div
			className="eltiz-widget-item"
			draggable
			onDragStart={ e => onDragStart( e, widget.name ) }
			title={ widget.title }
		>
			<span className="eltiz-widget-item__icon">{ widget.icon || '⊞' }</span>
			<span className="eltiz-widget-item__label">{ widget.title }</span>
		</div>
	);
}


export default function Sidebar( {
	widgets,
	selectedElement,
	onDragStart,
	onUpdateElement,
	sidebarTab,
	onSidebarTabChange,
} ) {
	const [ activeSettingsTab, setActiveSettingsTab ] = useState( 'content' );

	const widget = widgets?.find( w => w.name === selectedElement?.widgetType );
	const controls = widget?.controls || [];

	const contentControls  = controls.filter( c => ! c.section || c.section === 'content' );
	const styleControls    = controls.filter( c => c.section === 'style' );
	const advancedControls = controls.filter( c => c.section === 'advanced' );

	const renderControls = useCallback( ( controlsList ) => {
		if ( ! controlsList.length ) {
			return <p className="eltiz-sidebar__no-controls">{ __( 'No controls available.', 'eltiz-builder' ) }</p>;
		}

		let currentSection = null;
		const groups = [];

		controlsList.forEach( ( ctrl, i ) => {
			if ( ctrl.type === 'section_start' ) {
				currentSection = { label: ctrl.label, controls: [] };
				groups.push( currentSection );
			} else if ( ctrl.type === 'section_end' ) {
				currentSection = null;
			} else {
				if ( currentSection ) {
					currentSection.controls.push( ctrl );
				} else {
					if ( ! groups.length || groups[ groups.length - 1 ].controls === undefined ) {
						groups.push( { label: null, controls: [] } );
					}
					groups[ groups.length - 1 ].controls.push( ctrl );
				}
			}
		} );

		return groups.map( ( group, gi ) => (
			<div key={ gi } className="eltiz-controls-section">
				{ group.label && (
					<div className="eltiz-controls-section__label">{ group.label }</div>
				) }
				{ group.controls.map( ctrl => (
					<ControlRenderer
						key={ ctrl.name }
						control={ ctrl }
						value={ selectedElement?.settings?.[ ctrl.name ] }
						onChange={ val => {
							if ( ! selectedElement ) return;
							onUpdateElement( selectedElement.id, {
								settings: { ...( selectedElement.settings || {} ), [ ctrl.name ]: val },
							} );
						} }
					/>
				) ) }
			</div>
		) );
	}, [ selectedElement, onUpdateElement ] );

	return (
		<aside className="eltiz-sidebar">
			<div className="eltiz-sidebar__tabs">
				<button
					className={ `eltiz-sidebar__tab${ sidebarTab === PANEL_WIDGETS ? ' is-active' : '' }` }
					onClick={ () => onSidebarTabChange( PANEL_WIDGETS ) }
					title={ __( 'Widgets', 'eltiz-builder' ) }
				>
					<svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" strokeWidth="1.5">
						<rect x="1" y="1" width="7" height="7" rx="1" />
						<rect x="10" y="1" width="7" height="7" rx="1" />
						<rect x="1" y="10" width="7" height="7" rx="1" />
						<rect x="10" y="10" width="7" height="7" rx="1" />
					</svg>
				</button>
				<button
					className={ `eltiz-sidebar__tab${ sidebarTab === PANEL_SETTINGS ? ' is-active' : '' }` }
					onClick={ () => onSidebarTabChange( PANEL_SETTINGS ) }
					disabled={ ! selectedElement }
					title={ __( 'Element Settings', 'eltiz-builder' ) }
				>
					<svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" strokeWidth="1.5">
						<circle cx="9" cy="9" r="3" />
						<path d="M9 1v2M9 15v2M1 9h2M15 9h2M3.2 3.2l1.4 1.4M13.4 13.4l1.4 1.4M3.2 14.8l1.4-1.4M13.4 4.6l1.4-1.4" />
					</svg>
				</button>
			</div>

			<div className="eltiz-sidebar__content">
				{ sidebarTab === PANEL_WIDGETS && (
					<WidgetPanel widgets={ widgets || [] } onDragStart={ onDragStart } />
				) }

				{ sidebarTab === PANEL_SETTINGS && selectedElement && (
					<div className="eltiz-settings-panel">
						<div className="eltiz-settings-panel__header">
							<span className="eltiz-settings-panel__title">
								{ widget?.title || __( 'Element', 'eltiz-builder' ) }
							</span>
						</div>

						<div className="eltiz-settings-panel__tabs">
							{ [ 'content', 'style', 'advanced' ].map( tab => (
								<button
									key={ tab }
									className={ `eltiz-settings-panel__tab${ activeSettingsTab === tab ? ' is-active' : '' }` }
									onClick={ () => setActiveSettingsTab( tab ) }
								>
									{ tab === 'content'  && __( 'Content', 'eltiz-builder' ) }
									{ tab === 'style'    && __( 'Style', 'eltiz-builder' ) }
									{ tab === 'advanced' && __( 'Advanced', 'eltiz-builder' ) }
								</button>
							) ) }
						</div>

						<div className="eltiz-settings-panel__controls">
							{ activeSettingsTab === 'content'  && renderControls( contentControls ) }
							{ activeSettingsTab === 'style'    && renderControls( styleControls ) }
							{ activeSettingsTab === 'advanced' && renderControls( advancedControls ) }
						</div>
					</div>
				) }

				{ sidebarTab === PANEL_SETTINGS && ! selectedElement && (
					<div className="eltiz-sidebar__empty">
						<p>{ __( 'Click an element on the canvas to edit it.', 'eltiz-builder' ) }</p>
					</div>
				) }
			</div>
		</aside>
	);
}
