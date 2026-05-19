export default function ChooseControl( { control, value, onChange } ) {
	const options = control.options || {};

	return (
		<div className="eltiz-choose-group">
			{ Object.entries( options ).map( ( [ key, opt ] ) => (
				<button
					key={ key }
					type="button"
					className={ `eltiz-choose-btn${ value === key ? ' is-active' : '' }` }
					onClick={ () => onChange( key ) }
					title={ opt.title || key }
				>
					{ opt.icon ? (
						<span dangerouslySetInnerHTML={ { __html: opt.icon } } />
					) : (
						<span>{ opt.title || key }</span>
					) }
				</button>
			) ) }
		</div>
	);
}
