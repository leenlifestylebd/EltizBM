import { __ } from '@wordpress/i18n';
import TextControl from './TextControl';
import TextareaControl from './TextareaControl';
import SelectControl from './SelectControl';
import ColorControl from './ColorControl';
import SliderControl from './SliderControl';
import SwitcherControl from './SwitcherControl';
import NumberControl from './NumberControl';
import MediaControl from './MediaControl';
import TypographyControl from './TypographyControl';
import DimensionsControl from './DimensionsControl';
import URLControl from './URLControl';
import RepeaterControl from './RepeaterControl';
import ChooseControl from './ChooseControl';
import CodeControl from './CodeControl';

const CONTROL_MAP = {
	text:       TextControl,
	textarea:   TextareaControl,
	select:     SelectControl,
	color:      ColorControl,
	slider:     SliderControl,
	switcher:   SwitcherControl,
	number:     NumberControl,
	media:      MediaControl,
	typography: TypographyControl,
	dimensions: DimensionsControl,
	url:        URLControl,
	repeater:   RepeaterControl,
	choose:     ChooseControl,
	code:       CodeControl,
	hidden:     () => null,
};

export default function ControlRenderer( { control, value, onChange } ) {
	if ( ! control || control.type === 'section_start' || control.type === 'section_end' ) {
		return null;
	}

	const Component = CONTROL_MAP[ control.type ];

	if ( ! Component ) {
		return (
			<div className="eltiz-control eltiz-control--unknown">
				<label className="eltiz-control__label">{ control.label }</label>
				<input
					type="text"
					className="eltiz-control__input"
					value={ value ?? control.default ?? '' }
					onChange={ e => onChange( e.target.value ) }
				/>
			</div>
		);
	}

	const resolvedValue = value !== undefined ? value : ( control.default ?? '' );

	return (
		<div className={ `eltiz-control eltiz-control--${ control.type }` }>
			{ control.label && (
				<label className="eltiz-control__label">
					{ control.label }
					{ control.description && (
						<span className="eltiz-control__description" title={ control.description }>?</span>
					) }
				</label>
			) }
			<Component
				control={ control }
				value={ resolvedValue }
				onChange={ onChange }
			/>
		</div>
	);
}
