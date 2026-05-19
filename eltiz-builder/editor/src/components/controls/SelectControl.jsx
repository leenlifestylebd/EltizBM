export default function SelectControl( { control, value, onChange } ) {
	const options = control.options || {};
	const entries = Array.isArray( options )
		? options
		: Object.entries( options ).map( ( [ val, label ] ) => ( { value: val, label } ) );

	return (
		<select
			className="eltiz-control__select"
			value={ value }
			onChange={ e => onChange( e.target.value ) }
		>
			{ entries.map( opt => (
				<option key={ opt.value } value={ opt.value }>{ opt.label }</option>
			) ) }
		</select>
	);
}
