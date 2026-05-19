import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const SIDES = [ 'top', 'right', 'bottom', 'left' ];
const UNITS = [ 'px', '%', 'em', 'rem', 'vw' ];

export default function DimensionsControl( { control, value, onChange } ) {
	const [ linked, setLinked ] = useState( true );
	const dims = typeof value === 'object' ? value : { top: '', right: '', bottom: '', left: '', unit: 'px' };
	const unit = dims.unit || 'px';

	const update = ( side, val ) => {
		if ( linked ) {
			onChange( { top: val, right: val, bottom: val, left: val, unit } );
		} else {
			onChange( { ...dims, [ side ]: val } );
		}
	};

	const updateUnit = u => onChange( { ...dims, unit: u } );

	return (
		<div className="eltiz-dimensions-control">
			<div className="eltiz-dimensions-control__inputs">
				{ SIDES.map( side => (
					<div key={ side } className="eltiz-dimensions-control__side">
						<input
							type="number"
							className="eltiz-dimensions-control__input"
							value={ dims[ side ] ?? '' }
							onChange={ e => update( side, e.target.value ) }
						/>
						<label>{ side.charAt( 0 ).toUpperCase() }</label>
					</div>
				) ) }

				<button
					type="button"
					className={ `eltiz-dimensions-control__link${ linked ? ' is-linked' : '' }` }
					onClick={ () => setLinked( l => ! l ) }
					title={ linked ? __( 'Unlink sides', 'eltiz-builder' ) : __( 'Link sides', 'eltiz-builder' ) }
				>
					{ linked ? '🔗' : '🔓' }
				</button>
			</div>

			<div className="eltiz-dimensions-control__units">
				{ UNITS.map( u => (
					<button
						key={ u }
						type="button"
						className={ `eltiz-slider-control__unit${ unit === u ? ' is-active' : '' }` }
						onClick={ () => updateUnit( u ) }
					>{ u }</button>
				) ) }
			</div>
		</div>
	);
}
