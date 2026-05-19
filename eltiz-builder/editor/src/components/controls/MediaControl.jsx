import { __ } from '@wordpress/i18n';

export default function MediaControl( { control, value, onChange } ) {
	const media = typeof value === 'object' ? value : { url: value || '', id: null, alt: '' };

	const openMediaLibrary = () => {
		if ( ! window.wp?.media ) {
			const url = prompt( __( 'Enter image URL:', 'eltiz-builder' ), media.url || '' );
			if ( url !== null ) onChange( { ...media, url } );
			return;
		}

		const frame = window.wp.media( {
			title:    __( 'Select Media', 'eltiz-builder' ),
			button:   { text: __( 'Use this media', 'eltiz-builder' ) },
			multiple: false,
			library:  { type: control.media_type || 'image' },
		} );

		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			onChange( {
				id:  attachment.id,
				url: attachment.url,
				alt: attachment.alt || '',
			} );
		} );

		frame.open();
	};

	return (
		<div className="eltiz-media-control">
			{ media.url ? (
				<div className="eltiz-media-control__preview">
					<img src={ media.url } alt={ media.alt || '' } className="eltiz-media-control__image" />
					<div className="eltiz-media-control__actions">
						<button type="button" className="eltiz-media-control__btn" onClick={ openMediaLibrary }>
							{ __( 'Change', 'eltiz-builder' ) }
						</button>
						<button
							type="button"
							className="eltiz-media-control__btn eltiz-media-control__btn--remove"
							onClick={ () => onChange( { id: null, url: '', alt: '' } ) }
						>
							{ __( 'Remove', 'eltiz-builder' ) }
						</button>
					</div>
				</div>
			) : (
				<button type="button" className="eltiz-media-control__upload" onClick={ openMediaLibrary }>
					<span className="eltiz-media-control__upload-icon">🖼</span>
					<span>{ __( 'Choose Image', 'eltiz-builder' ) }</span>
				</button>
			) }
		</div>
	);
}
