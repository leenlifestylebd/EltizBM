export default function CodeControl( { control, value, onChange } ) {
	return (
		<textarea
			className="eltiz-control__code"
			value={ value || '' }
			rows={ control.rows || 8 }
			spellCheck={ false }
			autoComplete="off"
			autoCorrect="off"
			onChange={ e => onChange( e.target.value ) }
		/>
	);
}
