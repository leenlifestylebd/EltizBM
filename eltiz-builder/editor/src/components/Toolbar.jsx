import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function Toolbar( {
	isSaving,
	isDirty,
	onSave,
	onPublish,
	onUndo,
	onRedo,
	canUndo,
	canRedo,
	deviceView,
	onDeviceChange,
	postId,
} ) {
	const [ showDeviceMenu, setShowDeviceMenu ] = useState( false );

	const devices = [
		{ key: 'desktop', icon: '🖥', label: __( 'Desktop', 'eltiz-builder' ) },
		{ key: 'tablet',  icon: '📱', label: __( 'Tablet', 'eltiz-builder' ) },
		{ key: 'mobile',  icon: '📲', label: __( 'Mobile', 'eltiz-builder' ) },
	];

	const currentDevice = devices.find( d => d.key === deviceView ) || devices[ 0 ];

	const previewUrl = postId
		? `${ window.eltizEditorConfig?.homeUrl || '' }?p=${ postId }&eltiz_preview=1`
		: null;

	return (
		<header className="eltiz-toolbar">
			<div className="eltiz-toolbar__left">
				<a
					href={ window.eltizEditorConfig?.adminUrl || '#' }
					className="eltiz-toolbar__logo"
					title={ __( 'Back to Dashboard', 'eltiz-builder' ) }
				>
					<svg width="28" height="28" viewBox="0 0 28 28" fill="none">
						<rect width="28" height="28" rx="6" fill="#6366f1" />
						<path d="M7 14h14M14 7l7 7-7 7" stroke="#fff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
					</svg>
				</a>

				<div className="eltiz-toolbar__history">
					<button
						className="eltiz-toolbar__btn"
						onClick={ onUndo }
						disabled={ ! canUndo }
						title={ __( 'Undo (Ctrl+Z)', 'eltiz-builder' ) }
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M3.5 7.5L1 5l2.5-2.5M1 5h9a4 4 0 010 8H6" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" />
						</svg>
					</button>
					<button
						className="eltiz-toolbar__btn"
						onClick={ onRedo }
						disabled={ ! canRedo }
						title={ __( 'Redo (Ctrl+Y)', 'eltiz-builder' ) }
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M12.5 7.5L15 5l-2.5-2.5M15 5H6a4 4 0 000 8h4" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" />
						</svg>
					</button>
				</div>
			</div>

			<div className="eltiz-toolbar__center">
				<div className="eltiz-toolbar__device-switcher">
					{ devices.map( device => (
						<button
							key={ device.key }
							className={ `eltiz-toolbar__device-btn${ deviceView === device.key ? ' is-active' : '' }` }
							onClick={ () => onDeviceChange( device.key ) }
							title={ device.label }
						>
							{ device.icon }
						</button>
					) ) }
				</div>
			</div>

			<div className="eltiz-toolbar__right">
				{ previewUrl && (
					<a
						href={ previewUrl }
						target="_blank"
						rel="noreferrer"
						className="eltiz-toolbar__btn"
						title={ __( 'Preview', 'eltiz-builder' ) }
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
							<path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z" />
							<circle cx="8" cy="8" r="2" />
						</svg>
					</a>
				) }

				<button
					className="eltiz-toolbar__btn eltiz-toolbar__btn--save"
					onClick={ onSave }
					disabled={ isSaving || ! isDirty }
					title={ __( 'Save Draft (Ctrl+S)', 'eltiz-builder' ) }
				>
					{ isSaving
						? __( 'Saving…', 'eltiz-builder' )
						: __( 'Save', 'eltiz-builder' ) }
				</button>

				<button
					className="eltiz-toolbar__btn eltiz-toolbar__btn--publish"
					onClick={ onPublish }
					disabled={ isSaving }
				>
					{ __( 'Publish', 'eltiz-builder' ) }
				</button>
			</div>
		</header>
	);
}
