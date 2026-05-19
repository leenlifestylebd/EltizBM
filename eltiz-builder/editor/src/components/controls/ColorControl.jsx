import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const PRESETS = [
	'#6366f1','#8b5cf6','#f59e0b','#1f2937','#ffffff','#6b7280',
	'#ef4444','#f97316','#22c55e','#3b82f6','#ec4899','#000000',
];

export default function ColorControl( { control, value, onChange } ) {
	const [ open, setOpen ] = useState( false );
	const ref = useRef( null );

	useEffect( () => {
		const handler = e => {
			if ( ref.current && ! ref.current.contains( e.target ) ) setOpen( false );
		};
		if ( open ) document.addEventListener( 'mousedown', handler );
		return () => document.removeEventListener( 'mousedown', handler );
	}, [ open ] );

	const displayColor = value || 'transparent';

	return (
		<div className="eltiz-color-control" ref={ ref }>
			<button
				type="button"
				className="eltiz-color-control__swatch"
				style={ { backgroundColor: displayColor } }
				onClick={ () => setOpen( o => ! o ) }
				title={ displayColor }
			/>
			<input
				type="text"
				className="eltiz-color-control__hex"
				value={ value || '' }
				onChange={ e => onChange( e.target.value ) }
				placeholder="#000000"
			/>

			{ open && (
				<div className="eltiz-color-control__picker">
					<input
						type="color"
						className="eltiz-color-control__native"
						value={ value || '#000000' }
						onChange={ e => onChange( e.target.value ) }
					/>
					<div className="eltiz-color-control__presets">
						{ PRESETS.map( preset => (
							<button
								key={ preset }
								type="button"
								className={ `eltiz-color-control__preset${ value === preset ? ' is-active' : '' }` }
								style={ { backgroundColor: preset } }
								onClick={ () => { onChange( preset ); setOpen( false ); } }
								title={ preset }
							/>
						) ) }
					</div>
					<button
						type="button"
						className="eltiz-color-control__clear"
						onClick={ () => { onChange( '' ); setOpen( false ); } }
					>
						{ __( 'Clear', 'eltiz-builder' ) }
					</button>
				</div>
			) }
		</div>
	);
}
