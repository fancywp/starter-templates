<?php
class Starter_Templates_Placeholder {

	public $placeholder_id = 0;
	public $placeholder_post = false;
	public $placeholder_url = '';
	public $logo_post = false;

	private static $_instance = null;

	private function  init(){
		if ( get_option( 'starter_import_placeholder_only' ) ) {
			$this->maybe_insert_placeholder();
			$logo_id = get_theme_mod('custom_logo');
			if ( $logo_id ) {
				$logo_post = get_post( $logo_id );
				if ( $logo_post  && get_post_type( $logo_post ) == 'attachment' ) {
					$this->logo_post = $logo_post;
				}
			}
		}
	}

	static function get_instance(){
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
			self::$_instance->init();
		}
		return self::$_instance;
	}

	private function maybe_insert_placeholder(){
		if ( $this->placeholder_id ) {
			return $this->placeholder_id;
		}
		$name = sanitize_title( 'placeholder' );
		$placeholder_post = get_page_by_path( $name, OBJECT , 'attachment' );
		if ( $placeholder_post ) {
			$this->placeholder_post = $placeholder_post;
			$this->placeholder_id = $placeholder_post->ID;
			$this->placeholder_url = wp_get_attachment_url( $placeholder_post->ID );
		} else {
			$this->upload_placeholder_attachment();
		}

	}

	private function get_placeholder_img(){
		return '/assets/placeholder/placeholder.jpg';
	}

	private function upload_placeholder_attachment( $name = '', $parent_id = 0 ){

		// $filename should be the path to a file in the upload directory.
		$filename = STARTER_TEMPLATES_PATH.$this->get_placeholder_img();
		// Get the path to the upload directory.
		$wp_upload_dir = wp_upload_dir();

		$save_to_path =  $wp_upload_dir['path'] . '/' . basename( $filename );
		$save_to_url =  $wp_upload_dir['url'] . '/' . basename( $filename );

		global $wp_filesystem;
		WP_Filesystem();

		$wp_filesystem->copy( $filename, $save_to_path, true );

		// The ID of the post this attachment is for.
		$parent_post_id = 0;

		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $save_to_path ), null );

		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => $save_to_url,
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $save_to_path ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $save_to_path, $parent_post_id );

		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $save_to_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		if ( ! is_wp_error( $attach_id ) ) {
			$this->placeholder_post = get_post( $attach_id );
			$this->placeholder_id = $attach_id;
			$this->placeholder_url = wp_get_attachment_url( $attach_id );
		}

	}

	/**
	 * @param $url
	 * @param bool $post_id
	 *
	 * @return string
	 */
	function wp_get_attachment_url( $url, $post_id = false ){
		if ( $this->placeholder_url && is_string( $url ) ) {
			$name = basename( $url );
			$ext = explode( '.', $url );
			if ( is_array( $ext ) ) {
				$ext = end( $ext );
				$ext = strtolower( $ext );
				if ( $ext && in_array( $ext, array( 'png', 'jpeg', 'jpg' ) ) ) {
					$url = $this->placeholder_url;
				}
			}
		}

		return $url;
	}

	/**
	 * @param $data
	 *
	 * @return array|null|string|string[]
	 */
	function progress_elementor_data( $data ){
		if ( is_array( $data ) ) {
			if( isset( $data['url'] ) &&  isset( $data['id'] )  ) {
				$data['url'] = $this->placeholder_url;
				$data['id'] = $this->placeholder_id;
			}
			foreach ( $data as $index => $_d ) {
				$data[ $index ] = $this->progress_elementor_data( $_d );
			}
		} elseif ( is_string( $data ) ) {
			$data = $this->content_replace_placeholder( $data );
		}

		return $data;
	}

	/**
	 * @param $data
	 *
	 * @return array|null|string|string[]
	 */
	function progress_beaver_data( $data ){
		if ( is_object( $data ) ) {

			if ( property_exists( $data, 'photo' ) &&  property_exists( $data, 'photo_src' )  ) {
				$data->photo_src = $this->placeholder_url;
				$data->photo = $this->placeholder_id;
			}

			foreach ( $data as $index => $_d ) {
				if ( $index == 'bg_image_src' ) {
					$data->{ $index } = $this->placeholder_url;
				}

				if ( $index == 'bg_image' ) {
					$data->{ $index } = $this->placeholder_id;
				}

				$data->{ $index } = $this->progress_beaver_data( $_d );
			}
		} elseif ( is_array( $data ) ) {

			//var_dump( $data );

			if( isset( $data['photo'] ) &&  isset( $data['photo_src'] )  ) {
				$data['photo_src'] = $this->placeholder_url;
				$data['photo'] = $this->placeholder_id;
			}

			foreach ( $data as $index => $_d ) {
				if ( $index == 'bg_image_src' ) {
					$data[$index] = $this->placeholder_url;
				}

				if ( $index == 'bg_image' ) {
					$data[ $index ] = $this->placeholder_id;
				}

				$data[$index] = $this->progress_beaver_data( $_d );
			}

		} elseif ( is_string( $data ) ) {
			$data = $this->replace_placeholder( $data );
		}

		return $data;
	}

	/**
	 * @param $data
	 *
	 * @return array|string
	 */
	function replace_placeholder( $data ){
		if ( is_array( $data ) ) {
			foreach ( $data as $index => $_d ) {
				$data[ $index ] = $this->replace_placeholder( $_d );
			}
		} elseif ( is_string( $data ) ) {
			$data = $this->content_replace_placeholder( $data );
			$data = $this->wp_get_attachment_url( $data );
		}

		return $data;
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	function content_replace_placeholder( $content ){

		if ( $this->placeholder_id ) {

			// filter gallery shortcode
			$pattern = get_shortcode_regex();
			$content =  preg_replace_callback(
				'/'. $pattern .'/s',
				array( $this, 'gallery_reg_cb' ),
				$content
			);

			//Image URL
			$pattern = '/<*img[^>]*src*=*["\']?([^"\']*)/i';

			$content = preg_replace_callback(
				$pattern,
				array( $this, 'image_reg_url_cb' ),
				$content
			);

		}

		return $content;
	}


	function gallery_reg_cb($matches)
	{
		if ( strtolower( $matches[2] ) == 'gallery' ) {
			$n = count( explode( ',', $matches['3'] ) );
			$new_gallery =  array_fill( 0, $n - 1, $this->placeholder_id );
			return '[gallery ids="'.join( ',', $new_gallery ).'"]';
		}

		return $matches[0];
	}

	function image_reg_url_cb( $matches ){
		if ( isset(  $matches[1] ) ) {
			$array = explode( '.', $matches[1] );
			$ext   = end( $array );
			$ext   = strtolower( $ext );
			if ( $ext && in_array( $ext, array( 'png', 'jpeg', 'jpg' ) ) ) {
				return str_replace( $matches[1], $this->placeholder_url, $matches[0] );
			}

		}
		return $matches[0];
	}

	/**
	 *
	 * @param bool $key
	 * @param bool $value
	 * @param bool $post_id
	 *
	 * @return string|array
	 */
	function progress_post_meta( $meta_key = false ,$meta_value = false , $post_id = false ){

		switch ( $meta_key ) {
			case '_thumbnail_id':
				$meta_value = $this->placeholder_id;
				break;
			case '_product_image_gallery':
				if ( is_string( $meta_value ) ) {
					$meta_value = explode( ',', $meta_value );
					$n = count( $meta_value );
					$meta_value = array_fill( 0, $n - 1, $this->placeholder_id );
					$meta_value = join(',', $meta_value );
				}

				break;
			case '_starter_page_header_image':
				if ( is_array( $meta_value ) ) {
					$meta_value['id'] = $this->placeholder_id;
					$meta_value['url'] = $this->placeholder_url;
				}

				break;

			case '_fl_builder_data':
			case '_fl_builder_data_settings':
			case '_fl_builder_draft_settings':
			case '_fl_builder_draft':
				if ( is_object( $meta_value ) || is_array( $meta_value ) ) {
					$meta_value            = $this->progress_beaver_data( $meta_value );
				}
				break;
		}

		return $meta_value;
	}


	function progress_meta( $meta ){

		/**
		 *
		 * _thumbnail_id : 12
		 *
		 * _product_image_gallery :  37,38,39,36,35
		 *
		 * _starter_page_header_image: array( 'id' => '', 'url' )
		 */

		switch ( $meta->meta_key ) {
			case '_thumbnail_id':
				$meta->meta_value = $this->placeholder_id;
				break;
			case '_product_image_gallery':
				$value = maybe_unserialize( $meta -> meta_value );
				if ( is_string( $value ) ) {
					$value = explode( ',', $value );
					$n = count( $value );
					$value = array_fill( 0, $n - 1, $this->placeholder_id );
					$meta->meta_value = join(',', $value);
				}

				break;
			case '_starter_page_header_image':
				$value = maybe_unserialize( $meta -> meta_value );
				if ( is_array( $value ) ) {
					$value['id'] = $this->placeholder_id;
					$value['url'] = $this->placeholder_url;
					$meta->meta_value = serialize( $value );
				}

				break;

			case '_elementor_data':
				// $meta->meta_value = '';
				$value = json_decode( $meta->meta_value , true );
				$value = $this->progress_elementor_data( $value );
				$meta->meta_value = json_encode( $value );
				break;

			case '_fl_builder_data':
			case '_fl_builder_data_settings':
			case '_fl_builder_draft_settings':
			case '_fl_builder_draft':
				$value = maybe_unserialize( $meta->meta_value );
				if ( is_object( $value ) || is_array( $value ) ) {
					$value            = $this->progress_beaver_data( $value );
					$meta->meta_value = serialize( $value );
				}
				break;
		}

		return $meta;
	}

	function progress_config( $options ){
		if ( $this->placeholder_id ) {
			if ( is_array( $options ) ) {
				foreach ( $options as $k => $option ) {

					switch ( $k ) {
						case 'custom_logo':
							// $options[ $k ] = $this->placeholder_id;
							break;
					}

					if ( is_array( $option ) ) {

						// For image widget
						if ( isset( $option['attachment_id'] ) && isset( $option['url'] ) ) {
							$option['attachment_id'] = $this->placeholder_id;
							$option['url'] = $this->placeholder_url;
						} else {
							$options[ $k ] = $this->progress_config( $option );
						}

					}
				}
			} elseif ( is_string( $options ) ) {
				$options = $this->content_replace_placeholder( $options );
				$options = $this->wp_get_attachment_url( $options );
			}
		}
		return $options;
	}


}