export default function NumberControl( { control, value, onChange } ) {
	return (
		<input
			type="number"
			className="eltiz-control__input eltiz-control__input--number"
			value={ value ?? '' }
			min={ control.min ?? undefined }
			max={ control.max ?? undefined }
			step={ control.step ?? 1 }
			onChange={ e => onChange( Number( e.target.value ) ) }
		/>
	);
}
