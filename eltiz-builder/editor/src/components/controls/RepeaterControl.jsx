import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { generateId } from '../../store';
import ControlRenderer from './ControlRenderer';

export default function RepeaterControl( { control, value, onChange } ) {
	const [ openIndex, setOpenIndex ] = useState( null );
	const items = Array.isArray( value ) ? value : [];
	const fields = control.fields || [];

	const addItem = () => {
		const defaults = {};
		fields.forEach( f => { defaults[ f.name ] = f.default ?? ''; } );
		const newItems = [ ...items, { _id: generateId(), ...defaults } ];
		onChange( newItems );
		setOpenIndex( newItems.length - 1 );
	};

	const removeItem = i => {
		const newItems = items.filter( ( _, idx ) => idx !== i );
		onChange( newItems );
		if ( openIndex === i ) setOpenIndex( null );
	};

	const updateItem = ( i, key, val ) => {
		const newItems = items.map( ( item, idx ) =>
			idx === i ? { ...item, [ key ]: val } : item
		);
		onChange( newItems );
	};

	const moveItem = ( i, dir ) => {
		const j = i + dir;
		if ( j < 0 || j >= items.length ) return;
		const newItems = [ ...items ];
		[ newItems[ i ], newItems[ j ] ] = [ newItems[ j ], newItems[ i ] ];
		onChange( newItems );
		setOpenIndex( j );
	};

	return (
		<div className="eltiz-repeater">
			{ items.map( ( item, i ) => (
				<div key={ item._id || i } className={ `eltiz-repeater__item${ openIndex === i ? ' is-open' : '' }` }>
					<div className="eltiz-repeater__item-header">
						<button
							type="button"
							className="eltiz-repeater__toggle"
							onClick={ () => setOpenIndex( openIndex === i ? null : i ) }
						>
							<span>{ control.item_label ? ( item[ control.item_label ] || `${ __( 'Item', 'eltiz-builder' ) } ${ i + 1 }` ) : `${ __( 'Item', 'eltiz-builder' ) } ${ i + 1 }` }</span>
							<span>{ openIndex === i ? '▲' : '▼' }</span>
						</button>
						<div className="eltiz-repeater__item-actions">
							<button type="button" onClick={ () => moveItem( i, -1 ) } disabled={ i === 0 } title={ __( 'Move up', 'eltiz-builder' ) }>↑</button>
							<button type="button" onClick={ () => moveItem( i, 1 ) } disabled={ i === items.length - 1 } title={ __( 'Move down', 'eltiz-builder' ) }>↓</button>
							<button type="button" className="eltiz-repeater__remove" onClick={ () => removeItem( i ) } title={ __( 'Remove', 'eltiz-builder' ) }>✕</button>
						</div>
					</div>

					{ openIndex === i && (
						<div className="eltiz-repeater__item-body">
							{ fields.map( field => (
								<div key={ field.name } className={ `eltiz-control eltiz-control--${ field.type }` }>
									{ field.label && <label className="eltiz-control__label">{ field.label }</label> }
									<ControlRenderer
										control={ field }
										value={ item[ field.name ] }
										onChange={ val => updateItem( i, field.name, val ) }
									/>
								</div>
							) ) }
						</div>
					) }
				</div>
			) ) }

			<button type="button" className="eltiz-repeater__add" onClick={ addItem }>
				+ { control.add_label || __( 'Add Item', 'eltiz-builder' ) }
			</button>
		</div>
	);
}
