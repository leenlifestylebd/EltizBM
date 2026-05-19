import { __ } from '@wordpress/i18n';

const UNITS = [ 'px', '%', 'em', 'rem', 'vw', 'vh' ];

export default function SliderControl( { control, value, onChange } ) {
	const val = typeof value === 'object' ? value : { size: value || 0, unit: control.default_unit || 'px' };
	const unit = val.unit || 'px';
	const size = val.size ?? 0;

	const min  = control.min  ?? 0;
	const max  = control.max  ?? 100;
	const step = control.step ?? 1;

	const update = ( key, newVal ) => onChange( { ...val, [ key ]: newVal } );

	return (
		<div className="eltiz-slider-control">
			<div className="eltiz-slider-control__row">
				<input
					type="range"
					className="eltiz-slider-control__range"
					min={ min }
					max={ max }
					step={ step }
					value={ size }
					onChange={ e => update( 'size', Number( e.target.value ) ) }
				/>
				<input
					type="number"
					className="eltiz-slider-control__number"
					min={ min }
					max={ max }
					step={ step }
					value={ size }
					onChange={ e => update( 'size', Number( e.target.value ) ) }
				/>
			</div>
			{ ( control.allowed_units || UNITS ).length > 1 && (
				<div className="eltiz-slider-control__units">
					{ ( control.allowed_units || UNITS ).map( u => (
						<button
							key={ u }
							type="button"
							className={ `eltiz-slider-control__unit${ unit === u ? ' is-active' : '' }` }
							onClick={ () => update( 'unit', u ) }
						>{ u }</button>
					) ) }
				</div>
			) }
		</div>
	);
}
