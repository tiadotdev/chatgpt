<?php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Registers the block using the metadata loaded from the `block.json` file.
add_action( 'init', 'aubsmugg_register_display_posts_block_on_init' );
function aubsmugg_register_display_posts_block_on_init() {
	register_block_type( AUBSMUGG_BLOCKS_PLUGIN_DIR . '/build/blocks/display-posts', array(
		'render_callback' => 'aubsmugg_display_posts_block_render',
	) );
}

// Block callbackx
function aubsmugg_display_posts_block_render($attributes) {

    // Get initial attribute that determines the destiny of the block 
	$manualActive = isset($attributes['manualActive']) ? $attributes['manualActive'] : false;

	// Get default query for when block loads
	$post_types = get_post_types( array(
		'public' 	=> true,
	), 'names' );
	unset($post_types['attachment']);

	// Get sort
	$sort = explode('/', $attributes['sort']);	
	if ( !isset($sort[1]) ) {
		$sort[1] = 'DESC';
	}	

	$postsToShow = isset($attributes['postsPerPage']) ? $attributes['postsPerPage'] : 12;

	// original query args on block selection
	$args = array(
		'post_type' => $post_types,
		'posts_per_page' => $postsToShow,
		'orderby' => 'rand',
		'status' => 'publish',
	);
    
	if (!$manualActive) {

		// get relevant attributes
		if ( isset( $attributes['postTypes'] ) && ! empty( $attributes['postTypes'] ) ) {
			$post_types = $attributes['postTypes'];
			$post_types = str_replace('pages', 'page', $post_types);
			$post_types = str_replace('posts', 'post', $post_types);
		}
		
		$categories = isset($attributes['categories']['selected']) ? $attributes['categories']['selected'] : [];
		$categories_str = implode(',', $categories);
		$categories_relation = isset($attributes['categories']['relation']) ? $attributes['categories']['relation'] : '';
			
		$tags = isset($attributes['tags']['selected']) ? $attributes['tags']['selected'] : [];
		$tags_str = implode(',', $tags);
		$tags_relation = isset($attributes['tags']['relation']) ? $attributes['tags']['relation'] : '';

		// build query args
		$args = array(
			'post_type' => $post_types,
			'posts_per_page' => $postsToShow,
			'status' => 'publish',
			'orderby' => $sort[0],
			'order' => $sort[1],
		);

		if ( !empty($categories) || !empty($tags) ) {
			$args['tax_query'] = array(
				'relation' => 'AND',  // This sets the relation between categories and tags
			);
		}
		
		if ( !empty($categories) ) {
			$category_queries = array(
				'relation' => $categories_relation,  // This sets the relation between different categories
			);
			foreach ($categories as $cat ) {
				$category_queries[] = array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => $cat,
				);
			}
			$args['tax_query'][] = $category_queries;
		}
		
		if ( !empty($tags) ) {
			$tag_queries = array(
				'relation' => $tags_relation,  // This sets the relation between different tags
			);
			foreach ($tags as $tag ) {
				$tag_queries[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $tag,
				);
			}
			$args['tax_query'][] = $tag_queries;
		}

	} else {
		$manualPosts = isset($attributes['manualPosts']) ? $attributes['manualPosts'] : [];
		$postsToShow = count($manualPosts);

		$args = array(
			'post_type' => $post_types,
			'posts_per_page' => $postsToShow,
			'post__in' => $manualPosts,
			'status' => 'publish',
			'orderby' => $sort[0],
		);
	}

	// Execute the query.
	$the_query = new WP_Query( $args );
	$output = '';

	// The Loop.
	if ( $the_query->have_posts() ) {

		$output .= '<div class="aubsmugg-block-display-posts">';
		
			$output .= '<ul class="display-posts-list">';

				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					
					// Start output buffering.
					ob_start();
					
					// Check if the theme has the needed template part.
					$template = locate_template( 'template-parts/content-' . get_post_type() . '.php' );
					
					// If the theme has the template part, use it. Otherwise, use the plugin's template part.
					if ( ! empty( $template ) ) {
						get_template_part( 'template-parts/content', get_post_type() );
					} 
					// build this later
					// else {
					// 	include plugin_dir_path( __FILE__ ) . 'template-parts/content-' . get_post_type() . '.php';
					// }
					
					// Append the buffered output to the $output variable.
					$output .= ob_get_clean();
				}

			$output .= '</ul>';

			// If posts_per_page is greater than 12, show button
			if ( $postsToShow > 12 ) {
				$output .= '<button class="display-posts-show-more-btn">' . __('Show More', 'aubsmugg') . '</button>';
			}

		$output .= '</div>';
		
		// Restore original Post Data.
		wp_reset_postdata();
	}

	return $output;	

}

// Create endpoint for block to get posts, pages, etc.
add_action( 'rest_api_init', 'aubsmugg_register_display_posts_block_endpoint' );
function aubsmugg_register_display_posts_block_endpoint() {
	register_rest_route( 'aubsmugg/v1', '/display-posts', array(
		'methods' => 'GET',
		'callback' => 'aubsmugg_display_posts_block_endpoint_callback',
		'permission_callback' => '__return_true',
	) );
}

// Callback for endpoint
function aubsmugg_display_posts_block_endpoint_callback($request) {

	// Get parameters
	$params = $request->get_params();

	// Get posts
	$posts = get_posts( $params );

	// Get pages
	$pages = get_pages( $params );

	// Get all custom post types
	$custom_post_types = get_post_types( array(
		'public' => true,
		'_builtin' => false,
	) );

	// Loop through custom post types and get posts
	foreach ($custom_post_types as $custom_post_type) {
		$custom_post_type_posts = get_posts( array(
			'post_type' => $custom_post_type,
			'posts_per_page' => -1,
		) );
		$custom_post_types[$custom_post_type] = $custom_post_type_posts;
	}

	// Merge posts, pages, and custom post type posts separating by type
	$results = array_merge(
		array(
			'posts' => $posts,
			'pages' => $pages,
		),
		$custom_post_types
	);

	// Return posts
	return $results;

}

// Enqueue block specific scripts
add_action('wp_enqueue_scripts', 'aubsmugg_display_posts_block_enqueue_scripts');
add_action( 'enqueue_block_editor_assets', 'aubsmugg_display_posts_block_enqueue_scripts' );
function aubsmugg_display_posts_block_enqueue_scripts() {
	// eneque script
	wp_enqueue_script(
		'aubsmugg-display-posts-block-specific-script',
		AUBSMUGG_BLOCKS_PLUGIN_URL . 'src/blocks/display-posts/block.js',
		array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch', 'jquery' ),
		rand(), 
        true
	);
}