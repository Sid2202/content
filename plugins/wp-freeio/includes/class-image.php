<?php
/**
 * Image
 *
 * @package    wp-freeio
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Freeio_Image {
	
	public static function init() {
		add_filter( 'upload_dir', array( __CLASS__, 'upload_dir' ) );
		// Ajax endpoints.
		add_action( 'wpfi_ajax_wp_freeio_ajax_upload_file',  array(__CLASS__,'process_upload_file') );
		
		// compatible handlers.
		add_action( 'wp_ajax_wp_freeio_ajax_upload_file',  array(__CLASS__,'process_upload_file') );
		add_action( 'wp_ajax_nopriv_wp_freeio_ajax_upload_file',  array(__CLASS__,'process_upload_file') );
	}

	public static function process_upload_file() {
		
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in to upload files using this method.', 'wp-freeio' ) );
			return;
		}
		$data = array(
			'files' => array(),
		);

		if ( ! empty( $_FILES ) ) {
			foreach ( $_FILES as $file_key => $file ) {
				$files_to_upload = self::prepare_uploaded_files( $file );
				foreach ( $files_to_upload as $file_to_upload ) {
					$uploaded_file = self::upload_file(
						$file_to_upload,
						array(
							'file_key' => $file_key,
						)
					);

					if ( is_wp_error( $uploaded_file ) ) {
						$data['files'][] = array(
							'error' => $uploaded_file->get_error_message(),
						);
					} else {
						$data['files'][] = $uploaded_file;
					}
				}
			}
		}

		wp_send_json( $data );
	}

	public static function prepare_uploaded_files( $file_data ) {
		$files_to_upload = array();

		if ( is_array( $file_data['name'] ) ) {
			foreach ( $file_data['name'] as $file_data_key => $file_data_value ) {
				if ( $file_data['name'][ $file_data_key ] ) {
					if ( is_array($file_data['name'][ $file_data_key ]) ) {
						$name = array_shift(array_values($file_data['name'][ $file_data_key ]));
						
						$tmp_name = array_shift(array_values($file_data['tmp_name'][ $file_data_key ]));
						$error = array_shift(array_values($file_data['error'][ $file_data_key ]));
						$size = array_shift(array_values($file_data['size'][ $file_data_key ]));

						$type              = wp_check_filetype( $name ); // Map mime type to one WordPress recognises.
						$files_to_upload[] = array(
							'name'     => $name,
							'type'     => $type['type'],
							'tmp_name' => $tmp_name,
							'error'    => $error,
							'size'     => $size,
						);
					} else {
						$type              = wp_check_filetype( $file_data['name'][ $file_data_key ] ); // Map mime type to one WordPress recognises.
						$files_to_upload[] = array(
							'name'     => $file_data['name'][ $file_data_key ],
							'type'     => $type['type'],
							'tmp_name' => $file_data['tmp_name'][ $file_data_key ],
							'error'    => $file_data['error'][ $file_data_key ],
							'size'     => $file_data['size'][ $file_data_key ],
						);
					}
				}
			}
		} else {
			$type              = wp_check_filetype( $file_data['name'] ); // Map mime type to one WordPress recognises.
			$file_data['type'] = $type['type'];
			$files_to_upload[] = $file_data;
		}

		return apply_filters( 'wp_freeio_prepare_uploaded_files', $files_to_upload );
	}

	public static function upload_file( $file, $args = array() ) {
		global $wp_freeio_upload, $wp_freeio_uploading_file;

		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/media.php';

		$args = wp_parse_args(
			$args,
			array(
				'file_key'           => '',
				'file_label'         => '',
				'allowed_mime_types' => '',
			)
		);

		$wp_freeio_upload         = true;
		$wp_freeio_uploading_file = $args['file_key'];
		$uploaded_file              = new stdClass();

		if ( '' === $args['allowed_mime_types'] ) {
			$allowed_mime_types = self::get_allowed_mime_types($wp_freeio_uploading_file);
		} else {
			$allowed_mime_types = $args['allowed_mime_types'];
		}

		/**
		 * Filter file configuration before upload
		 *
		 * This filter can be used to modify the file arguments before being uploaded, or return a WP_Error
		 * object to prevent the file from being uploaded, and return the error.
		 *
		 * @since 1.25.2
		 *
		 * @param array $file               Array of $_FILE data to upload.
		 * @param array $args               Optional file arguments.
		 * @param array $allowed_mime_types Array of allowed mime types from field config or defaults.
		 */
		$file = apply_filters( 'wp_freeio_upload_file_pre_upload', $file, $args, $allowed_mime_types );
		
		if ( is_wp_error( $file ) ) {
			return $file;
		}

		if ( ! in_array( $file['type'], $allowed_mime_types, true ) ) {
			if ( $args['file_label'] ) {
				// translators: %1$s is the file field label; %2$s is the file type; %3$s is the list of allowed file types.
				return new WP_Error( 'upload', sprintf( __( '"%1$s" (filetype %2$s) needs to be one of the following file types: %3$s', 'wp-freeio' ), $args['file_label'], $file['type'], implode( ', ', array_keys( $allowed_mime_types ) ) ) );
			} else {
				// translators: %s is the list of allowed file types.
				return new WP_Error( 'upload', sprintf( __( 'Uploaded files need to be one of the following file types: %s', 'wp-freeio' ), implode( ', ', array_keys( $allowed_mime_types ) ) ) );
			}
		} else {
			$upload = wp_handle_upload( $file, apply_filters( 'submit_job_wp_handle_upload_overrides', array( 'test_form' => false ) ) );
			if ( ! empty( $upload['error'] ) ) {
				return new WP_Error( 'upload', $upload['error'] );
			} else {
				$uploaded_file->url       = $upload['url'];
				$uploaded_file->file      = $upload['file'];
				$uploaded_file->name      = basename( $upload['file'] );
				$uploaded_file->type      = $upload['type'];
				$uploaded_file->size      = $file['size'];
				$uploaded_file->extension = substr( strrchr( $uploaded_file->name, '.' ), 1 );
			}
		}

		$wp_freeio_upload         = false;
		$wp_freeio_uploading_file = '';

		return $uploaded_file;
	}

	public static function upload_cv_file( $file) {

		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/media.php';
		
		$uploaded_file              = new stdClass();
		$allowed_mime_types = self::get_allowed_mime_types('apply_job_cv_attachment');

		/**
		 * Filter file configuration before upload
		 *
		 * This filter can be used to modify the file arguments before being uploaded, or return a WP_Error
		 * object to prevent the file from being uploaded, and return the error.
		 *
		 * @since 1.25.2
		 *
		 * @param array $file               Array of $_FILE data to upload.
		 * @param array $allowed_mime_types Array of allowed mime types from field config or defaults.
		 */
		$file = apply_filters( 'wp_freeio_upload_cv_file_pre_upload', $file, $allowed_mime_types );
		
		if ( is_wp_error( $file ) ) {
			return $file;
		}

		if ( ! in_array( $file['type'], $allowed_mime_types, true ) ) {
			// translators: %s is the list of allowed file types.
			return new WP_Error( 'upload', sprintf( __( 'Uploaded files need to be one of the following file types: %s', 'wp-freeio' ), implode( ', ', array_keys( $allowed_mime_types ) ) ) );
		} else {
			$upload = wp_handle_upload( $file, apply_filters( 'submit_job_wp_handle_upload_overrides', array( 'test_form' => false ) ) );
			if ( ! empty( $upload['error'] ) ) {
				return new WP_Error( 'upload', $upload['error'] );
			} else {
				$uploaded_file->url       = $upload['url'];
				$uploaded_file->file      = $upload['file'];
				$uploaded_file->name      = basename( $upload['file'] );
				$uploaded_file->type      = $upload['type'];
				$uploaded_file->size      = $file['size'];
				$uploaded_file->extension = substr( strrchr( $uploaded_file->name, '.' ), 1 );
			}
		}

		return $uploaded_file;
	}

	public static function upload_dir( $pathdata ) {
		global $wp_freeio_upload, $wp_freeio_uploading_file;

		if ( ! empty( $wp_freeio_upload ) ) {
			$dir = untrailingslashit( apply_filters( 'wp_freeio_upload_dir', 'wp-freeio-uploads/' . sanitize_key( $wp_freeio_uploading_file ), sanitize_key( $wp_freeio_uploading_file ) ) );

			if ( empty( $pathdata['subdir'] ) ) {
				$pathdata['path']   = $pathdata['path'] . '/' . $dir;
				$pathdata['url']    = $pathdata['url'] . '/' . $dir;
				$pathdata['subdir'] = '/' . $dir;
			} else {
				$new_subdir         = '/' . $dir . $pathdata['subdir'];
				$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
				$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
				$pathdata['subdir'] = $new_subdir;
			}
		}

		return $pathdata;
	}

	public static function create_attachment( $attachment_url, $post_id = 0 ) {
		include_once ABSPATH . 'wp-admin/includes/image.php';
		include_once ABSPATH . 'wp-admin/includes/media.php';

		$upload_dir     = wp_upload_dir();
		$attachment_url = esc_url( $attachment_url, array( 'http', 'https' ) );
		if ( empty( $attachment_url ) ) {
			return 0;
		}

		$attachment_url = str_replace( array( $upload_dir['baseurl'], WP_CONTENT_URL, site_url( '/' ) ), array( $upload_dir['basedir'], WP_CONTENT_DIR, ABSPATH ), $attachment_url );
		if ( empty( $attachment_url ) || ! is_string( $attachment_url ) ) {
			return 0;
		}

		$attachment = array(
			'post_title'   => esc_html__('Attachment Image', 'wp-freeio'),
			'post_content' => '',
			'post_status'  => 'inherit',
			'post_parent'  => $post_id,
			'guid'         => $attachment_url,
		);

		$info = wp_check_filetype( $attachment_url );
		if ( $info ) {
			$attachment['post_mime_type'] = $info['type'];
		}

		$attachment_id = wp_insert_attachment( $attachment, $attachment_url, $post_id );

		if ( ! is_wp_error( $attachment_id ) ) {
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $attachment_url ) );
			return $attachment_id;
		}

		return 0;
	}

	public static function get_allowed_mime_types( $field_key = '' ) {

		if ( $field_key == 'apply_job_cv_attachment') {
			$allowed_mime_types = wp_freeio_get_option('cv_file_types');
		} elseif ( strrpos( $field_key, '_freelancer_', -strlen( $field_key ) ) !== false ) {
			$meta_obj = WP_Freeio_Freelancer_Meta::get_instance(0);
			$field = $meta_obj->get_custom_meta_field($field_key);
			$allowed_mime_types = !empty($field['allow_types']) ? $field['allow_types'] : array();
		} elseif ( strrpos( $field_key, '_employer_', -strlen( $field_key ) ) !== false ) {
			$meta_obj = WP_Freeio_Employer_Meta::get_instance(0);
			$field = $meta_obj->get_custom_meta_field($field_key);
			$allowed_mime_types = !empty($field['allow_types']) ? $field['allow_types'] : array();
		} elseif ( strrpos( $field_key, '_job_', -strlen( $field_key ) ) !== false ) {
			$meta_obj = WP_Freeio_Job_Listing_Meta::get_instance(0);
			$field = $meta_obj->get_custom_meta_field($field_key);
			$allowed_mime_types = !empty($field['allow_types']) ? $field['allow_types'] : array();
		} elseif ( strrpos( $field_key, '_project_', -strlen( $field_key ) ) !== false ) {
			$meta_obj = WP_Freeio_Project_Meta::get_instance(0);
			$field = $meta_obj->get_custom_meta_field($field_key);
			$allowed_mime_types = !empty($field['allow_types']) ? $field['allow_types'] : array();
		} elseif ( strrpos( $field_key, '_service_', -strlen( $field_key ) ) !== false ) {
			$meta_obj = WP_Freeio_Service_Meta::get_instance(0);
			$field = $meta_obj->get_custom_meta_field($field_key);
			$allowed_mime_types = !empty($field['allow_types']) ? $field['allow_types'] : array();
		} else {
			$meta_obj = WP_Freeio_Freelancer_Meta::get_instance(0);
			$field = $meta_obj->get_custom_meta_field($field_key);
			if ( empty($field) ) {
				$meta_obj = WP_Freeio_Job_Listing_Meta::get_instance(0);
				$field = $meta_obj->get_custom_meta_field($field_key);
				if ( empty($field) ) {
					$meta_obj = WP_Freeio_Employer_Meta::get_instance(0);
					$field = $meta_obj->get_custom_meta_field($field_key);
					
				}
			}
			if ( !empty($field) ) {
				$allowed_mime_types = !empty($field['allow_types']) ? $field['allow_types'] : array();
			}
		}

		if ( !empty($allowed_mime_types) ) {
			$return = array();
			$all_mime_types = get_allowed_mime_types();
			foreach ($allowed_mime_types as $key => $mime_type) {
				$return[$mime_type] = !empty($all_mime_types[$mime_type]) ? $all_mime_types[$mime_type] : '';
			}
		} else {
			$return = WP_Freeio_Mixes::get_image_mime_types();
		}

		// if ( '_freelancer_cv_attachment' === $field_key ) {
		// 	$meta_obj = WP_Freeio_Freelancer_Meta::get_instance(0);
		// 	$field = $meta_obj->get_custom_meta_field($field_key);
		// 	$allowed_mime_types = !empty($field['mime_types']) ? $field['mime_types'] : array();

		// } else {
		// 	$allowed_mime_types = WP_Freeio_Mixes::get_image_mime_types();
		// }

		return apply_filters( 'wp_freeio_mime_types', $return, $field_key );
	}
	
	public static function get_attachment_id_from_url( $attachment_url = '' ) {

		$attachment_id = attachment_url_to_postid($attachment_url);

		return $attachment_id;
	}

	public static function upload_attach_with_external_url($image_url, $post_ID = 0) {
		global $wp_freeio_upload;
		$wp_freeio_upload = true;

	    $upload_dir = wp_upload_dir();
	    $image_data = @file_get_contents($image_url);

	    if ($image_data) {
	        $filename = basename($image_url);
	        if (wp_mkdir_p($upload_dir['path'])) {
	            $file = $upload_dir['path'] . '/' . $filename;
	        } else {
	            $file = $upload_dir['basedir'] . '/' . $filename;
	        }
	        @file_put_contents($file, $image_data);

	        $wp_filetype = wp_check_filetype($filename, null);
	        $attachment = array(
	            'post_mime_type' => $wp_filetype['type'],
	            'post_title' => sanitize_file_name($filename),
	            'post_content' => '',
	            'post_status' => 'inherit'
	        );
	        $attach_id = wp_insert_attachment($attachment, $file, $post_ID);
	        require_once(ABSPATH . 'wp-admin/includes/image.php');
	        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
	        wp_update_attachment_metadata($attach_id, $attach_data);

	        set_post_thumbnail($post_ID, $attach_id);
	    }
	    $wp_freeio_upload = false;
	}
}

WP_Freeio_Image::init();