<?php

function freeio_get_services( $params = array() ) {
	$params = wp_parse_args( $params, array(
		'limit' => -1,
		'post_status' => 'publish',
		'get_services_by' => 'recent',
		'orderby' => '',
		'order' => '',
		'post__in' => array(),
		'fields' => null, // ids
		'author' => null,
		'category' => array(),
		'location' => array(),
	));
	extract($params);

	$query_args = array(
		'post_type'         => 'service',
		'posts_per_page'    => $limit,
		'post_status'       => $post_status,
		'orderby'       => $orderby,
		'order'       => $order,
	);

	$meta_query = array();
	switch ($get_services_by) {
		case 'recent':
			$query_args['orderby'] = 'date';
			$query_args['order'] = 'DESC';
			break;
		case 'featured':
			$meta_query[] = array(
				'key' => WP_FREEIO_SERVICE_PREFIX.'featured',
	           	'value' => 'on',
	           	'compare' => '=',
			);
			break;
		case 'urgent':
			$meta_query[] = array(
				'key' => WP_FREEIO_SERVICE_PREFIX.'urgent',
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
            'taxonomy'      => 'service_category',
            'field'         => 'slug',
            'terms'         => $category,
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

    if ( method_exists('WP_Freeio_Service', 'service_restrict_listing_query_args') ) {
	    $query_args = WP_Freeio_Service::service_restrict_listing_query_args($query_args, null);
	}
	
	return new WP_Query( $query_args );
}

if ( !function_exists('freeio_service_content_class') ) {
	function freeio_service_content_class( $class ) {
		$prefix = 'services';
		if ( is_singular( 'service' ) ) {
            $prefix = 'service';
        }
		if ( freeio_get_config($prefix.'_fullwidth') ) {
			return 'container-fluid';
		}
		return $class;
	}
}
add_filter( 'freeio_service_content_class', 'freeio_service_content_class', 1 , 1  );

if ( !function_exists('freeio_get_services_layout_configs') ) {
	function freeio_get_services_layout_configs() {
		$layout_sidebar = freeio_get_services_layout_sidebar();

		$sidebar = 'services-filter-sidebar';
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
	 			$configs['main'] = array( 'class' => 'col-12' );
	 			break;
		}
		return $configs; 
	}
}

function freeio_get_services_layout_sidebar() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$layout_type = get_post_meta( $post->ID, 'apus_page_layout', true );
	}
	if ( empty($layout_type) ) {
		$layout_type = freeio_get_config('services_layout_sidebar', 'main-right');
	}
	return apply_filters( 'freeio_get_services_layout_sidebar', $layout_type );
}

function freeio_get_services_layout_type() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$layout_type = get_post_meta( $post->ID, 'apus_page_services_layout_type', true );
	}
	if ( empty($layout_type) ) {
		$layout_type = freeio_get_config('services_layout_type', 'default');
	}
	return apply_filters( 'freeio_get_services_layout_type', $layout_type );
}

function freeio_get_services_display_mode() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$display_mode = get_post_meta( $post->ID, 'apus_page_services_display_mode', true );
	}
	if ( empty($display_mode) ) {
		$display_mode = freeio_get_config('services_display_mode', 'list');
	}
	return apply_filters( 'freeio_get_services_display_mode', $display_mode );
}

function freeio_get_services_inner_style() {
	global $post;
	$display_mode = freeio_get_services_display_mode();
	if ( $display_mode == 'list' ) {
		if ( is_page() && is_object($post) ) {
			$inner_style = get_post_meta( $post->ID, 'apus_page_inner_list_style', true );
		}
		if ( empty($inner_style) ) {
			$inner_style = freeio_get_config('services_inner_list_style', 'list');
		}
	} else {
		if ( is_page() && is_object($post) ) {
			$inner_style = get_post_meta( $post->ID, 'apus_page_inner_grid_style', true );
		}
		if ( empty($inner_style) ) {
			$inner_style = freeio_get_config('services_inner_grid_style', 'grid');
		}
	}
	return apply_filters( 'freeio_get_services_inner_style', $inner_style );
}

function freeio_get_services_columns() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$columns = get_post_meta( $post->ID, 'apus_page_services_columns', true );
	}
	if ( empty($columns) ) {
		$columns = freeio_get_config('services_columns', 3);
	}
	return apply_filters( 'freeio_get_services_columns', $columns );
}

function freeio_get_services_pagination() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$pagination = get_post_meta( $post->ID, 'apus_page_services_pagination', true );
	}
	if ( empty($pagination) ) {
		$pagination = freeio_get_config('services_pagination', 'default');
	}
	return apply_filters( 'freeio_get_services_pagination', $pagination );
}


function freeio_get_services_show_top_content() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$show_filter_top = get_post_meta( $post->ID, 'apus_page_services_show_top_content', true );
	}
	if ( empty($show_filter_top) ) {
		$show_filter_top = freeio_get_config('services_show_top_content');
	} else {
		if ( $show_filter_top == 'no' ) {
			$show_filter_top = 0;
		}
	}
	return apply_filters( 'freeio_get_services_show_top_content', $show_filter_top );
}

function freeio_get_services_show_offcanvas_filter() {
	global $post;
	if ( is_page() && is_object($post) ) {
		$show_offcanvas_filter = get_post_meta( $post->ID, 'apus_page_services_show_offcanvas_filter', true );
	}
	if ( empty($show_offcanvas_filter) ) {
		$show_offcanvas_filter = freeio_get_config('services_show_offcanvas_filter');
	} else {
		if ( $show_offcanvas_filter == 'yes' ) {
			$show_offcanvas_filter = true;
		} else {
			$show_offcanvas_filter = false;
		}
	}
	return apply_filters( 'freeio_get_services_show_offcanvas_filter', $show_offcanvas_filter );
}

function freeio_is_services_page() {
	if ( is_page() ) {
		$page_name = basename(get_page_template());
		if ( $page_name == 'page-services.php' ) {
			return true;
		}
	} elseif( is_post_type_archive('service') || is_tax('service_category') || is_tax('location') || is_tax('service_tag') ) {
		return true;
	}
	return false;
}

function freeio_get_service_post_author($post_id) {
	if ( method_exists('WP_Freeio_Service', 'get_author_id') ) {
		return WP_Freeio_Service::get_author_id($post_id);
	}

	return get_post_field( 'post_author', $post_id );
}

add_filter('wp-freeio-add-service-favorite-return', 'freeio_service_add_remove_service_favorite_return', 10, 2);
add_filter('wp-freeio-remove-service-favorite-return', 'freeio_service_add_remove_service_favorite_return', 10, 2);
function freeio_service_add_remove_service_favorite_return($return, $service_id) {
	$return['html'] = freeio_service_display_favorite_btn($service_id);
	return $return;
}


if(!function_exists('freeio_service_filter_before')){
    function freeio_service_filter_before(){
        echo '<div class="wrapper-fillter"><div class="apus-listing-filter d-sm-flex align-items-center">';
    }
}
if(!function_exists('freeio_service_filter_after')){
    function freeio_service_filter_after(){
        echo '</div></div>';
    }
}
add_action( 'wp_freeio_before_service_archive', 'freeio_service_filter_before' , 9 );
add_action( 'wp_freeio_before_service_archive', 'freeio_service_filter_after' , 101 );



add_action( 'wpfi_ajax_freeio_autocomplete_search_services', 'freeio_autocomplete_search_services' );
function freeio_autocomplete_search_services() {
    // Query for suggestions
    $suggestions = array();
    $args = array(
		'post_type' => 'service',
		'post_per_page' => 10,
		'fields' => 'ids'
	);
    $filter_params = isset($_REQUEST['data']) ? $_REQUEST['data'] : null;

	$services = WP_Freeio_Query::get_posts( $args, $filter_params );

	if ( !empty($services->posts) ) {
		foreach ($services->posts as $post_id) {
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
	        
	        $suggestion['salary'] = freeio_service_display_price($post, '', false);

        	$suggestions[] = $suggestion;

		}
	}
    echo json_encode( $suggestions );
 
    exit;
}