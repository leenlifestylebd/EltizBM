export default function TextControl( { control, value, onChange } ) {
	return (
		<input
			type="text"
			className="eltiz-control__input"
			value={ value }
			placeholder={ control.placeholder || '' }
			onChange={ e => onChange( e.target.value ) }
		/>
	);
}
