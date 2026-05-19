import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function ContextMenu( { x, y, elementId, onClose, onDuplicate, onDelete, onEdit } ) {
	const menuRef = useRef( null );

	useEffect( () => {
		const handleClickOutside = e => {
			if ( menuRef.current && ! menuRef.current.contains( e.target ) ) {
				onClose();
			}
		};
		const handleKeyDown = e => {
			if ( e.key === 'Escape' ) onClose();
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		document.addEventListener( 'keydown', handleKeyDown );
		return () => {
			document.removeEventListener( 'mousedown', handleClickOutside );
			document.removeEventListener( 'keydown', handleKeyDown );
		};
	}, [ onClose ] );

	const actions = [
		{
			label: __( 'Edit', 'eltiz-builder' ),
			icon: '✏️',
			onClick: () => { onEdit( elementId ); onClose(); },
		},
		{
			label: __( 'Duplicate', 'eltiz-builder' ),
			icon: '📋',
			onClick: () => { onDuplicate( elementId ); onClose(); },
		},
		{ type: 'separator' },
		{
			label: __( 'Delete', 'eltiz-builder' ),
			icon: '🗑',
			onClick: () => { onDelete( elementId ); onClose(); },
			danger: true,
		},
	];

	return (
		<div
			ref={ menuRef }
			className="eltiz-context-menu"
			style={ { top: y, left: x } }
			role="menu"
		>
			{ actions.map( ( action, i ) =>
				action.type === 'separator'
					? <div key={ i } className="eltiz-context-menu__separator" />
					: (
						<button
							key={ i }
							className={ `eltiz-context-menu__item${ action.danger ? ' is-danger' : '' }` }
							onClick={ action.onClick }
							role="menuitem"
						>
							<span className="eltiz-context-menu__icon">{ action.icon }</span>
							{ action.label }
						</button>
					)
			) }
		</div>
	);
}
