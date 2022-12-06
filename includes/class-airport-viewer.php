<?php 

/**
 * Settings class.
 */
class Amazing_Airport_Viewer_Init {

    static $instance = false;
	 
    private function __construct() {
        add_action( 'init', array( $this, 'submmissions_post_type' ) , 0 );

        add_action( 'wp_ajax_send_formcd', array( $this, 'post_csv' ) , 0 );
        add_action( 'wp_ajax_nopriv_send_formcd', array( $this, 'post_csv' ) , 0 );

        add_action( 'wp_ajax_get_submission', array( $this, 'get_submission' ) , 0 );
        add_action( 'wp_ajax_nopriv_get_submission', array( $this, 'get_submission' ) , 0 );

        add_action( 'wp_enqueue_scripts', array( $this, 'wp_scripts' ) , 0 );

        add_shortcode( 'airport_viewer', array( $this, 'airport_viewer_shortcode' ) );
	}

    /**
	 *
	 *
	 * @return void
	 */

    public static function get_instance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

    /**
	 *
	 *
	 * @return void
	 */

    public function wp_scripts() {

          wp_enqueue_script( 'mapfile', plugin_dir_url( __FILE__ ) . '../assets/js/map.js' ,'');

          wp_enqueue_script( 'postmaps', plugin_dir_url( __FILE__ ) . '../assets/js/postmaps.js' ,'');

          wp_localize_script( 'postmaps', 'wp_pageviews_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wp-pageviews-nonce' ),
          ) );
          
      }

    /**
	 *
	 *
	 * @return void
	 */

    public function submmissions_post_type() {
    
        // Set UI labels for Custom Post Type

        $labels = array(
            'name'                => _x( 'Submissions', 'Post Type General Name', '' ),
            'singular_name'       => _x( 'Submission', 'Post Type Singular Name', '' ),
            'menu_name'           => __( 'Submissions', '' ),
            'parent_item_colon'   => __( 'Parent Submission', '' ),
            'all_items'           => __( 'All Submissions', '' ),
            'view_item'           => __( 'View Submission', '' ),
            'add_new_item'        => __( 'Add New Submission', '' ),
            'add_new'             => __( 'Add New', '' ),
            'edit_item'           => __( 'Edit Submission', '' ),
            'update_item'         => __( 'Update Submission', '' ),
            'search_items'        => __( 'Search Submission', '' ),
            'not_found'           => __( 'Not Found', '' ),
            'not_found_in_trash'  => __( 'Not found in Trash', '' ),
        );
        
        // Set other options for Custom Post Type
        
        $args = array(
            'label'               => __( 'submissions', '' ),
            'description'         => __( 'Submission news and reviews', '' ),
            'labels'              => $labels,
            // Features this CPT supports in Post Editor
            'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields', ),
            /* A hierarchical CPT is like Pages and can have
            * Parent and child items. A non-hierarchical CPT
            * is like Posts.
            */
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest' => true,
        );
        
        // Registering your Custom Post Type
        register_post_type( 'submissions', $args );
 
    }

    public function airport_viewer_shortcode($atts) {
        $map = $this->map_html();
        return $map;
    }

    private function map_html() {
        ob_start();
        ?>

        <!-- file size
        map markers
        Change the location of where files are stored
        Ensure that only logged-in users can upload files
        reset
        get request vs post
        language 
    -->

        <section class="relative">
            <div id="map" style="width: 100%; height:100vh;"></div>
            <h2 class="absolute top-0 left-0 text-white z-10 p-2"><?php _e('The Amazing Airport Viewer'); ?></h2>
            <div class="absolute top-0 left-0 right-0 bottom-0 flex items-center justify-center text-center text-white bg-[green]/50 transition-opacity duration-500" id="map_overlay">
                <div class=" p-2">
                    <h3 class="text-3xl font-bold mb-2">
                        <?php _e('Share your favorite airports!'); ?>
                    </h3>
                    <p class="mb-2 max-w-xl mx-auto"><?php _e('Upload a CSV document with your favorite airports. We"ll put them on a map, and provide a shareable url.'); ?></p>
                    <form method="POST" >
                        <input type="file" id="file_upload" name="filename" accept=".csv" class="hidden">
                        <label for="file_upload" class="bg-[#000]/25 inline-flex items-center p-4 rounded-lg flex-col md:flex-row">
                            <span class="bg-white hover:bg-slate-800 hover:text-white cursor-pointer rounded-lg text-black p-2 inline-flex mb-4 md:mb-0 md:mr-4 "><?php _e('Select file'); ?></span>
                            <?php _e('Drag and drop a CSV file, or select on from your computer.'); ?>
                        </label>
                    </form>
                </div>
            </div>

            <div class="absolute left-0 right-0 bottom-0 flex items-center justify-center" id="shareable_link">
            </div>

        </section>

        <script src="https://cdn.tailwindcss.com"></script>
        <script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBSYN4U7NwpFWZfXmHCMF7jta6SHdMewVY&v=3.exp&callback=initialize"></script>
        <script type="text/javascript">
        let templateUrl = '<?= plugin_dir_url(__DIR__); ?>';
        </script>

        <?php
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public function get_submission(){
        check_ajax_referer( 'wp-pageviews-nonce', 'nonce' );

        if($_POST['submission_id']){
            $field = get_field('csv_file', $_POST['submission_id']);
            
            if(empty($field)){
                throw new Exception("No csv available");
            }
        }

        die(json_encode( $field ));
    }

    /**
     * In this function we will handle the form inputs and send our email.
     *
     * @return void
     */
    
    public function post_csv(){
        check_ajax_referer( 'wp-pageviews-nonce', 'nonce' );
        
        if ($_POST){
            $mediaId =  $this->uploadCSVToMediaFiles( $_FILES );

            $page_slug = $_FILES['name']; // Slug of the Post

            $new_page = array(
                'post_type'     => 'submissions', 				// Post Type Slug eg: 'page', 'post'
                'post_title'    =>  'csv'.time(),	// Title of the Content
                'post_content'  => 'Test Page Content',	// Content
                'post_status'   => 'publish',			// Post Status
                'post_author'   => 1,					// Post Author ID
                'post_name'     => $page_slug			// Slug of the Post
            );
            
            if (!get_page_by_path( $page_slug, OBJECT, 'page')) { // Check If Page Not Exits
                $new_page_id = wp_insert_post($new_page);
            }

            update_field( 'csv_file', $mediaId, $new_page_id );

        }

        $content = $new_page_id;

        die(json_encode( $content));
    }

    public function uploadCSVToMediaFiles( $file ) {

       // WordPress environmet
       $path = preg_replace( '/wp-content.*$/', '', __DIR__ );
    //    require_once( $path . 'wp-load.php' );
       require_once ABSPATH . 'wp-load.php';

        require_once( ABSPATH . 'wp-admin/includes/file.php' );

        if( empty( $_FILES[ 'csvfile' ] ) ) {
            wp_die( 'No files selected.' );
        }

        $upload = wp_handle_upload( 
            $_FILES[ 'csvfile' ], 
            array( 'test_form' => false ) 
        );

        if( ! empty( $upload[ 'error' ] ) ) {
            wp_die( $upload[ 'error' ] );
        }

        // it is time to add our uploaded image into WordPress media library
        $attachment_id = wp_insert_attachment(
            array(
                'guid'           => $upload[ 'url' ],
                'post_mime_type' => $upload[ 'type' ],
                'post_title'     => basename( $upload[ 'file' ] ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ),
            $upload[ 'file' ]
        );

        if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            wp_die( 'Upload error.' );
        }

        return($attachment_id);

    }

}