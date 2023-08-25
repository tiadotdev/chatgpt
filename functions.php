<?php

/**
 * Add some additional functionality like widgets
 */
require get_template_directory() . '/inc/admin-pages/admin-page-theme-settings.php';
require get_template_directory() . '/inc/admin-pages/admin-page-reading.php';
require get_template_directory() . '/inc/api/api-businesses.php';
require get_template_directory() . '/inc/api/api-places.php';
require get_template_directory() . '/inc/api/api-experiences.php';
require get_template_directory() . '/inc/api/api-posts.php';
require get_template_directory() . '/inc/blocks/blocks.php';
require get_template_directory() . '/inc/functions/helpers.php';
require get_template_directory() . '/inc/functions/locations.php';
require get_template_directory() . '/inc/functions/template-functions.php';
require get_template_directory() . '/inc/functions/wp-login.php';
require get_template_directory() . '/inc/functions/tripadvisor.php'; // requires vendor/autoload.php in the root directory
require get_template_directory() . '/inc/post-types/post-type-business.php';
require get_template_directory() . '/inc/post-types/post-type-places.php';
require get_template_directory() . '/inc/post-types/post-type-experience.php';
require get_template_directory() . '/inc/post-types/post-type-page.php';
require get_template_directory() . '/inc/post-types/post-type-post.php';
require get_template_directory() . '/inc/post-types/post-type-tribe_events.php';
require get_template_directory() . '/inc/post-types/post-type-menu.php';
require get_template_directory() . '/inc/roles/user-role-owner.php';
require get_template_directory() . '/inc/taxonomies/taxonomy-business_type.php';
// require get_template_directory() . '/inc/taxonomies/taxonomy-place_type.php';
require get_template_directory() . '/inc/taxonomies/taxonomy-amenity.php';
require get_template_directory() . '/inc/taxonomies/taxonomy-business_badge.php';
require get_template_directory() . '/inc/taxonomies/taxonomy-experience_type.php';
require get_template_directory() . '/inc/taxonomies/taxonomy-site_tag.php';



/**
 * (Plugable) Set up theme defaults and registers support for WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
if (!function_exists('pb_setup')) :
function pb_setup() {
	// Set the content width based on the theme's design and stylesheet
	$GLOBALS['content_width'] = 940;

	// Make theme available for translation. Translations found in /languages/.
	load_theme_textdomain('pb', get_template_directory() . '/languages');

	// Add default posts and comments RSS feed links to head
	add_theme_support('automatic-feed-links');

	// Let WordPress manage the document title
	add_theme_support('title-tag');

	// Switch default core markup to output valid HTML5
	add_theme_support('html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'script',
		'style',
	));

	// Enable customizer selective refresh for widgets
	add_theme_support('customize-selective-refresh-widgets');

	// Enable support for featured images on posts and pages
	add_theme_support('post-thumbnails');
	set_post_thumbnail_size(512, 288, array('center', 'center'));

	add_theme_support('post-formats', array(
		'link',
		'video',
	));

	// Enable support for wp menus
	add_theme_support('menus');

	register_nav_menus(array(
		'header-links' => __('Header Links', 'pb'),
		'footer-links' => __('Footer Links', 'pb'),
		'legal-links'  => __('Legal Links', 'pb'),
		'social-links' => __('Social Links', 'pb'),
	));

	// Enable responsive embeds
	add_theme_support('responsive-embeds');

	// Enable Gutenberg align wide or full
	add_theme_support('align-wide');

	// Enqueuing the editor style
	add_theme_support('editor-styles');
	add_editor_style('style-editor.css');

	// Opt-out of the bundled block patterns
	remove_theme_support('core-block-patterns');
	remove_theme_support('block-templates');

	// Yoast breadcrumbs
	add_theme_support('yoast-seo-breadcrumbs');
}
endif;
add_action('after_setup_theme', 'pb_setup');



/**
 * Get the theme's version number
 * If WP_Debug is set to true, returns a current timestamp instead (helps with
 * caching issues during development).
 */
function pb_get_theme_version() {
	$theme = wp_get_theme();

	return (defined('WP_DEBUG') && WP_DEBUG) ? time() : $theme->get('Version');
}



/**
 * Register and enqueue scripts and styles
 */
function pb_scripts() {
	wp_enqueue_style(
		'pb-theme-style',
		get_stylesheet_uri(),
		array(),
		pb_get_theme_version()
	);

	wp_enqueue_script(
		'pb-swiper',
		get_template_directory_uri() . '/js/swiper.min.js',
		array(),
		'6.7.0',
		true
	);

	wp_enqueue_script(
		'pb-theme-script',
		get_template_directory_uri() . '/js/scripts.js',
		array(
			'wp-i18n',
			'pb-swiper',
		),
		pb_get_theme_version(),
		true
	);

	wp_register_script(
		'pb-google-maps-marker-cluster',
		get_template_directory_uri() . '/js/marker-cluster.js',
		array(),
		'1.2.0',
		true
	);

	wp_register_script(
		'pb-helper-functions',
		get_template_directory_uri() . '/js/helpers.js',
		array(),
		pb_get_theme_version(),
		true
	);

	wp_register_script(
		'pb-google-maps',
		get_template_directory_uri() . '/js/google-maps.js',
		array(
			'pb-helper-functions',
			'pb-google-maps-marker-cluster',
		),
		pb_get_theme_version(),
		true
	);

	wp_register_script(
		'pb-places-map',
		get_template_directory_uri() . '/js/places-map.js',
		array(
			'pb-helper-functions',
			'pb-google-maps-marker-cluster',
		),
		pb_get_theme_version(),
		true
	);

	wp_register_script(
		'pb-experiences-script',
		get_template_directory_uri() . '/js/experiences.js',
		array(
			'pb-helper-functions',
		),
		pb_get_theme_version(),
		true
	);

	if (is_post_type_archive('experience')) {
		wp_localize_script('pb-experiences-script', 'pbGoogleMaps', array(
			'apiKey'   => get_option('pb_theme_setting_google_map_api_key'),
			'pinColor' => get_option('pb_theme_setting_google_map_pin_color'),
			'styles'   => get_option('pb_theme_setting_google_map_styles'),
			'templateDirectoryUri' => get_template_directory_uri(),
		));

		wp_enqueue_script('pb-experiences-script');
	}

	if ( get_post_type() == 'business' || get_post_type() == 'tribe_events' ) {
		$markers = array();

		if (is_single()) {
			// Add single location marker details to markers array
			$marker = get_post_type() == 'business'
				? pb_get_location_marker(get_the_ID())
				: pb_get_venue_location_marker(get_the_ID());

			if ($marker) {
				$markers[] = $marker;
			}
		}
		else {
			$markers = pb_get_all_location_markers();
		}

		wp_localize_script('pb-google-maps', 'pbGoogleMaps', array(
			'markers'  => $markers,
			'apiKey'   => get_option('pb_theme_setting_google_map_api_key'),
			'pinColor' => get_option('pb_theme_setting_google_map_pin_color'),
			'styles'   => get_option('pb_theme_setting_google_map_styles'),
			'templateDirectoryUri' => get_template_directory_uri(),
		));

		// Enqueue the google-map.js script
		wp_enqueue_script('pb-google-maps');
	}

	if ( get_post_type() == 'place' ) {
		$markers = array();

		if (is_single()) {
			// Add single location marker details to markers array
			$marker = get_post_type() == 'place'
				? pb_get_location_marker(get_the_ID())
				: pb_get_venue_location_marker(get_the_ID());

			if ($marker) {
				$markers[] = $marker;
			}
		}
		else {
			$markers = pb_get_all_location_markers();
		}

		wp_localize_script('pb-places-map', 'pbGoogleMaps', array(
			'markers'  => $markers,
			'apiKey'   => get_option('pb_theme_setting_google_map_api_key'),
			'pinColor' => get_option('pb_theme_setting_google_map_pin_color'),
			'styles'   => get_option('pb_theme_setting_google_map_styles'),
			'templateDirectoryUri' => get_template_directory_uri(),
		));

		// Enqueue the google-map.js script
		wp_enqueue_script('pb-places-map');
	}

	/**
	 * Highlight all images that don't have an alt attribute with a red outline
	 * if the user is logged in and an administrator.
	 */
	if (is_user_logged_in() && current_user_can('administrator')) {
		wp_add_inline_style('pb-theme-style', '
			[alt=""] {
				outline: 10px dashed red !important;
				outline-offset: -5px !important;
			}

			.c-map [alt=""] {
				outline: none !important;
			};
		');
	}
}
add_action('wp_enqueue_scripts', 'pb_scripts');

/**
 * Register and enqueue admin scripts and styles
 */
function pb_admin_enqueue_scripts() {
	wp_enqueue_style(
		'pb-admin-style',
		get_template_directory_uri() . '/style-admin.css',
		array(),
		pb_get_theme_version()
	);

	$current_screen = get_current_screen();

	if (!isset($current_screen->id)) {
		return;
	}

	if ($current_screen->id == 'edit-amenity') {
		wp_enqueue_script('media-upload');
		wp_enqueue_media();
		wp_enqueue_script(
			'pb-admin-amenity-taxonomy',
			get_template_directory_uri() . '/js/amenity-taxonomy-edit.js',
			array()
		);
	}

	if ( ! did_action( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	}
    wp_enqueue_script('admin-scripts', get_template_directory_uri().'/js/admin.js', array('jquery'), rand(), true);
}
add_action('admin_enqueue_scripts', 'pb_admin_enqueue_scripts');



/**
 * Customize the login page styles
 */
function pb_login_stylesheet() {
	wp_enqueue_style(
		'pb-login-styles',
		get_stylesheet_directory_uri() . '/style-login.css',
		array(),
		pb_get_theme_version()
	);
}
add_action('login_enqueue_scripts', 'pb_login_stylesheet');



/**
 * Tell WordPress which JavaScript files contain translations
 */
function pb_set_script_translations() {
	wp_set_script_translations('pb-theme-script', 'pb');
	wp_set_script_translations('pb-google-maps', 'pb');
	wp_set_script_translations('pb-experiences-script', 'pb');
}
add_action('init', 'pb_set_script_translations');



/**
 * Add `defer` or `async` attribute to enqueued scripts
 */
function pb_add_async_or_defer_attribute($tag, $handle) {
	$scripts_to_async = array();

	$scripts_to_defer = array(
		'pb-theme-script',
		'pb-helpers',
		'pb-google-maps-marker-cluster',
		'pb-google-maps',
		'pb-places-map',
		'pb-experiences-script',
	);

	if (in_array($handle, $scripts_to_async)) {
		return str_replace(' src', ' async="async" src', $tag);
	}
	else if (in_array($handle, $scripts_to_defer)) {
		return str_replace(' src', ' defer="defer" src', $tag);
	}

	return $tag;
}
add_filter('script_loader_tag', 'pb_add_async_or_defer_attribute', 10, 2);



/**
 * Register sidebars and widgets areas.
 */
function pb_widgets_init() {
	register_sidebar(array(
		'name'          => __('Header Top Bar', 'pb'),
		'id'            => 'header-sidebar',
		'description'   => __('Displays above the logo in the site header.', 'pb'),
		'before_widget' => '<div id="%1$s" class="%2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<div class="c-super-header__widget-title">',
		'after_title'   => '</div>',
	));

	register_sidebar(array(
		'name'          => __('Alert Bar', 'pb'),
		'id'            => 'alert-bar',
		'description'   => __('Displays below the Header Top Bar. Use with “Alert” blocks.', 'pb'),
		'before_widget' => '',
		'after_widget'  => '',
		'before_title'  => '<h2 class="is-style-h5">',
		'after_title'   => '</h2>',
	));

	register_sidebar(array(
		'name'          => __('Cookie Notice', 'pb'),
		'id'            => 'cookie-notice',
		'before_widget' => '',
		'after_widget'  => '',
		'before_title'  => '<h2 class="is-style-h4">',
		'after_title'   => '</h2>',
	));

	register_sidebar(array(
		'name'				=> __('Newsletter', 'pb'),
		'id'				=> 'newsletter',
		'before_widget' 	=> '',
		'after_widget'		=> '',
		'before_title'		=> '',
		'after_title'		=> '',
	));
}
add_action('widgets_init', 'pb_widgets_init');



/**
 * (Plugable) Set a custom default excerpt length
 */
if (!function_exists('pb_excerpt_length')) :
function pb_excerpt_length($length) {
	return 30;
}
endif;
add_filter('excerpt_length', 'pb_excerpt_length', 999);



/**
 * (Plugable) Set custom excerpt "read more" text
 */
if (!function_exists('pb_custom_excerpt_more')) :
function pb_custom_excerpt_more($more) {
	return '…';
}
endif;
add_filter('excerpt_more', 'pb_custom_excerpt_more');



/**
 * Remove "Howdy, " welcome text from admin bar
 */
function pb_remove_howdy($wp_admin_bar) {
	$my_account = $wp_admin_bar->get_node('my-account');
	$wp_admin_bar->add_node(array(
		'id' => 'my-account',
		'title' => str_replace('Howdy, ', '', $my_account->title),
	));
}
add_filter('admin_bar_menu', 'pb_remove_howdy', 25);



/**
 * Fixes empty <p> tags showing in the output content.
 */
function pb_the_content_empty_paragraphs_fix($content) {
	return str_replace('<p></p>', '', $content);
}
add_filter('the_content', 'pb_the_content_empty_paragraphs_fix');



/**
 * Add post type to body classes in editor
 */
function pb_admin_body_class($classes) {
	$screen = get_current_screen();

	if (!$screen->is_block_editor()) {
		return $classes;
	}

	$post_id = isset($_GET['post']) ? intval($_GET['post']) : false;
	$post_type = get_post_type($post_id);

	if ($post_type) {
		$classes .= " $post_type";
	}

	if ($post_id == get_option('page_on_front')) {
		$classes .= ' is-front-page';
	}

	return $classes;
}
add_filter('admin_body_class', 'pb_admin_body_class');



/**
 * (Plugable) Clean up post classes
 * This removes all the default post classes and replaces them with our own.
 *
 * @param array $classes            An array of the default classes.
 * @param array $additional_classes Additional classes added to the post_class() function.
 * @param int   $post_id            The post ID.
 * @return array A new array of post classes.
 */
if (!function_exists('pb_post_classes')) :
function pb_post_classes($classes, $additional_classes, $post_id) {
	global $wp_query;

	$post_or_excerpt = is_singular() && $wp_query->queried_object_id == get_the_ID()
		? 'post' : 'excerpt';

	$classes = array(
		'c-' . $post_or_excerpt,
		'c-' . $post_or_excerpt . '--' . get_post_type($post_id),
		'c-' . $post_or_excerpt . '--' . get_the_ID($post_id),
	);

	if (post_type_supports(get_post_type(), 'post-formats')) {
		$post_format = get_post_format();

		if ($post_format && !is_wp_error($post_format)) {
			$classes[] = 'c-' . $post_or_excerpt . '--format-' . sanitize_html_class($post_format);
		}
		else {
			$classes[] = 'c-' . $post_or_excerpt . '--format-standard';
		}
	}

	return array_merge($classes, $additional_classes);
}
endif;
add_filter('post_class', 'pb_post_classes', 10, 3);



/**
 * Filter archive page title
 */
function pb_archive_title($title) {
	if (is_home()) {
		// Use page set in Settings > Reading for main blog page title
		$title = get_the_title(get_option('page_for_posts'));
	}
	else if (is_post_type_archive('business')) {
		/**
		 * This beats out the is_search() if statement so business search
		 * results still show the post type title, and not "Search Results for".
		 */
		$post_type_obj = get_post_type_object('business');

		$title = $post_type_obj->labels->archives;
	}
	else if (is_tax()) {
		$queried_object = get_queried_object();

		$title = $queried_object->name;
	}
	else if (is_category()) {
		$title = str_replace('Category: ', '', $title);
	}
	else if (is_tag()) {
		$title = str_replace('Tag: ', '', $title);
	}
	else if (is_search()) {
		$title = __('Your search results', 'pb');
	}
	else if (is_author()) {
		$title = get_the_author_meta('display_name');
	}
	else {
		$title = str_replace('Archives: ', '', $title);
	}

	return $title;
};
add_filter('get_the_archive_title', 'pb_archive_title');



/**
 * Remove the admin bar of non-administrators
 */
function pb_remove_admin_bar() {
	$user = wp_get_current_user();
	$allowed_roles = array( 'editor', 'administrator' );
	if ( !array_intersect($allowed_roles, $user->roles ) && !is_admin() ) {
		show_admin_bar(false);
	}
}
add_action('after_setup_theme', 'pb_remove_admin_bar');



/**
 * Add post ID column to admin table views
 */
function pb_post_id_column_register($columns) {
	$columns['post_id'] = esc_html__('ID', 'pb');

	return $columns;
}
add_filter('manage_posts_columns', 'pb_post_id_column_register');
add_filter('manage_pages_columns', 'pb_post_id_column_register');

function pb_post_id_column_display($column_name, $post_id) {
	if ($column_name === 'post_id') {
		echo esc_html($post_id);
	}
}
add_action('manage_posts_custom_column', 'pb_post_id_column_display', 10, 2);
add_action('manage_pages_custom_column', 'pb_post_id_column_display', 10, 2);



/**
 * Add term ID column to taxonomy admin table views
 */
function pb_term_id_column($columns) {
	$columns['term_id'] = __('ID', 'pb');

	return $columns;
}
add_filter('manage_edit-business_type_columns', 'pb_term_id_column');
add_filter('manage_edit-amenity_columns', 'pb_term_id_column');

function pb_term_id_column_render($content, $column_name, $term_id) {
	if ($column_name == 'term_id') {
		$content = esc_html($term_id);
	}

	return $content;
}
add_filter('manage_business_type_custom_column', 'pb_term_id_column_render', 10, 3);
add_filter('manage_amenity_custom_column', 'pb_term_id_column_render', 10, 3);



/**
 * Add new address field scheme (Canada) for WPForms
 *
 * @link https://wpforms.com/developers/create-additional-schemes-for-the-address-field/
 *
 * @param array $schemes
 * @return array
 */
function pb_wpforms_address_schemes($schemes) {
	$schemes['canada'] = array(
		'label'          => __('Canada', 'pb'),
		'address1_label' => __('Address Line 1', 'pb'),
		'address2_label' => __('Address Line 2', 'pb'),
		'city_label'     => __('City', 'pb'),
		'postal_label'   => __('Postal Code', 'pb'),
		'state_label'    => __('Province', 'pb'),
		'states'         => array(
			'AB' => __('Alberta', 'pb'),
			'BC' => __('British Columbia', 'pb'),
			'MB' => __('Manitoba', 'pb'),
			'NB' => __('New Brunswick', 'pb'),
			'NL' => __('Newfoundland and Labrador', 'pb'),
			'NT' => __('Northwest Territories', 'pb'),
			'NS' => __('Nova Scotia', 'pb'),
			'NU' => __('Nunavut', 'pb'),
			'ON' => __('Ontario', 'pb'),
			'PE' => __('Prince Edward Island', 'pb'),
			'WQ' => __('Quebec', 'pb'),
			'SK' => __('Saskatchewan', 'pb'),
			'YT' => __('Yukon', 'pb'),
		),
	);

	return $schemes;
}
add_filter('wpforms_address_schemes', 'pb_wpforms_address_schemes');

/**
 * Save WPForms fields data into post meta as objects. (For things like address
 * and social media links).
 *
 * This only applies to forms that are set up for post submissions.
 */
function pb_process_wpforms_address_for_post_submissions($post_id, $fields, $form_data, $entry_id) {
	$settings = $form_data['settings'];

	// Check if this is a post submission form
	if (
		!array_key_exists('post_submissions', $settings) ||
		$settings['post_submissions'] != 1
	) {
		return;
	}

	error_log(json_encode($fields));
	error_log(json_encode($form_data));

	$address_meta_field = '_pb_address';

	if (isset($settings['post_submissions_meta'][$address_meta_field])) {
		/**
		 * Get the WPForms field ID for the address meta field. I.e. what is the
		 * WPForms field number that should save its value into the meta field?
		 */
		$address_field_id = $settings['post_submissions_meta'][$address_meta_field];

		/**
		 * If that field exists, update the post meta with the address details
		 * saved as an array instead of a string.
		 */
		if (array_key_exists($address_field_id, $fields)) {
			$field = $fields[$address_field_id];

			// Make sure this is actually a WPForms address field type
			if ($field['type'] === 'address') {
				$address_details = array(
					'address1' => $field['address1'],
					'address2' => $field['address2'],
					'city'     => $field['city'],
					'province' => $field['state'],
					'postal'   => $field['postal'],
					'country'  => !empty($field['country']) ? $field['country'] : 'Canada',
				);

				update_post_meta($post_id, $address_meta_field, $address_details);
			}
		}
	}

	/**
	 * Get the WPForms data for social media URLs and merge them together into
	 * a single object of 'network' => 'url' pairs for saving to the
	 * _pb_social_urls meta.
	 */
	if (!empty($settings['post_submissions_meta'])) {
		$social_links = array();

		foreach($settings['post_submissions_meta'] as $meta_key => $field_id) {
			if (strpos($meta_key, '_pb_social_urls') !== false) {
				$network = str_replace('_pb_social_urls_', '', $meta_key);

				$social_links[$network] = $fields[$field_id]['value'];
			}

			update_post_meta($post_id, '_pb_social_urls', $social_links);
		}
	}
}
add_action('wpforms_post_submissions_process', 'pb_process_wpforms_address_for_post_submissions', 4, 10);

// Shortcode to add Submit event button to Events Calendar Pro view page
add_shortcode('submit-event', 'create_submit_event_button');
function create_submit_event_button() {
	ob_start(); ?>
	
	<a href="<?php echo site_url('/submit-event'); ?>" class="wp-block-button__link tribe-submit-event-btn"><?php _e('Submit an Event' , 'pb'); ?></a>
	
	<?php
	return ob_get_clean();
}

// Add Meta pixel to header of the HAULiday page
add_action('wp_head', 'add_meta_pixel');
function add_meta_pixel() {
	global $post;
	if ($post && $post->ID == 82139) { ?>
		<!-- Meta Pixel Code for Hauliday -->
		<script>
			!function(f,b,e,v,n,t,s)
			{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
			n.callMethod.apply(n,arguments):n.queue.push(arguments)};
			if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
			n.queue=[];t=b.createElement(e);t.async=!0;
			t.src=v;s=b.getElementsByTagName(e)[0];
			s.parentNode.insertBefore(t,s)}(window, document,'script',
			'https://connect.facebook.net/en_US/fbevents.js');
			fbq('init', '486325163437372');
			fbq('track', 'PageView');
		</script>
		<noscript><img height="1" width="1" style="display:none" src=https://www.facebook.com/tr?id=486325163437372&ev=PageView&noscript=1 /></noscript>

		<!-- End Meta Pixel Code -->
	<?php }
}

// Add Ontario Culinary shortcode
add_shortcode('ontario-culinary', 'create_ontario_culinary_shortcode');
function create_ontario_culinary_shortcode() {
	ob_start(); ?>

	<link rel="stylesheet" type="text/css" href="https://discover.ontarioculinary.com/css/checkout/persistent-cart/persistentCart.css">
	<script src="https://discover.ontarioculinary.com/js/checkout/persistent-cart/persistentCart.js"></script><script>bwpcart.offerId = 1808;bwpcart.partnerId = 340;bwpcart.checkoutUrl = "https://discover.ontarioculinary.com";bwpcart.buildCart();</script>
	<div class="bwmodule" data-bwmoduleid="80278"></div>

	<?php
	return ob_get_clean();
}