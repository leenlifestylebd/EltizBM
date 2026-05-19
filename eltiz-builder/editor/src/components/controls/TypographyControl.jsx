import { __ } from '@wordpress/i18n';
import SliderControl from './SliderControl';

const FONT_WEIGHTS = [
	{ value: '100', label: '100 Thin' },
	{ value: '300', label: '300 Light' },
	{ value: '400', label: '400 Normal' },
	{ value: '500', label: '500 Medium' },
	{ value: '600', label: '600 Semi Bold' },
	{ value: '700', label: '700 Bold' },
	{ value: '800', label: '800 Extra Bold' },
	{ value: '900', label: '900 Black' },
];

const TRANSFORMS = [ 'none', 'uppercase', 'lowercase', 'capitalize' ];
const DECORATIONS = [ 'none', 'underline', 'line-through', 'overline' ];
const STYLES      = [ 'normal', 'italic', 'oblique' ];

export default function TypographyControl( { control, value, onChange } ) {
	const typo = typeof value === 'object' ? value : {};

	const update = ( key, val ) => onChange( { ...typo, [ key ]: val } );

	return (
		<div className="eltiz-typography-control">
			<div className="eltiz-typography-control__row">
				<label>{ __( 'Family', 'eltiz-builder' ) }</label>
				<input
					type="text"
					className="eltiz-control__input"
					value={ typo.family || '' }
					placeholder="Inherit"
					onChange={ e => update( 'family', e.target.value ) }
				/>
			</div>

			<div className="eltiz-typography-control__row">
				<label>{ __( 'Weight', 'eltiz-builder' ) }</label>
				<select
					className="eltiz-control__select"
					value={ typo.weight || '' }
					onChange={ e => update( 'weight', e.target.value ) }
				>
					<option value="">{ __( 'Default', 'eltiz-builder' ) }</option>
					{ FONT_WEIGHTS.map( w => (
						<option key={ w.value } value={ w.value }>{ w.label }</option>
					) ) }
				</select>
			</div>

			<div className="eltiz-typography-control__row">
				<label>{ __( 'Size', 'eltiz-builder' ) }</label>
				<SliderControl
					control={ { min: 1, max: 200, step: 1, default_unit: 'px' } }
					value={ typo.size }
					onChange={ val => update( 'size', val ) }
				/>
			</div>

			<div className="eltiz-typography-control__row">
				<label>{ __( 'Line Height', 'eltiz-builder' ) }</label>
				<SliderControl
					control={ { min: 0.5, max: 5, step: 0.1, default_unit: 'em', allowed_units: [ 'em', 'px' ] } }
					value={ typo.line_height }
					onChange={ val => update( 'line_height', val ) }
				/>
			</div>

			<div className="eltiz-typography-control__row">
				<label>{ __( 'Letter Spacing', 'eltiz-builder' ) }</label>
				<SliderControl
					control={ { min: -5, max: 20, step: 0.1, default_unit: 'px', allowed_units: [ 'px', 'em' ] } }
					value={ typo.letter_spacing }
					onChange={ val => update( 'letter_spacing', val ) }
				/>
			</div>

			<div className="eltiz-typography-control__row">
				<label>{ __( 'Style', 'eltiz-builder' ) }</label>
				<div className="eltiz-choose-group">
					{ STYLES.map( s => (
						<button
							key={ s }
							type="button"
							className={ `eltiz-choose-btn${ typo.style === s ? ' is-active' : '' }` }
							onClick={ () => update( 'style', s ) }
						>{ s }</button>
					) ) }
				</div>
			</div>

			<div className="eltiz-typography-control__row">
				<label>{ __( 'Transform', 'eltiz-builder' ) }</label>
				<div className="eltiz-choose-group">
					{ TRANSFORMS.map( t => (
						<button
							key={ t }
							type="button"
							className={ `eltiz-choose-btn${ typo.transform === t ? ' is-active' : '' }` }
							onClick={ () => update( 'transform', t ) }
						>{ t }</button>
					) ) }
				</div>
			</div>

			<div className="eltiz-typography-control__row">
				<label>{ __( 'Decoration', 'eltiz-builder' ) }</label>
				<div className="eltiz-choose-group">
					{ DECORATIONS.map( d => (
						<button
							key={ d }
							type="button"
							className={ `eltiz-choose-btn${ typo.decoration === d ? ' is-active' : '' }` }
							onClick={ () => update( 'decoration', d ) }
						>{ d }</button>
					) ) }
				</div>
			</div>
		</div>
	);
}
