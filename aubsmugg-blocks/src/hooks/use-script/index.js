/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

export default function useScript(src) {
	// Keep track of script status ('idle', 'loading', 'ready', 'error')
	const [status, setStatus] = useState(src ? 'loading' : 'idle');

	useEffect(() => {
		if (!src) {
			setStatus('idle');
			return;
		}

		// Fetch existing script element by src
		// It may have been added by another instance of this hook
		let script = document.querySelector(`script[src="${src}"]`);

		if (!script) {
			script = document.createElement('script');
			script.src = src;
			script.async = true;
			script.setAttribute('data-status', 'loading');

			document.body.appendChild(script);

			const setAttributeFromEvent = (event) => {
				script.setAttribute(
					'data-status',
					event.type === 'load' ? 'ready' : 'error'
				);
			};

			script.addEventListener('load', setAttributeFromEvent);
			script.addEventListener('error', setAttributeFromEvent);
		}
		else {
			setStatus(script.getAttribute('data-status'));
		}

		const setStateFromEvent = (event) => {
			setStatus(event.type === 'load' ? 'ready' : 'error');
		};

		script.addEventListener('load', setStateFromEvent);
		script.addEventListener('error', setStateFromEvent);

		return () => {
			if (script) {
				script.removeEventListener('load', setStateFromEvent);
				script.removeEventListener('error', setStateFromEvent);
			}
		};
	}, [src]);

	return status;
};
