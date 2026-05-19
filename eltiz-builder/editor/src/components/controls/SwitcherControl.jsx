export default function SwitcherControl( { control, value, onChange } ) {
	const isOn = value === 'yes' || value === true;

	return (
		<label className="eltiz-switcher">
			<input
				type="checkbox"
				className="eltiz-switcher__input"
				checked={ isOn }
				onChange={ e => onChange( e.target.checked ? 'yes' : '' ) }
			/>
			<span className="eltiz-switcher__track" />
		</label>
	);
}
