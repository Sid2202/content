<?php

function freeio_get_projects( $params = array() ) {
	$params = wp_parse_args( $params, array(
		'limit' => -1,
		'post_status' => 'publish',
		'get_projects_by' => 'recent',
		'orderby' => '',
		'order' => '',
		'post__in' => array(),
		'fields' => null, // ids
		'author' => null,
		'category' => array(),
		'types' => array(),
		'location' => array(),
	));
	extract($params);

	$query_args = array(
		'post_type'         => 'project',
		'posts_per_page'    => $limit,
		'post_status'       => $post_status,
		'orderby'       => $orderby,
		'order'       => $order,
	);

	$meta_query = array();
	switch ($get_projects_by) {
		case 'recent':
			$query_args['orderby'] = 'date';
			$query_args['order'] = 'DESC';
			break;
		case 'featured':
			$meta_query[] = array(
				'key' => WP_FREEIO_PROJECT_PREFIX.'featured',
	           	'value' => 'on',
	           	'compare' => '=',
			);
			break;
		case 'urgent':
			$meta_query[] = array(
				'key' => WP_FREEIO_PROJECT_PREFIX.'urgent',
	           	'value' => 'on',
	           	'compare' => '=',
			);
			break;
	}

	if ( !empty($post__in) ) {
    	$query_args['post__in'] = $post__in;
    }

    if ( !empty($fields) ) {
    	$query_args['fields'] = $fields;
    }

    if ( !empty($author) ) {
    	$query_args['author'] = $author;
    }

    $tax_query = array();
    if ( !empty($category) ) {
    	$tax_query[] = array(
            'taxonomy'      => 'project_category',
            'field'         => 'slug',
            'terms'         => $category,
            'operator'      => 'IN'
        );
    }
    if ( !empty($types) ) {
    	$tax_query[] = array(
            'taxonomy'      => 'project_type',
            'field'         => 'slug',
            'terms'         => $types,
            'operator'      => 'IN'
        );
    }
    if ( !empty($location) ) {
    	$tax_query[] = array(
            'taxonomy'      => 'location',
            'field'         => 'slug',
            'terms'         => $location,
            'operator'      => 'IN'
        );
    }

    if ( !empty($tax_query) ) {
    	$query_args['tax_query'] = $tax_query;
    }
    
    if ( !empty($meta_query) ) {
    	$query_args['meta_query'] = $meta_query;
    }

    if ( method_exists('WP_Freeio_Project', 'project_restrict_listing_query_args') ) {
	    $query_args = WP_Freeio_Project::project_restrict_listing_query_args($query_args, null);
	}
	
	return new WP_Query( $query_args );
}

if ( !function_exists('freeio_project_content_class') ) {
	function freeio_project_content_class( $class ) {
		$prefix = 'projects';
		if ( is_singular( 'project' ) ) {
            $prefix = 'project';
        }
		if ( freeio_get_config($prefix.'_fullwidth') ) {
			return 'container-fluid';
		}
		return $class;
	}
}
add_filter( 'freeio_project_content_class', 'freeio_project_content_class', 1 , 1  );

if ( !function_exists('freeio_get_projects_layout_configs') ) {
	function freeio_get_projects_layout_configs() {
		$layout_sidebar = freeio_get_projects_layout_sidebar();

		$sidebar = 'projects-filter-sidebar';
		switch ( $layout_sidebar ) {
		 	case 'left-main':
		 		$configs['left'] = array( 'sidebar' => $sidebar, 'class' => 'col-lg-3 col-sm-12 col-12'  );
		 		$configs['main'] = array( 'class' => 'col-lg-9 col-sm-12 col-12' );
		 		break;
		 	case 'main-right':
		 	default:
		 		$configs['right'] = array( 'sidebar' => $sidebar,  'class' => 'col-lg-3 col-sm-12 col-12' ); 
		 		$configs['main'] = array( 'class' => 'col-lg-9 col-sm-12 col-12' );
		 		break;
	 		case 'main':
	 			$configs['main'] = array( 'class' => 'col-lg-12 col-sm-12 col-12' );
	 			break;
		}
		return $configs; 
	}
}

function freeio_get_projects_layout_sidebar() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$layout_type = get_post_meta( $post->ID, 'apus_page_layout', true );
	}
	if ( empty($layout_type) ) {
		$layout_type = freeio_get_config('project_archive_layout', 'main-right');
	}
	return apply_filters( 'freeio_get_projects_layout_sidebar', $layout_type );
}

function freeio_get_projects_display_mode() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$display_mode = get_post_meta( $post->ID, 'apus_page_projects_display_mode', true );
	}
	if ( empty($display_mode) ) {
		$display_mode = freeio_get_config('project_display_mode', 'list');
	}
	return apply_filters( 'freeio_get_projects_display_mode', $display_mode );
}

function freeio_get_projects_inner_style() {
	global $post;
	$display_mode = freeio_get_projects_display_mode();
	if ( $display_mode == 'list' ) {
		$inner_style = 'list';
	} else {
		$inner_style = 'grid';
	}
	return apply_filters( 'freeio_get_projects_inner_style', $inner_style );
}

function freeio_get_projects_columns() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$columns = get_post_meta( $post->ID, 'apus_page_projects_columns', true );
	}
	if ( empty($columns) ) {
		$columns = freeio_get_config('projects_columns', 3);
	}
	return apply_filters( 'freeio_get_projects_columns', $columns );
}

function freeio_get_projects_pagination() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$pagination = get_post_meta( $post->ID, 'apus_page_projects_pagination', true );
	}
	if ( empty($pagination) ) {
		$pagination = freeio_get_config('projects_pagination', 'default');
	}
	return apply_filters( 'freeio_get_projects_pagination', $pagination );
}

function freeio_get_projects_show_top_content() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$show_filter_top = get_post_meta( $post->ID, 'apus_page_projects_show_top_content', true );
	}
	if ( empty($show_filter_top) ) {
		$show_filter_top = freeio_get_config('projects_show_top_content');
	} else {
		if ( $show_filter_top == 'no' ) {
			$show_filter_top = 0;
		}
	}
	return apply_filters( 'freeio_get_projects_show_top_content', $show_filter_top );
}


function freeio_is_projects_page() {
	if ( is_page() ) {
		$page_name = basename(get_page_template());
		if ( $page_name == 'page-projects.php' ) {
			return true;
		}
	} elseif( is_post_type_archive('project') || is_tax('project_category') || is_tax('location') || is_tax('project_skill') || is_tax('project_duration') || is_tax('project_experience') || is_tax('project_freelancer_type') || is_tax('project_language') || is_tax('project_level') ) {
		return true;
	}
	return false;
}

function freeio_get_project_post_author($post_id) {
	if ( method_exists('WP_Freeio_Project', 'get_author_id') ) {
		return WP_Freeio_Project::get_author_id($post_id);
	}

	return get_post_field( 'post_author', $post_id );
}


add_action( 'wpfi_ajax_freeio_autocomplete_search_projects', 'freeio_autocomplete_search_projects' );
function freeio_autocomplete_search_projects() {
    // Query for suggestions
    $suggestions = array();
    $args = array(
		'post_type' => 'project',
		'post_per_page' => 10,
		'fields' => 'ids'
	);
    $filter_params = isset($_REQUEST['data']) ? $_REQUEST['data'] : null;

	$projects = WP_Freeio_Query::get_posts( $args, $filter_params );

	if ( !empty($projects->posts) ) {
		foreach ($projects->posts as $post_id) {
			$post = get_post($post_id);
			
			$suggestion['title'] = get_the_title($post_id);
			$suggestion['url'] = get_permalink($post_id);
			
			$image = '';
		 	if ( has_post_thumbnail($post_id) ) {
    			$image_id = get_post_thumbnail_id($post_id);
    			if ( $image_id ) {
        			$image = wp_get_attachment_image_url( $image_id, 'thumbnail' );
        		}
			}

			$suggestion['image'] = $image;
	        
	        $suggestion['salary'] = freeio_project_display_price($post, '', false);

        	$suggestions[] = $suggestion;

		}
	}
    echo json_encode( $suggestions );
 
    exit;
}

add_filter('wp-freeio-add-project-favorite-return', 'freeio_project_add_remove_project_favorite_return', 10, 2);
add_filter('wp-freeio-remove-project-favorite-return', 'freeio_project_add_remove_project_favorite_return', 10, 2);
function freeio_project_add_remove_project_favorite_return($return, $project_id) {
	$return['html'] = freeio_project_display_favorite_btn($project_id);
	return $return;
}

if(!function_exists('freeio_project_filter_before')){
    function freeio_project_filter_before(){
        echo '<div class="wrapper-fillter"><div class="apus-listing-filter d-sm-flex align-items-center">';
    }
}
if(!function_exists('freeio_project_filter_after')){
    function freeio_project_filter_after(){
        echo '</div></div>';
    }
}
add_action( 'wp_freeio_before_project_archive', 'freeio_project_filter_before' , 9 );
add_action( 'wp_freeio_before_project_archive', 'freeio_project_filter_after' , 101 );