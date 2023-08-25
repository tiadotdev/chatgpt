function initMap() {
	'use strict';

	const mapEle = document.getElementById('places-map');

	if (!mapEle) {
		return;
	}

	// Setup the map
	const map = new google.maps.Map(mapEle, {
		center: new google.maps.LatLng(44.299999, -78.316666), // Defaults to Peterborough
		zoom: 12,
		maxZoom: 17,
		minZoom: 8,
		zoomControl: true,
		streetViewControl: false,
		zoomControlOptions: {
			position: google.maps.ControlPosition.LEFT_BOTTOM,
		},
		panControl: true,
		mapTypeControl: false,
		scrollwheel: false,
		scaleControl: false,
		fullscreenControl: true,
		fullscreenControlOptions: {
			position: google.maps.ControlPosition.LEFT_BOTTOM,
		},
		restriction: {
			latLngBounds: {
				north: 45.05,
				south: 43.985502,
				west: -79.259611,
				east: -77.606168,
			},
			strictBounds: false,
		},
		styles: pbGoogleMaps.styles ?
			JSON.parse(pbGoogleMaps.styles) : [],
	});

	let bounds = null;
	let geocoder = null;

	/**
	 * Documentation for MarkerClusterer can be found here:
	 * http://web.archive.org/web/20160122183325/http://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerclustererplus/docs/reference.html
	 */
	let markerCluster = new MarkerClusterer(map, [], {
		ignoreHidden: true,
		gridSize: 60,
		maxZoom: 16,
		minimumClusterSize: 3,
		zoomOnClick: true,
		styles: [0, 1, 2, 3, 4].map(function(item, index) {
			return {
				anchorText: [9, 0],
				width: 34,
				height: 34,
				url: 'data:image/svg+xml;base64,' + window.btoa('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30"><circle cx="15" cy="15" r="13" fill="#182c00" stroke="#ffffff" stroke-width="2"/></svg>'),
				textColor: '#ffffff',
				fontFamily: 'Inter,sans-serif',
				textSize: 12,
			};
		}),
	});

	// Set up local variables
	let settings = {
		radius: 2500,
		perPage: 24,
		markerClustererThreshold: 25,
		defaultPinColor: pbGoogleMaps.pinColor
			? pbGoogleMaps.pinColor : '#000000',
	};
	let allMarkers = [];
	let liveMarkers = [];
	let infoWindow = null;
	let currentLocationMarker = null;
	let searchRadiusMarker = null;



	/**
	 * Create address HTML block
	 */
	const createAddressBlock = function(address) {
		let lines = [];
		let parts = [];

		const order = [
			'address1',
			'address2',
			'city',
			'province',
			'postal',
		];

		order.forEach(function(key) {
			if (address.hasOwnProperty(key) && address[key]) {
				switch (key) {
					case 'address1':
					case 'address2':
						lines.push(address[key]);
						break;
					default:
						parts.push(address[key]);
				}
			}
		});

		if (parts.length) {
			lines.push(parts.join(', '));
		}

		return lines.length ? lines.join('<br/>') : '';
	}



	/**
	 * Create info window for marker
	 */
	const openInfoWindow = function(marker) {
		if (!infoWindow) {
			infoWindow = new google.maps.InfoWindow();
		}

		infoWindow.close();

		let content = '';

		const address = createAddressBlock(marker.details.address);

		if (marker.details.meta.types) {
			content += '<div class="u-text-small u-margin-bottom-xs">' + marker.details.meta.types.join(', ') + '</div>';
		}

		if (marker.details.title) {
			content += '<h2 class="is-style-h4">' + marker.details.title + '</h2>';
		}

		if (address !== '') {
			content += '<address>' + address + '</address>';
		}

		if (marker.details.url) {
			content += '<div class="wp-block-buttons"><div class="wp-block-button is-style-link"><a href="' + marker.details.url + '" class="wp-block-button__link">' + wp.i18n.__('View Profile', 'pb') + '</a></div></div>';
		}

		infoWindow.setContent(content);
		infoWindow.open(map, marker);
	};



	/**
	 * Create Google Map marker object
	 * Note: This does *not* place the markers on the map by default.
	 *
	 * @param location object An object with marker details
	 */
	const getMarkerObj = function(location) {
		const position = new google.maps.LatLng(
			location.position.lat,
			location.position.lng
		);

		const marker = new google.maps.Marker({
			id: location.id,
			details: {
				title: location.title,
				meta: location.meta,
				url: location.url,
				color: location.color,
				thumbnail: location.thumbnail,
				address: location.address,
				websiteUrl: location.websiteUrl,
			},
			position: position,
			icon: {
				path: 'M-19.4-28c0-10.8,8.6-19.7,19.4-20l0,0l0,0c10.8,0,19.7,8.9,19.4,20l0,0l0,0c0,4.8-1.9,9.2-4.8,12.7C10.2-9.9,5.1-4.8,0,0c-5.1-4.8-10.2-9.5-14.6-14.9C-17.8-18.8-19.4-23.2-19.4-28z',
				fillColor: location.color || settings.defaultPinColor,
				fillOpacity: 1.0,
				scale: 2,
				strokeWeight: 2,
				strokeColor: '#ffffff',
				scale: 0.75,
			},
			zIndex: 1,
			clickable: true,
			visible: false,
		});

		google.maps.event.addListener(marker, 'click', function() {
			openInfoWindow(marker);
		});

		return marker;
	};



	/**
	 * Remove a marker from the map
	 */
	const removeMarkerFromMap = function(marker) {
		if (!settings.useClusterer) {
			marker.setMap(null);
		}

		marker.setVisible(false);
	};



	/**
	 * Remove multiple markers from the map
	 */
	const removeMarkersFromMap = function(markers) {
		if (markers.length > 0) {
			markers.forEach(function(marker) {
				removeMarkerFromMap(marker);
			});

			if (settings.useClusterer) {
				markerCluster.repaint();
			}
		}
	};



	/**
	 * Add a single marker to the map
	 */
	const addMarkerToMap = function(marker) {
		if (!settings.useClusterer) {
			marker.setMap(map);
		}

		marker.setVisible(true);
	};



	/**
	 * Add multiple markers to the map
	 *
	 * @param markers array An array of marker objects
	 * @param bounds bool Fit the map to the bounds of the markers
	 */
	const addMarkersToMap = function(markers, bounds) {
		if (markers.length > 0) {
			if (bounds) {
				bounds = new google.maps.LatLngBounds();
			}

			markers.forEach(function(marker) {
				addMarkerToMap(marker);

				if (bounds) {
					bounds.extend(marker.getPosition());
				}
			});

			if (bounds) {
				map.fitBounds(bounds);
			}

			if (settings.useClusterer) {
				markerCluster.repaint();
			}
		}
	}



	/**
	 * Toggle display of markers on the map
	 */
	const toggleMarkersOnMap = function(markers, filter, bounds) {
		if (markers.length === 0 || typeof filter !== 'function') {
			return;
		}

		let liveMarkers = [];

		if (bounds) {
			bounds = new google.maps.LatLngBounds();
		}

		markers.forEach(function(marker) {
			const showMarker = filter(marker);

			if (showMarker) {
				liveMarkers.push(marker);

				if (bounds) {
					bounds.extend(marker.getPosition());
				}

				addMarkerToMap(marker);
			}
			else {
				removeMarkerFromMap(marker);
			}
		});

		if (settings.useClusterer) {
			markerCluster.repaint();
		}

		if (bounds) {
			map.fitBounds(bounds);
		}

		return liveMarkers;
	};



	/**
	 * Get list item HTML
	 */
	const listWrapper = document.getElementById('js-map-list-wrapper');
	const list = document.getElementById('js-map-list');
	const loadMoreBtn = document.getElementById('js-map-list-load-more');

	const getListItemHTML = function(marker) {
		const websiteLink =  marker.details.websiteUrl
				? createElement(
				'div',
				{className: 'wp-block-buttons'},
				[
					createElement(
						'div',
						{className: 'wp-block-button is-style-link'},
						[
							createElement(
								'a',
								{
									href: marker.details.websiteUrl,
									className: 'wp-block-button__link',
									style: 'position: relative; z-index: 12;',
								},
								[
									wp.i18n.__('Visit Website', 'pb'),
									createElement(
										'span',
										{className: 'u-sr-only'},
										wp.i18n.sprintf(wp.i18n.__('for %s', 'pb'),
											marker.details.title
										)
									),
									createElement(
										'svg',
										{
											viewBox: '0 0 24 24',
											width: 24,
											height: 24,
											'aria-hidden': 'true',
										},
										[
											createElement(
												'polygon',
												{
													className: 'chevron',
													points: '12.8 23.02 9.83 20.03 17.9 12.02 9.88 3.95 12.86 0.98 23.84 12.03 12.8 23.02',
												}
											),
											createElement(
												'rect',
												{
													className: 'stem',
													y: '9.98',
													width: '21.36',
													height: '4.04',
												}
											),
										]
									),
								]
							)
						]
					)
				]
			) : null;

		const hasAddress = marker.details.address && marker.details.address.address1 !== '';

		let addressParts = [];

		console.log(marker);

		['city', 'province', 'postal'].forEach(function(prop) {
			if (marker.details.address.hasOwnProperty(prop) && marker.details.address[prop] !== '') {
				addressParts.push(marker.details.address[prop]);
			}
		});

		const listItem = createElement(
			'li',
			{className: 'c-map-list__item'},
			[
				createElement(
					'li',
					{className: 'c-map-list__card'},
					[
						createElement(
							'a',
							{
								href: marker.details.url,
								className: 'c-map-list__link',
								'data-id': marker.id
							}
						),
						createElement(
							'figure',
							{className: 'c-map-list__image'},
							[
								createElement(
									'img',
									{
										src: marker.details.thumbnail.src || pbGoogleMaps.templateDirectoryUri + '/images/ui/thumbnail-card-default.jpg',
										alt: marker.details.thumbnail.alt,
									}
								)
							]
						),
						createElement(
							'div',
							{className: 'c-map-list__item-body'},
							[
								createElement(
									'svg',
									{
										viewBox: '0 0 20 24',
										width: 20,
										height: 24,
										fill: '#5b8688',
										className: 'c-map-list__pin',
									},
									[
										createElement(
											'path',
											{
												d: 'M1,10.21A9.1,9.1,0,0,1,10,1h0a9.09,9.09,0,0,1,9,9.2h0a9.38,9.38,0,0,1-2.19,5.9A70.3,70.3,0,0,1,10,23a70.3,70.3,0,0,1-6.81-6.89A9.38,9.38,0,0,1,1,10.21Z',
											}
										),
									]
								),
								createElement(
									'div',
									{className: 'c-map__item-content'},
									[
										marker.details.meta.types && marker.details.meta.types.length > 0
											? createElement(
												'div',
												{
													className: 'u-text-small',
												},
												decodeEntities(marker.details.meta.types.join(', '))
											)
											: null,
										createElement(
											'h2',
											{className: 'is-style-h5 c-map-list__title c-excerpt__title'},
											decodeEntities(marker.details.title)
										),
										hasAddress
											? createElement(
												'address',
												{className: 'u-text-small'},
												[
													marker.details.address.address1,
													createElement('br'),
													addressParts.length > 0 ? addressParts.join(', ') : null,
												]
											) : null,
										websiteLink,
									]
								),
							]
						),
					]
				),
			]
		);

		listItem.addEventListener('mouseenter', function(event) {
			marker.setAnimation(4);
		});

		return listItem;
	};

	const addMarkerToList = function(marker) {
		if (list) {
			list.appendChild(getListItemHTML(marker));
		}
	};

	const addMarkersToList = function(markers, startIndex, endIndex) {
		if (!startIndex) {
			startIndex = 0;
		}

		if (!endIndex) {
			endIndex = Math.min(settings.perPage, markers.length);
		}

		if (list) {
			for (let i = startIndex; i < endIndex; i += 1) {
				addMarkerToList(markers[i]);
			}
		}
	};

	const removeMarkersFromList = function() {
		if (list) {
			list.innerHTML = '';
		}
	};



	/**
	 * Update place count
	 */
	const countEle = document.getElementById('js-map-list-count');
	const updateMarkerCount = function(count, context) {
		if (!countEle) {
			return;
		}

		let output = '';

		if (count === 0) {
			output += wp.i18n.__('No places', 'pb');

			if (context) {
				output += ' ' + context;
			}

			output += wp.i18n.__(' match your search criteria.', 'pb');
		}
		else {
			output += count;
			output += count === 1
				? wp.i18n.__(' place', 'pb')
				: wp.i18n.__(' places', 'pb');

			if (context) {
				output += ' ' + context;
			}

			output += ':';
		}

		countEle.innerText = output;
	}



	/**
	 * Add an error message above the map.
	 */
	const mapErrorsEle = document.getElementById('js-map-errors');

	const clearMapErrors = function() {
		mapErrorsEle.innerHTML = '';
	};

	const setMapError = function(message) {
		clearMapErrors();

		const xmlns = 'http://www.w3.org/2000/svg';

		let alert = document.createElement('div');
		let text = document.createTextNode(message);

		alert.setAttribute('role', 'alert');
		alert.classList.add(
			'wp-block-pb-alert',
			'is-style-warning',
			'is-style-small',
			'u-no-margin-top',
			'u-margin-bottom-md'
		);

		alert.appendChild(text);
		mapErrorsEle.appendChild(alert);
	};



	/**
	 * Place (or replace) the current location marker
	 */
	const updateCurrentLocationMarker = function(position) {
		if (!position) {
			return;
		}

		/**
		 * If the marker already exists, just reposition it, otherwise create
		 * the marker
		 */
		if (currentLocationMarker) {
			currentLocationMarker.setPosition(position);
		}
		else {
			currentLocationMarker = new google.maps.Marker({
				position: position,
				map: map,
				icon: {
					path: 'M-15-45c0-8.3,6.7-15,15-15s15,6.7,15,15c0,7.6-5.6,13.9-13.1,14.9v20.7c0,1-0.8,1.9-1.9,1.9 c-1,0-1.9-0.8-1.9-1.9v-20.7C-9.4-31.1-15-37.4-15-45L-15-45z M-5.7-12.8c0.2,1-0.5,2-1.5,2.2c-2.7,0.4-4.8,1.1-6.2,1.8 c-0.5,0.2-1,0.6-1.4,1c-0.1,0.1-0.2,0.2-0.2,0.3v0l0,0c0,0,0,0.1,0.1,0.1c0.1,0.2,0.3,0.4,0.5,0.6c0.6,0.5,1.6,1,3,1.5 C-8.5-4.4-4.5-3.8,0-3.8s8.5-0.6,11.3-1.6c1.4-0.5,2.4-1,3-1.5c0.2-0.2,0.4-0.3,0.5-0.6c0,0,0-0.1,0.1-0.1l0,0v0 c-0.1-0.1-0.1-0.2-0.2-0.3c-0.4-0.4-0.9-0.7-1.4-1c-1.4-0.7-3.5-1.4-6.2-1.8c-1-0.1-1.7-1.1-1.6-2.1c0.1-1,1.1-1.7,2.1-1.6 c0,0,0.1,0,0.1,0c2.9,0.5,5.4,1.2,7.3,2.2c1.7,0.9,3.6,2.4,3.6,4.7c0,1.6-1,2.8-2,3.7c-1.1,0.9-2.6,1.5-4.2,2.1C9.2-0.6,4.8,0,0,0 s-9.2-0.6-12.5-1.7c-1.6-0.5-3.1-1.2-4.2-2.1c-1.1-0.8-2-2.1-2-3.7c0-2.2,1.9-3.8,3.6-4.7c1.9-1,4.5-1.8,7.3-2.2 C-6.8-14.6-5.8-13.9-5.7-12.8z',
					fillColor: '#B7A349',
					fillOpacity: 1.0,
					scale: 4,
					strokeWeight: 1,
					strokeColor: '#ffffff',
					scale: 0.75,
				},
				animation: google.maps.Animation.DROP,
				zIndex: 10,
				clickable: false,
			});
		}

		currentLocationMarker.setMap(map);
		map.setCenter(position);
	};



	/**
	 * Place (and replace) the search radius marker
	 */
	const updateSearchRadius = function(position, radius) {
		if (!position) {
			return;
		}

		/**
		 * If the marker already exists, just reposition it, otherwise create
		 * the marker
		 */
		if (searchRadiusMarker) {
			searchRadiusMarker.setCenter(position);
			searchRadiusMarker.setRadius(radius);
		}
		else {
			searchRadiusMarker = new google.maps.Circle({
				center: position,
				map: map,
				fillColor: '#ffffff',
				fillOpacity: 0,
				strokeColor: '#B7A349',
				strokeWeight: 2,
				strokeOpacity: 1,
				radius: radius,
				clickable: false,
			});
		}

		searchRadiusMarker.setMap(map);
		map.setCenter(position);
		map.fitBounds(searchRadiusMarker.getBounds());
	};



	/**
	 * Get a lat lng position object based on an address
	 *
	 * Since the geocode function is async, a callback function is passed and
	 * called when the result is returned. The callback function has a single
	 * param: the position object or false is no position is found for the given
	 * address.
	 *
	 * @param address string The address string
	 * @param callback function Called when the position object is returned
	 */
	let previousAddress = '';
	let cachedAddressPosition = null;
	const getPositionFromAddress = function(address, callback) {
		if (!address || address.trim() === '') {
			return false;
		}

		if (!geocoder) {
			geocoder = new google.maps.Geocoder();
		}

		if (address === previousAddress && cachedAddressPosition) {
			callback(cachedAddressPosition);

			return;
		}

		geocoder.geocode({'address': address.trim()}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				const position = results[0].geometry.location;

				previousAddress = address.trim();
				cachedAddressPosition = position;

				callback(position);
			}
			else {
				setMapError(wp.i18n.__('We were unable to get an accurate location from the address you provided.', 'pb'));
			}
		});
	}



	/**
	 * Get address from position
	 *
	 * Since the geocode function is async, a callback function is passed and
	 * called when the result is returned. The callback function has a single
	 * param: the formatted address.
	 *
	 * @param latlng object Properties for lat and lng
	 * @param callback function Called when the address object is returned
	 */
	const getAddressFromPosition = function(latlng, callback) {
		if (!geocoder) {
			geocoder = new google.maps.Geocoder();
		}

		const position = new google.maps.LatLng(latlng);

		geocoder.geocode({'latLng': position}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				const address = results[0].formatted_address;

				previousAddress = address;
				cachedAddressPosition = position;

				callback(address);
			}
		});
	};



	/**
	 * Update the list display
	 */
	const updateListDisplay = function(markers) {
		loadMoreBtn.setAttribute('data-page', 2);

		if (listWrapper.style.display === 'none') {
			listWrapper.style.removeProperty('display');
		}

		if (markers.length > settings.perPage) {
			if (loadMoreBtn.style.display === 'none') {
				loadMoreBtn.style.removeProperty('display');
			}
		}
		else {
			loadMoreBtn.style.display = 'none';
		}
	};



	/**
	 * Fetch markers from API
	 */
	const fetchMarkers = function(args, callback) {
		const fetchData = async function() {
			const response = await fetch(addQueryArgs('/wp-json/pb/v1/points', args));

			if (response.ok) {
				const data = await response.json();

				console.log('data: ', data);

				const markerIds = data.map(function(marker) {
					return marker.id;
				});

				const markers = toggleMarkersOnMap(allMarkers, function(marker) {
					return markerIds.indexOf(marker.id) > -1;
				}, true);

				liveMarkers = markers;

				removeMarkersFromList();
				addMarkersToList(markers);
				updateMarkerCount(
					markers.length,
					!!args.address
						? wp.i18n.sprintf(wp.i18n.__('within %s km', 'pb'),
							settings.radius / 1000
						) : null
				);

				updateListDisplay(markers);

				if (callback && typeof callback === 'function') {
					callback(markers);
				}
			}
			else {
				setMapError(wp.i18n.__('We are unable to fetch places at the moment. Please try again later.', 'pb'));
			}
		};

		fetchData();
	};



	/**
	 * Place all pins on the map
	 */
	if (pbGoogleMaps.markers.length > 0) {
		const markers = pbGoogleMaps.markers.map(function(location) {
			return getMarkerObj(location);
		});

		settings.useClusterer = markers.length > settings.markerClustererThreshold;

		addMarkersToMap(markers, true);
		allMarkers = markers;

		if (settings.useClusterer) {
			markerCluster.addMarkers(markers);
		}
	}

	/**
	 * Update the places list when a cluster icon is clicked
	 */
	if (settings.useClusterer) {
		google.maps.event.addListener(markerCluster, 'click', function(cluster) {
			const markers = cluster.getMarkers();

			removeMarkersFromList();
			addMarkersToList(markers);
			updateMarkerCount(markers.length);
			updateListDisplay(markers);

			liveMarkers = markers;
		});
	}

	google.maps.event.addListener(map, 'click', function() {
		if (infoWindow) {
			infoWindow.close();
		}
	});

	const amenitiesPlaceholderEl = document.getElementById('js-amenities-placeholder');
	const selectedAmenitiesEl = document.getElementById('js-selected-amenities');
	const amenitiesCheckboxes = document.querySelectorAll('input[name=amenities]');

	let selectedAmenities = [];

	if (amenitiesCheckboxes && amenitiesCheckboxes.length > 0) {
		amenitiesCheckboxes.forEach(function(checkbox) {
			checkbox.addEventListener('change', function(checkbox) {
				const label = checkbox.target.nextElementSibling.innerText.trim();
				const index = selectedAmenities.indexOf(label);

				if (index > -1) {
					selectedAmenities.splice(index, 1);
				}
				else {
					selectedAmenities.push(label);
				}

				selectedAmenities.sort();

				if (selectedAmenities.length > 0) {
					amenitiesPlaceholderEl.style.display = 'none';
					selectedAmenitiesEl.innerText = wp.i18n.sprintf('(%d) %s',
						selectedAmenities.length,
						selectedAmenities.join(', ')
					);
					selectedAmenitiesEl.style.removeProperty('display');
				}
				else {
					amenitiesPlaceholderEl.style.removeProperty('display');
					selectedAmenitiesEl.style.display = 'none';
					selectedAmenitiesEl.innerText = '';
				}
			});
		});
	}

	const badgesPlaceholderEl = document.getElementById('js-badges-placeholder');
	const selectedBadgesEl = document.getElementById('js-selected-badges');
	const badgesCheckboxes = document.querySelectorAll('input[name=business_badge]');

	let selectedBadges = [];

	if (badgesCheckboxes && badgesCheckboxes.length > 0) {
		badgesCheckboxes.forEach(function(checkbox) {
			checkbox.addEventListener('change', function(checkbox) {
				const label = checkbox.target.nextElementSibling.innerText.trim();
				const index = selectedBadges.indexOf(label);

				if (index > -1) {
					selectedBadges.splice(index, 1);
				} else {
					selectedBadges.push(label);
				}

				selectedBadges.sort();

				if (selectedBadges.length > 0) {
					badgesPlaceholderEl.style.display = 'none';
					selectedBadgesEl.innerText = wp.i18n.sprintf('(%d) %s',
						selectedBadges.length,
						selectedBadges.join(', ')
					);
					selectedBadgesEl.style.removeProperty('display');
				} else {
					badgesPlaceholderEl.style.removeProperty('display');
					selectedBadgesEl.style.display = 'none';
					selectedBadgesEl.innerText = '';
				}
			});
		});
	}


	const form = document.getElementById('search-map-form');

	if (form) {
		const fields = form.querySelectorAll('input, select');

		const getFormData = function() {
			let data = {};

			fields.forEach(function(field, index) {
				const key = field.getAttribute('name');
				let value = field.value.trim();

				if (key === 'place_type') {
					value = parseInt(value, 10);
				}

				if (value) {
					if (field.type === 'checkbox') {
						/**
						 * Checkboxes are treated as arrays, since multiple
						 * checkboxes with the same "name" attribute can be
						 * selected
						 */
						if (field.checked) {
							if (!data.hasOwnProperty(key)) {
								data[key] = [];
							}

							data[key].push(parseInt(value, 10));
						}
					}
					else {
						data[key] = value;
					}
				}
			});

			return data;
		};

		let existingArgs = getFormData();

		if (Object.keys(existingArgs).length > 0) {
			existingArgs.per_page = -1;

			fetchMarkers(existingArgs);
		}

		form.addEventListener('submit', function(event) {
			event.preventDefault();

			clearMapErrors();

			let args = getFormData();

			args.per_page = -1;

			if (!!args.address) {
				getPositionFromAddress(args.address, function(position) {
					args.lat = position.lat();
					args.lng = position.lng();
					args.radius = settings.radius;

					fetchMarkers(args, function() {
						updateCurrentLocationMarker(position);
						updateSearchRadius(position, settings.radius);
					});
				});
			}
			else {
				fetchMarkers(args);
			}
		});

		const useCurrentLocationBtn = document.getElementById('js-use-current-location');
		if (useCurrentLocationBtn) {
			if (!navigator.geolocation) {
				useCurrentLocationBtn.parentElement.style.display = 'none';
			}
			else {
				useCurrentLocationBtn.addEventListener('click', function(event) {
					event.preventDefault();

					clearMapErrors();

					navigator.geolocation.getCurrentPosition(function(position) {
						const latlng = {
							lat: position.coords.latitude,
							lng: position.coords.longitude,
						};

						let args = getFormData();

						args.per_page = -1;
						args.lat = position.coords.latitude;
						args.lng = position.coords.longitude;
						args.radius = settings.radius;

						getAddressFromPosition(latlng, function(address) {
							args.address = address;

							document.getElementById('address').value = address;

							fetchMarkers(args, function() {
								updateCurrentLocationMarker(latlng);
								updateSearchRadius(latlng, settings.radius);
							});
						});
					},
					function(error) {
						switch (error.code) {
							case error.PERMISSION_DENIED:
								setMapError(wp.i18n.__('You declined to grant permission to allow us to use your geolocation.', 'pb'));
								break;
							case error.POSITION_UNAVAILABLE:
								setMapError(wp.i18n.__('Your geolocation is currently unavailable.', 'pb'));
								break;
							default:
								setMapError(wp.i18n.__('An error occured and we are unable to use your geolocation.', 'pb'));
						}

						useCurrentLocationBtn.parentElement.style.display = 'none';
					});
				});
			}
		}
	}

	if (loadMoreBtn) {
		loadMoreBtn.addEventListener('click', function(event) {
			event.preventDefault();

			clearMapErrors();

			const page = Number(this.getAttribute('data-page'));

			if (!isNaN(page)) {
				const startIndex = (page * settings.perPage) - settings.perPage;
				const endIndex = liveMarkers.length > startIndex + settings.perPage
					? startIndex + settings.perPage
					: liveMarkers.length;

				addMarkersToList(liveMarkers, startIndex, endIndex);

				if (endIndex === liveMarkers.length) {
					this.style.display = 'none';
				}
				else {
					this.setAttribute('data-page', page + 1);
				}
			}
		});
	}
}

(function() {
	'use strict';

	if (pbGoogleMaps && pbGoogleMaps.markers.length > 0) {
		let script = document.createElement('script');

		script.type = 'text/javascript';
		script.src = 'https://maps.googleapis.com/maps/api/js?v=3' +
			'&key=' + pbGoogleMaps.apiKey +
			'&libraries=geometry&callback=initMap';

		document.body.appendChild(script);
	}
}());
