<?php

class Starter_Templates_WXR_Import_UI {
	/**
	 * Should we fetch attachments?
	 *
	 * Set in {@see display_import_step}.
	 *
	 * @var bool
	 */
	protected $fetch_attachments = true;

	public $counts = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'upload_mimes', array( $this, 'add_mime_type_xml' ) );

		$this->counts = array(
            'posts' => 0,
            'media' => 0,
            'users' => 0,
            'comments' => 0,
            'terms' => 0
        );

	}

	/**
	 * Add .xml files as supported format in the uploader.
	 *
	 * @param array $mimes Already supported mime types.
	 */
	public function add_mime_type_xml( $mimes ) {
		$mimes = array_merge( $mimes, array( 'xml' => 'application/xml' ) );

		return $mimes;
	}

	/**
	 * Get preliminary data for an import file.
	 *
	 * This is a quick pre-parse to verify the file and grab authors from it.
	 *
	 * @param int $id Media item ID.
	 * @return Starter_Templates_WXR_Import_Info|WP_Error Import info instance on success, error otherwise.
	 */
	public function get_data_for_attachment( $id ) {
		$existing = get_post_meta( $id, '_wxr_import_info' );
		if ( ! empty( $existing ) ) {
			$data = $existing[0];
			$this->authors = $data->users;
			$this->version = $data->version;
			return $data;
		}

		$file = get_attached_file( $id );

		$importer = $this->get_importer();
		$data = $importer->get_preliminary_information( $file );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Cache the information on the upload
		if ( ! update_post_meta( $id, '_wxr_import_info', $data ) ) {
			return new WP_Error(
				'wxr_importer.upload.failed_save_meta',
				__( 'Could not cache information on the import.', 'wordpress-importer', 'starter-templates' ),
				compact( 'id' )
			);
		}

		$this->authors = $data->users;
		$this->version = $data->version;

		return $data;
	}

	function get_posts_by_title( $page_title, $post_type ){
        global $wpdb;
        $sql = $wpdb->prepare( "
			SELECT ID
			FROM $wpdb->posts
			WHERE post_title = %s
			AND post_type = %s
		", $page_title, $post_type );
        return $wpdb->get_col( $sql );
    }


	public function import() {

		$this->id = wp_unslash( (int) $_REQUEST['id'] );
		// Download media files
		$this->fetch_attachments = true;
		$importer = $this->get_importer();

		// Are we allowed to create users?
		if ( ! $this->allow_create_users() ) {
			add_filter( 'wxr_importer.pre_process.user', '__return_null' );
		}

		// Keep track of our progress
		add_action( 'wxr_importer.processed.post', array( $this, 'imported_post' ), 10, 2 );
		add_action( 'wxr_importer.process_failed.post', array( $this, 'imported_post' ), 10, 2 );
		add_action( 'wxr_importer.process_already_imported.post', array( $this, 'already_imported_post' ), 10, 2 );
		add_action( 'wxr_importer.process_skipped.post', array( $this, 'already_imported_post' ), 10, 2 );
		add_action( 'wxr_importer.processed.comment', array( $this, 'imported_comment' ) );
		add_action( 'wxr_importer.process_already_imported.comment', array( $this, 'imported_comment' ) );
		add_action( 'wxr_importer.processed.term', array( $this, 'imported_term' ) );
		add_action( 'wxr_importer.process_failed.term', array( $this, 'imported_term' ) );
		add_action( 'wxr_importer.process_already_imported.term', array( $this, 'imported_term' ) );
		add_action( 'wxr_importer.processed.user', array( $this, 'imported_user' ) );
		add_action( 'wxr_importer.process_failed.user', array( $this, 'imported_user' ) );

		$file = get_attached_file( $this->id );
		$err = $importer->import( $file );
		update_post_meta( $this->id, '_wxr_importer_mapping', $importer->mapping );

		// remove Hello World! post

        $ids = $this->get_posts_by_title( 'Hello World!', 'post' );
        if ( is_array( $ids ) ) {
            foreach( $ids as $id ) {
                wp_update_post( array( 'ID' => $id, 'post_status' => 'pending' ) );
            }
        }

        $ids = $this->get_posts_by_title( 'Sample Page', 'page' );
        if ( is_array( $ids ) ) {
            foreach( $ids as $id ) {
                wp_update_post( array( 'ID' => $id, 'post_status' => 'pending' ) );
            }
        }

		ob_start();
		ob_end_clean();
		ob_end_flush();
		ob_start();
		wp_send_json( $this->counts );
	}

	function re_mapping_thumbnails(){

    }

	/**
	 * Get the importer instance.
	 *
	 * @return Starter_Templates_WXR_Importer
	 */
	protected function get_importer() {
		$importer = new Starter_Templates_WXR_Importer( $this->get_import_options() );
		$logger = new Starter_Templates_Importer_Logger_ServerSentEvents();
		$importer->set_logger( $logger );
		return $importer;
	}

	/**
	 * Get options for the importer.
	 *
	 * @return array Options to pass to Starter_Templates_WXR_Importer::__construct
	 */
	protected function get_import_options() {
		$options = array(
			'fetch_attachments' => $this->fetch_attachments,
			'default_author'    => get_current_user_id(),
		);

		/**
		 * Filter the importer options used in the admin UI.
		 *
		 * @param array $options Options to pass to Starter_Templates_WXR_Importer::__construct
		 */
		return apply_filters( 'wxr_importer.admin.import_options', $options );
	}


	/**
	 * Decide whether or not the importer should attempt to download attachment files.
	 * Default is true, can be filtered via import_allow_fetch_attachments. The choice
	 * made at the import options screen must also be true, false here hides that checkbox.
	 *
	 * @return bool True if downloading attachments is allowed
	 */
	protected function allow_fetch_attachments() {
		return apply_filters( 'import_allow_fetch_attachments', true );
	}

	/**
	 * Decide whether or not the importer is allowed to create users.
	 * Default is true, can be filtered via import_allow_create_users
	 *
	 * @return bool True if creating users is allowed
	 */
	protected function allow_create_users() {
		return apply_filters( 'import_allow_create_users', true );
	}

	/**
	 * Get mapping data from request data.
	 *
	 * Parses form request data into an internally usable mapping format.
	 *
	 * @param array $args Raw (UNSLASHED) POST data to parse.
	 * @return array Map containing `mapping` and `slug_overrides` keys.
	 */
	protected function get_author_mapping( $args ) {
		if ( ! isset( $args['imported_authors'] ) ) {
			return array(
				'mapping'        => array(),
				'slug_overrides' => array(),
			);
		}

		$map        = isset( $args['user_map'] ) ? (array) $args['user_map'] : array();
		$new_users  = isset( $args['user_new'] ) ? $args['user_new'] : array();
		$old_ids    = isset( $args['imported_author_ids'] ) ? (array) $args['imported_author_ids'] : array();

		// Store the actual map.
		$mapping = array();
		$slug_overrides = array();

		foreach ( (array) $args['imported_authors'] as $i => $old_login ) {
			$old_id = isset( $old_ids[ $i ] ) ? (int) $old_ids[ $i ] : false;

			if ( ! empty( $map[ $i ] ) ) {
				$user = get_user_by( 'id', (int) $map[ $i ] );

				if ( isset( $user->ID ) ) {
					$mapping[] = array(
						'old_slug' => $old_login,
						'old_id'   => $old_id,
						'new_id'   => $user->ID,
					);
				}
			} elseif ( ! empty( $new_users[ $i ] ) ) {
				if ( $new_users[ $i ] !== $old_login ) {
					$slug_overrides[ $old_login ] = $new_users[ $i ];
				}
			}
		}

		return compact( 'mapping', 'slug_overrides' );
	}


	/**
	 * Send message when a post has been imported.
	 *
	 * @param int $id Post ID.
	 * @param array $data Post data saved to the DB.
	 */
	public function imported_post( $id, $data ) {
        if ( $data['post_type'] ===  'attachment' ) {
            $this->counts['media'] ++ ;
        } else {
            $this->counts['posts'] ++ ;
        }
	}

	/**
	 * Send message when a post is marked as already imported.
	 *
	 * @param array $data Post data saved to the DB.
	 */
	public function already_imported_post( $data ) {
	    if ( $data['post_type'] ===  'attachment' ) {
            $this->counts['media'] ++ ;
        } else {
            $this->counts['posts'] ++ ;
        }

	}

	/**
	 * Send message when a comment has been imported.
	 */
	public function imported_comment() {
        $this->counts['comments'] ++ ;
	}

	/**
	 * Send message when a term has been imported.
	 */
	public function imported_term() {
        $this->counts['terms'] ++ ;
	}

	/**
	 * Send message when a user has been imported.
	 */
	public function imported_user() {
	    $this->counts['users'] ++ ;
	}
}
