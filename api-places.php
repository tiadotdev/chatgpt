<?php

/**
 * Adds new REST API endpoints
 */
function pb_register_place_rest_routes() {
	register_rest_route('pb/v1', '/points', array(
		'methods' => WP_REST_Server::READABLE,
		'callback' => 'pb_api_points',
		'permission_callback' => function() {
			return true;
		}
	));
}
add_action('rest_api_init', 'pb_register_place_rest_routes');

function pb_api_points(WP_REST_Request $request) {

	$args = array(
		'post_type' => 'place',
		'posts_per_page' => intval($request['per_page'])
			? intval($request['per_page']) : 24,
		'orderby' => 'title',
		'order'   => 'ASC',
	);

	if (!empty($request['include'])) {
		$args['post__in'] = $request['include'];
	}

	if (!empty($request['keyword'])) {
		$args['s'] = $request['keyword'];
	}

	if (
		$request['place_type'] ||
		$request['amenities'] ||
		$request['business_badge']
	) {
		$args['tax_query'] = array();
	}

	if ($request['place_type']) {
		$args['tax_query'][] = array(
			array(
				'taxonomy' => 'place_type',
				'field'    => 'term_id',
				'terms'    => $request['place_type'],
			),
		);
	}

	if ($request['amenities']) {
		$args['tax_query'][] = array(
			array(
				'taxonomy' => 'amenity',
				'field'    => 'term_id',
				'terms'    => $request['amenities'],
				'operator' => 'AND',
			),
		);
	}

	if ($request['business_badge']) {
		error_log('business_badge is passed with value: ' . $request['business_badge']);
		$args['tax_query'][] = array(
			array(
				'taxonomy' => 'business_badge',
				'field'    => 'term_id',
				'terms'    => $request['business_badge'],
				'operator' => 'AND',
			),
		);
	} else {
		error_log('business_badge is not passed or its value is empty.');
	}
	

	if (array_key_exists('tax_query', $args) && count($args['tax_query']) > 1) {
		$args['tax_query']['relation'] = 'AND';
	}

	$posts_query = get_posts($args);

	// Handle query results
	$marker_ids = array();
	foreach ($posts_query as $post) {
		$location = get_post_meta($post->ID, '_pb_lat_lng', true);

		$badge_image_urls = array(); // Initialize the array
		$badges = get_the_terms($post->ID, 'business_badge');
		if (!empty($badges)) {
			foreach ($badges as $badge) {
				$badge_icon = get_term_meta($badge->term_id, '_pb_badge_icon', true);
				$badge_icon_url = wp_get_attachment_image_src($badge_icon, 'thumbnail');
				$badge_image_urls[] = $badge_icon_url[0];
			}
		}

		if (
			$location &&
			array_key_exists('lat', $location) &&
			array_key_exists('lng', $location) &&
			$location['lat'] &&
			$location['lng']
		) {
			if (
				isset($request['lat']) &&
				isset($request['lng']) &&
				isset($request['radius']) &&
				is_numeric($request['lat']) &&
				is_numeric($request['lng']) &&
				is_numeric($request['radius'])
			) {
				$within_distance = pb_is_location_within_radius(
					$location,
					array(
						'lat' => $request['lat'],
						'lng' => $request['lng'],
					),
					$request['radius']
				);

				if ($within_distance) {
					$marker_ids  [] = (object) array(
						'id' => $post->ID,
						'position' => $location,
						'color' => pb_get_location_marker_color($post->ID),
						'badge_image_urls' => $badge_image_urls,
					);
				}
			}
			else {
				$marker_ids  [] = (object) array(
					'id' => $post->ID,
					'position' => $location,
					'color' => pb_get_location_marker_color($post->ID),
					'badge_image_urls' => $badge_image_urls,
				);
			}

		}

	}

	// Construct response
	$response = rest_ensure_response($marker_ids);
	$response->header('X-WP-Total', (int) count($marker_ids));

	return $response;
}
