import { __ } from '@wordpress/i18n';

export default function URLControl( { control, value, onChange } ) {
	const url = typeof value === 'object' ? value : { url: value || '', is_external: false, nofollow: false };

	const update = ( key, val ) => onChange( { ...url, [ key ]: val } );

	return (
		<div className="eltiz-url-control">
			<input
				type="url"
				className="eltiz-control__input"
				value={ url.url || '' }
				placeholder={ control.placeholder || 'https://' }
				onChange={ e => update( 'url', e.target.value ) }
			/>
			<div className="eltiz-url-control__options">
				<label className="eltiz-url-control__option">
					<input
						type="checkbox"
						checked={ !! url.is_external }
						onChange={ e => update( 'is_external', e.target.checked ) }
					/>
					{ __( 'Open in new tab', 'eltiz-builder' ) }
				</label>
				<label className="eltiz-url-control__option">
					<input
						type="checkbox"
						checked={ !! url.nofollow }
						onChange={ e => update( 'nofollow', e.target.checked ) }
					/>
					{ __( 'Add nofollow', 'eltiz-builder' ) }
				</label>
			</div>
		</div>
	);
}
