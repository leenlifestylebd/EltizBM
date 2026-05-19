import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

function NavItem( { element, depth, selectedId, onSelect, onToggle, expanded, widgets } ) {
	const isExpanded = expanded[ element.id ] !== false;
	const hasChildren = element.children && element.children.length > 0;
	const widget = widgets?.find( w => w.name === element.widgetType );
	const label = widget?.title || element.type || element.id;

	return (
		<li className={ `eltiz-nav-item${ selectedId === element.id ? ' is-selected' : '' }` }>
			<div
				className="eltiz-nav-item__row"
				style={ { paddingLeft: `${ 8 + depth * 16 }px` } }
				onClick={ () => onSelect( element.id ) }
			>
				{ hasChildren && (
					<button
						className={ `eltiz-nav-item__toggle${ isExpanded ? ' is-expanded' : '' }` }
						onClick={ e => { e.stopPropagation(); onToggle( element.id ); } }
					>
						▶
					</button>
				) }
				{ ! hasChildren && <span className="eltiz-nav-item__spacer" /> }
				<span className="eltiz-nav-item__icon">
					{ element.type === 'container' ? '□' : '⊞' }
				</span>
				<span className="eltiz-nav-item__label">{ label }</span>
			</div>
			{ hasChildren && isExpanded && (
				<ul className="eltiz-nav-list">
					{ element.children.map( child => (
						<NavItem
							key={ child.id }
							element={ child }
							depth={ depth + 1 }
							selectedId={ selectedId }
							onSelect={ onSelect }
							onToggle={ onToggle }
							expanded={ expanded }
							widgets={ widgets }
						/>
					) ) }
				</ul>
			) }
		</li>
	);
}

export default function Navigator( { elements, selectedId, onSelect, widgets } ) {
	const [ expanded, setExpanded ] = useState( {} );

	const toggleExpanded = useCallback( id => {
		setExpanded( prev => ( { ...prev, [ id ]: prev[ id ] === false } ) );
	}, [] );

	if ( ! elements || elements.length === 0 ) {
		return (
			<div className="eltiz-navigator eltiz-navigator--empty">
				<p>{ __( 'No elements yet. Drag widgets to the canvas.', 'eltiz-builder' ) }</p>
			</div>
		);
	}

	return (
		<div className="eltiz-navigator">
			<div className="eltiz-navigator__header">
				<span>{ __( 'Structure', 'eltiz-builder' ) }</span>
			</div>
			<ul className="eltiz-nav-list eltiz-nav-list--root">
				{ elements.map( el => (
					<NavItem
						key={ el.id }
						element={ el }
						depth={ 0 }
						selectedId={ selectedId }
						onSelect={ onSelect }
						onToggle={ toggleExpanded }
						expanded={ expanded }
						widgets={ widgets }
					/>
				) ) }
			</ul>
		</div>
	);
}
