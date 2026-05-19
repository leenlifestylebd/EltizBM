import { useState, useCallback, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

function DropZone( { containerId, index, onDragOver, onDrop, isOver } ) {
	return (
		<div
			className={ `eltiz-drop-zone${ isOver ? ' is-over' : '' }` }
			onDragOver={ e => { e.preventDefault(); onDragOver( e, containerId || null ); } }
			onDrop={ e => onDrop( e, containerId || null, index ) }
		/>
	);
}

function ElementToolbar( { onEdit, onDuplicate, onDelete, onDragStart, element } ) {
	return (
		<div className="eltiz-element-toolbar">
			<span
				className="eltiz-element-toolbar__handle"
				draggable
				onDragStart={ e => onDragStart( e, element.widgetType, element.id ) }
				title={ __( 'Drag to reorder', 'eltiz-builder' ) }
			>⠿</span>
			<button className="eltiz-element-toolbar__btn" onClick={ onEdit } title={ __( 'Edit', 'eltiz-builder' ) }>✏</button>
			<button className="eltiz-element-toolbar__btn" onClick={ onDuplicate } title={ __( 'Duplicate', 'eltiz-builder' ) }>⧉</button>
			<button className="eltiz-element-toolbar__btn eltiz-element-toolbar__btn--delete" onClick={ onDelete } title={ __( 'Delete', 'eltiz-builder' ) }>✕</button>
		</div>
	);
}

function WidgetPreview( { element, widgets } ) {
	const widget = widgets?.find( w => w.name === element.widgetType );
	const settings = element.settings || {};

	if ( ! widget ) {
		return (
			<div className="eltiz-widget-preview eltiz-widget-preview--unknown">
				<span>{ element.widgetType }</span>
			</div>
		);
	}

	return (
		<div className="eltiz-widget-preview" data-widget={ element.widgetType }>
			<div className="eltiz-widget-preview__badge">{ widget.title }</div>
			<WidgetPreviewContent widget={ widget } settings={ settings } />
		</div>
	);
}

function WidgetPreviewContent( { widget, settings } ) {
	switch ( widget.name ) {
		case 'heading':
			return <div className="eltiz-preview-heading"><HeadingTag tag={ settings.tag || 'h2' }>{ settings.title || widget.title }</HeadingTag></div>;
		case 'text-editor':
			return <div className="eltiz-preview-text" dangerouslySetInnerHTML={ { __html: settings.editor || '<p>Text content here…</p>' } } />;
		case 'image':
			return settings.image?.url
				? <img src={ settings.image.url } alt={ settings.image.alt || '' } className="eltiz-preview-image" />
				: <div className="eltiz-preview-image-placeholder">🖼 { __( 'Image', 'eltiz-builder' ) }</div>;
		case 'button':
			return <button className="eltiz-preview-button" style={ { backgroundColor: settings.button_background_color || '#6366f1', color: settings.text_color || '#fff' } }>{ settings.text || __( 'Click Here', 'eltiz-builder' ) }</button>;
		case 'divider':
			return <hr className="eltiz-preview-divider" style={ { borderColor: settings.color || '#e5e7eb' } } />;
		case 'spacer':
			return <div className="eltiz-preview-spacer" style={ { height: `${ settings.space?.size || 50 }${ settings.space?.unit || 'px' }` } }><span>{ __( 'Spacer', 'eltiz-builder' ) }</span></div>;
		case 'video':
			return <div className="eltiz-preview-video">▶ { __( 'Video', 'eltiz-builder' ) }</div>;
		case 'google-maps':
			return <div className="eltiz-preview-map">📍 { __( 'Google Map', 'eltiz-builder' ) }</div>;
		case 'icon':
			return <div className="eltiz-preview-icon">{ settings.selected_icon || '★' }</div>;
		case 'counter':
			return <div className="eltiz-preview-counter"><span className="eltiz-preview-counter__number">{ settings.ending_number || 100 }</span><span>{ settings.suffix || '' }</span></div>;
		case 'countdown':
			return <div className="eltiz-preview-countdown">⏱ { __( 'Countdown Timer', 'eltiz-builder' ) }</div>;
		case 'accordion':
			return <div className="eltiz-preview-accordion">{ __( 'Accordion', 'eltiz-builder' ) } ({ ( settings.tabs || [] ).length || 3 } { __( 'items', 'eltiz-builder' ) })</div>;
		case 'tabs':
			return <div className="eltiz-preview-tabs">{ __( 'Tabs', 'eltiz-builder' ) } ({ ( settings.tabs || [] ).length || 3 } { __( 'tabs', 'eltiz-builder' ) })</div>;
		case 'gallery':
			return <div className="eltiz-preview-gallery">🖼 { __( 'Gallery', 'eltiz-builder' ) } ({ ( settings.gallery || [] ).length || 0 } { __( 'images', 'eltiz-builder' ) })</div>;
		case 'posts':
		case 'loop-grid':
			return <div className="eltiz-preview-posts">📄 { widget.title }</div>;
		case 'loop-carousel':
			return <div className="eltiz-preview-carousel">🎠 { __( 'Loop Carousel', 'eltiz-builder' ) }</div>;
		case 'form':
			return <div className="eltiz-preview-form">📝 { __( 'Form', 'eltiz-builder' ) } ({ ( settings.form_fields || [] ).length || 0 } { __( 'fields', 'eltiz-builder' ) })</div>;
		case 'search-form':
			return <div className="eltiz-preview-search">🔍 { __( 'Search', 'eltiz-builder' ) }</div>;
		case 'social-icons':
			return <div className="eltiz-preview-social">{ __( 'Social Icons', 'eltiz-builder' ) }</div>;
		case 'pricing-table':
			return <div className="eltiz-preview-pricing">💳 { __( 'Pricing Table', 'eltiz-builder' ) }</div>;
		case 'testimonials':
			return <div className="eltiz-preview-testimonials">💬 { __( 'Testimonials', 'eltiz-builder' ) }</div>;
		case 'progress-bar':
			return (
				<div className="eltiz-preview-progress">
					<div className="eltiz-preview-progress__bar" style={ { width: `${ settings.percent?.size || 50 }%`, backgroundColor: settings.bar_color || '#6366f1' } } />
				</div>
			);
		case 'html':
		case 'shortcode':
			return <div className="eltiz-preview-html"><code>{ settings.html || settings.shortcode || `[${ widget.name }]` }</code></div>;
		default:
			return <div className="eltiz-preview-generic">{ widget.title }</div>;
	}
}

function HeadingTag( { tag, children, ...props } ) {
	const Tag = [ 'h1','h2','h3','h4','h5','h6' ].includes( tag ) ? tag : 'h2';
	return <Tag { ...props }>{ children }</Tag>;
}

function ContainerElement( {
	element,
	elements,
	selectedId,
	widgets,
	dragOver,
	draggingId,
	onSelect,
	onDuplicate,
	onDelete,
	onDragStart,
	onDragEnd,
	onDrop,
	onDragOver,
	onContextMenu,
} ) {
	const isSelected    = selectedId === element.id;
	const isDragging    = draggingId === element.id;
	const children      = element.children || [];

	const handleSelect = e => {
		e.stopPropagation();
		onSelect( element.id );
	};

	const handleContextMenu = e => {
		e.preventDefault();
		e.stopPropagation();
		onContextMenu( e, element.id );
	};

	if ( element.type === 'widget' ) {
		return (
			<div
				className={ `eltiz-canvas-element eltiz-canvas-widget${ isSelected ? ' is-selected' : '' }${ isDragging ? ' is-dragging' : '' }` }
				onClick={ handleSelect }
				onContextMenu={ handleContextMenu }
				data-element-id={ element.id }
			>
				{ isSelected && (
					<ElementToolbar
						element={ element }
						onEdit={ () => onSelect( element.id ) }
						onDuplicate={ () => onDuplicate( element.id ) }
						onDelete={ () => onDelete( element.id ) }
						onDragStart={ onDragStart }
					/>
				) }
				<WidgetPreview element={ element } widgets={ widgets } />
			</div>
		);
	}

	return (
		<div
			className={ `eltiz-canvas-element eltiz-canvas-container${ isSelected ? ' is-selected' : '' }${ isDragging ? ' is-dragging' : '' }` }
			onClick={ handleSelect }
			onContextMenu={ handleContextMenu }
			data-element-id={ element.id }
		>
			{ isSelected && (
				<ElementToolbar
					element={ element }
					onEdit={ () => onSelect( element.id ) }
					onDuplicate={ () => onDuplicate( element.id ) }
					onDelete={ () => onDelete( element.id ) }
					onDragStart={ onDragStart }
				/>
			) }

			<DropZone
				containerId={ element.id }
				index={ 0 }
				onDragOver={ onDragOver }
				onDrop={ onDrop }
				isOver={ dragOver === element.id && children.length === 0 }
			/>

			{ children.map( ( child, i ) => (
				<Fragment key={ child.id }>
					<ContainerElement
						element={ child }
						elements={ elements }
						selectedId={ selectedId }
						widgets={ widgets }
						dragOver={ dragOver }
						draggingId={ draggingId }
						onSelect={ onSelect }
						onDuplicate={ onDuplicate }
						onDelete={ onDelete }
						onDragStart={ onDragStart }
						onDragEnd={ onDragEnd }
						onDrop={ onDrop }
						onDragOver={ onDragOver }
						onContextMenu={ onContextMenu }
					/>
					<DropZone
						containerId={ element.id }
						index={ i + 1 }
						onDragOver={ onDragOver }
						onDrop={ onDrop }
						isOver={ dragOver === `${ element.id }-${ i + 1 }` }
					/>
				</Fragment>
			) ) }
		</div>
	);
}

export default function Canvas( {
	elements,
	selectedId,
	widgets,
	deviceView,
	dragOver,
	draggingId,
	onSelect,
	onDuplicate,
	onDelete,
	onDragStart,
	onDragEnd,
	onDrop,
	onDragOver,
	onContextMenu,
} ) {
	const deviceWidths = {
		desktop: '100%',
		tablet:  '768px',
		mobile:  '375px',
	};

	const handleCanvasClick = e => {
		if ( e.target === e.currentTarget ) {
			onSelect( null );
		}
	};

	return (
		<div className="eltiz-canvas-wrapper">
			<div
				className={ `eltiz-canvas eltiz-canvas--${ deviceView }` }
				style={ { maxWidth: deviceWidths[ deviceView ] || '100%' } }
				onClick={ handleCanvasClick }
				onDragOver={ e => { e.preventDefault(); onDragOver( e, null ); } }
				onDrop={ e => onDrop( e, null, ( elements || [] ).length ) }
			>
				{ ( elements || [] ).length === 0 && (
					<div className="eltiz-canvas__empty">
						<div className="eltiz-canvas__empty-icon">⊞</div>
						<p>{ __( 'Drag widgets here to start building', 'eltiz-builder' ) }</p>
					</div>
				) }

				{ ( elements || [] ).length > 0 && (
					<DropZone
						containerId={ null }
						index={ 0 }
						onDragOver={ onDragOver }
						onDrop={ onDrop }
						isOver={ dragOver === 'root-0' }
					/>
				) }

				{ ( elements || [] ).map( ( element, i ) => (
					<Fragment key={ element.id }>
						<ContainerElement
							element={ element }
							elements={ elements }
							selectedId={ selectedId }
							widgets={ widgets }
							dragOver={ dragOver }
							draggingId={ draggingId }
							onSelect={ onSelect }
							onDuplicate={ onDuplicate }
							onDelete={ onDelete }
							onDragStart={ onDragStart }
							onDragEnd={ onDragEnd }
							onDrop={ onDrop }
							onDragOver={ onDragOver }
							onContextMenu={ onContextMenu }
						/>
						<DropZone
							containerId={ null }
							index={ i + 1 }
							onDragOver={ onDragOver }
							onDrop={ onDrop }
							isOver={ dragOver === `root-${ i + 1 }` }
						/>
					</Fragment>
				) ) }
			</div>
		</div>
	);
}
