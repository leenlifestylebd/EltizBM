import { useEffect, useRef } from '@wordpress/element';

export function useAutoSave( isDirty, saveFn, interval = 60000 ) {
	const timerRef = useRef( null );

	useEffect( () => {
		if ( ! isDirty ) return;

		timerRef.current = setTimeout( () => {
			saveFn();
		}, interval );

		return () => clearTimeout( timerRef.current );
	}, [ isDirty, saveFn, interval ] );
}
