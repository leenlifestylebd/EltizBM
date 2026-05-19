export default function TextareaControl( { control, value, onChange } ) {
	return (
		<textarea
			className="eltiz-control__textarea"
			value={ value }
			placeholder={ control.placeholder || '' }
			rows={ control.rows || 4 }
			onChange={ e => onChange( e.target.value ) }
		/>
	);
}
