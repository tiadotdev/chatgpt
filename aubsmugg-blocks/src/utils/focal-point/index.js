export default function getFocalPoint(item) {
	if (
		typeof item.focalPointX === 'undefined' &&
		typeof item.focalPointY === 'undefined'
	) {
		return null;
	}

	const focalPoint = {
		x: Math.floor(item.focalPointX * 100),
		y: Math.floor(item.focalPointY * 100),
	};

	return `${ focalPoint.x }% ${ focalPoint.y }%`;
};
