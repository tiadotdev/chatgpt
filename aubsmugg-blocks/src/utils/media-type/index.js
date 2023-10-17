export default function getMediaType(mime) {
	if (!mime) {
		return 'image';
	}

	return mime.includes('video') ? 'video' : 'image';
};
