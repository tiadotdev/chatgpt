/**
 * WordPress dependencies
 */
import { useCallback, useState } from '@wordpress/element';

export default function useToggle(initialState = false) {
	const [state, setState] = useState(initialState);

	const toggle = useCallback(() => setState(state => !state), []);

	return [state, toggle]
}
