<?php
/**
 * The core plugin class.
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
/**
 * Get the ACF fields from the options page and set the global variables accordingly
 */
add_action( 'acf/init', 'set_global_variables' );
function set_global_variables() {
    global $slotApiUrl, $postTypeSlug, $postType, $showProvidersOn;

    if ( function_exists( 'get_field' ) ) {
        $slotApiUrl = get_field( 'slot_library_url', 'option' ) ?: '';
        $postTypeSlug = strtolower( get_field( 'post_type_slug', 'option' ) ?: 'slots' );
        $postType = strtolower( get_field( 'post_type', 'option' ) ?: 'slots' );
        $showProvidersOn = get_field( 'site_specific_custom_options_post_types', 'option' ) ?: '';
    }
}

if(!class_exists('Rt_Slots')) {
    class Rt_Slots {
        
        /**
        * The single instance of the class.
        *
        * @var Rt_Slots
        * @since 1.0
        */
        protected static $_instance = null;

        /**
        * Main Rt_Slots Instance.
        *
        * Ensures only one instance of Rt_Slots is loaded or can be loaded.
        *
        * @since 1.0
        * @static
        * @return Rt_Slots - Main instance.
        */
        public static function get_instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
                self::$_instance->includes();
                self::$_instance->hooks();
            }
            return self::$_instance;
        }

        /**
        * Include required files.
        *
        * @since 1.0
        * @access private
        */
        private function includes() {
            require_once plugin_dir_path( __FILE__ ) . 'class-rt-slots-api.php';
            require_once plugin_dir_path( __FILE__ ) . 'class-rt-slots-ajax.php';
            require_once plugin_dir_path( __FILE__ ) . 'class-rt-providers-ajax.php';
            // Site specific custom options
			include_once plugin_dir_path( __FILE__ ) . 'slotjava/custom-options.php';

		}

        /**
         * Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
         *
         * @since 1.0
         * @access private
         */
        private function hooks() {
            register_deactivation_hook( __FILE__, array( 'Rt_Slots', 'deactivate' ) );
        }

        /**
         * Initialize the plugin.
         *
         * @since 1.0
         * @access private
         */
        private function __construct() {
            add_action( 'init', array( $this, 'init' ) );
            // Hook into WordPress to register custom post types and taxonomies
            add_action( 'init', [ $this, 'register_post_type' ] );
            add_action( 'init', [ $this, 'create_taxonomies' ] );
            // Hook into the admin menu to add the Slots Manager page
            add_action( 'admin_menu', [ $this, 'add_admin_submenu' ] );
            // Enqueue any scripts or stylesheets needed for the admin page
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
            // Hook into ACF to add the Slots Settings page
            add_action( 'acf/init', [ $this, 'acf_options_init' ] );

			add_action( 'wp_ajax_slot_action', array( 'SlotActionHandler', 'handle_request' ), 10, 0 );
            add_action( 'wp_ajax_provider_action', array( 'ProviderActionHandler', 'handle_providers_request' ), 10, 0 );
            
            add_action( 'wp_ajax_update_list', array( 'Rt_Slots_API', 'clearSlotsCache' ) );

			// Hook to modify ACF JSON load path
			add_filter( 'acf/settings/load_json', array( $this, 'add_acf_json_load_point' ) );
            // Hook to modify ACF JSON Save path
            add_filter('acf/settings/save_json', array($this, 'add_acf_json_save_point'));

            // Slotjava custom options hook
			add_filter( 'acf/load_field/name=post_types', 'my_acf_add_post_types_choices' );

		}

        /**
         * Fired when the plugin is initialized.
         *
         * @since 1.0
         * @access public
         */
        public function init() {
            $this->register_post_type();
            $this->create_taxonomies();

        }

        /**
         * Add admin submenu for Slots Manager page.
         */
        public function add_admin_submenu () {
            global $postType;
            add_submenu_page( 'edit.php?post_type='. $postType .'' , 'Slots Manager', 'Slots Manager', 'manage_options', 'slots-manager', [ $this, 'slots_manager_page' ] );
        }
        
        /**
         * Add ACF JSON load path.
         */
		public function add_acf_json_load_point( $paths ) {
			// Append path to your plugin's ACF JSON folder
			$paths[] = plugin_dir_path( __FILE__ ) . 'acf-json/';
			return $paths;
		}

        /**
         * Add ACF JSON Save Path
         */
        public function add_acf_json_save_point( $path ) {
            // Update path to your plugin's ACF JSON folder
            $path = plugin_dir_path( __FILE__ ) . 'acf-json/';
            return $path;
        }

        /**
         * Add ACF options page for Slots Settings.
         */
        public function acf_options_init() {
            global $postType;
            if ( function_exists( 'acf_add_options_page' ) ) {
                acf_add_options_page(array(
                    'page_title'    => 'Slots Settings',
                    'menu_title'    => 'Slots Settings',
                    'menu_slug'     => 'slots-settings',
                    'parent_slug'   => 'edit.php?post_type=' . $postType . '',
                ));
            }
        }

        /**
         * Render the Slots Manager page.
         */
        public function slots_manager_page() {
            require_once plugin_dir_path( __FILE__ ) . 'templates/admin/slots-manager.php';
        }

        /**
         * Register the custom post type for Slots.
         */
        public function register_post_type() {
            global $postTypeSlug;
            global $postType;

            register_post_type(
                $postType,
                [
                    'labels' => [
                        'name' => __( 'Slots' ),
                        'singular_name' => __( 'Slot' ),
                        'add_new' => __( 'Add New Slot' ),
                        'add_new_item' => __( 'Add New Slot' ),
                        'edit_item' => __( 'Edit Slot' ),
                        'new_item' => __( 'Add New Slot' ),
                        'view_item' => __( 'View Slot' ),
                        'search_items' => __( 'Search Slots' ),
                        'not_found' => __( 'No Slots found' ),
                        'not_found_in_trash' => __( 'No Slots found in trash' )
                    ],
                    'public' => true,
                    'supports' => [ 'title', 'editor', 'thumbnail', 'excerpt', 'tags', 'revisions' ],
                    'capability_type' => 'post',
                    'rewrite' => [ "slug" => $postTypeSlug ],

                    'menu_icon' => 'dashicons-games',
                    'has_archive' => false,
                    'show_in_rest' => true,
                    'taxonomies' => [ 'casino_software', 'game_type' ],
                ]
            );
        }

        /**
         * Register the custom taxonomy for Slots.
         */
        public function create_taxonomies(){
			global $postType;
            global $showProvidersOn;
			// SJ Custom Option - Add Providers to other post types
			if ( is_array( $showProvidersOn ) && !empty( $showProvidersOn)) {
				array_push( $showProvidersOn, $postType );
			} else {
				$showProvidersOn = array( $postType );
            }
			// Game Types
            register_taxonomy(
                'game_type',
				$postType,
                [
                    'labels' => [
                        'name' => __( 'Game Types' ),
                        'singular_name' => __( 'Game Type' ),
                        'search_items' => __( 'Search Game Types' ),
                        'all_items' => __( 'All Game Types' ),
                        'edit_item' => __( 'Edit Game Type' ),
                        'update_item' => __( 'Update Game Type' ),
                        'add_new_item' => __( 'Add New Game Type' ),
                        'new_item_name' => __( 'New Game Type Name' ),
                        'menu_name' => __( 'Game Types' ),
                    ],
                    'hierarchical' => true,
                    'show_in_rest' => true,
                    'show_ui' => true,
                    'show_tagcloud' => false,
                ]
            );

            // Game Providers (Casino Software)
            register_taxonomy(
                'casino_software',
				$showProvidersOn,
                [
                    'labels' => [
                        'name' => __( 'Casino Software' ),
                        'singular_name' => __( 'Casino Software' ),
                        'search_items' => __( 'Search Casino Software' ),
                        'all_items' => __( 'All Casino Software' ),
                        'edit_item' => __( 'Edit Casino Software' ),
                        'update_item' => __( 'Update Casino Software' ),
                        'add_new_item' => __( 'Add New Casino Software' ),
                        'new_item_name' => __( 'New Casino Software Name' ),
                        'menu_name' => __( 'Casino Software' ),
                    ],
                    'hierarchical' => true,
                    'show_in_rest' => true,
                    'show_ui' => true,
                    'show_tagcloud' => false,
                ]
            );
        }
        
        public function enqueue_scripts() {
			global $pagenow, $postType;
            
            // load only on Slot Manager page
            if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == $postType && isset($_GET['page']) && $_GET['page'] == 'slots-manager') {
                //styles
                wp_enqueue_style( 'slots-manager-css', plugin_dir_url( __FILE__ ) . 'css/slots-manager.css', [], '1.0.0' );
                wp_enqueue_style( 'data-table-css', 'https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css', [], '1.0.0');
                //scripts
                wp_enqueue_script( 'slots-manager-js', plugin_dir_url( __FILE__ ) . 'js/slots-manager.js', [], '1.0.0', true );
                wp_localize_script( 'slots-manager-js', 'slots_manager', array(
                    'nonce' => wp_create_nonce( 'slots_manager_nonce' ), 
                    'ajax_url' => admin_url( 'admin-ajax.php' ), 
                    'post_type' => $postType));
                wp_enqueue_script( 'slots-manager-tables-js', plugin_dir_url( __FILE__ ) . 'js/slots-manager-tables.js', [ 'jquery' ], '1.0.0', true );
                wp_enqueue_script('data-table-js', 'https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js', [], '1.0.0', true);        
            }
        }
    }
}

/**
 * Check if ACF is installed and activated
 */
function Rt_slots_check_acf() {
	if ( is_admin() && current_user_can( 'activate_plugins' ) && ! class_exists( 'ACF' ) ) {
		add_action( 'admin_notices', 'Rt_slots_missing_acf_notice' );

		deactivate_plugins( plugin_basename( __FILE__ ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}
add_action( 'admin_init', 'Rt_slots_check_acf' );

/**
 * Display notice if ACF is not installed and activated
 */
function Rt_slots_missing_acf_notice() {
	echo '<div class="notice notice-error"><p>Advanced Custom Fields plugin is required for the Rt Slots plugin. Please install and activate Advanced Custom Fields.</p></div>';
}

/**
 * Add a Read Only field setting to ACF fields
 */
if (!function_exists('add_acf_readonly_field')) {
    function add_acf_readonly_field( $field ) {
        acf_render_field_setting( $field, array(
            'label' => __( 'Read Only?', 'acf' ),
            'instructions' => '',
            'type' => 'radio',
            'name' => 'readonly',
            'choices' => array(
                0 => __( "No", 'acf' ),
                1 => __( "Yes", 'acf' ),
            ),
            'layout' => 'horizontal',
        ) );
    }
}
add_action( 'acf/render_field_settings', 'add_acf_readonly_field' );

/**
 * Add WP API Endpoint to get all post slugs
 */
add_action('rest_api_init', 'add_slugs_endpoint_api');
function add_slugs_endpoint_api() {
    register_rest_route('wp/v2', '/all-slugs', array(
        'methods' => 'GET',
        'callback' => 'get_all_post_slugs',
        'permission_callback' => '__return_true',
    ));
};

/**
 * Get all post slugs form DB
 */
function get_all_post_slugs() {
    global $wpdb;
    global $postType; // Change this to your specific post type
    $transient_key = 'all_post_slugs'; // Unique key for the transient

    // Attempt to get cached data
    $cached_slugs = get_transient($transient_key);

    if ($cached_slugs !== false) {
        // Cached data was found, use it
        return new WP_REST_Response($cached_slugs, 200);
    } else {
        // No cached data, need to query the database
        $query = $wpdb->prepare("
            SELECT p.post_name,
                   MAX(CASE WHEN pm.meta_key = 'temporarily_offline' THEN pm.meta_value END) as temporarily_offline,
                   MAX(CASE WHEN pm.meta_key = 'temporarily_offline_mobile' THEN pm.meta_value END) as temporarily_offline_mobile
            FROM $wpdb->posts p
            LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
            WHERE p.post_type = %s
            GROUP BY p.post_name
        ", $postType);

        $results = $wpdb->get_results($query);

        $slugObjects = array_map(function($result) {
            return array(
                'slug' => $result->post_name,
                'temporarily_offline' => $result->temporarily_offline,
                'temporarily_offline_mobile' => $result->temporarily_offline_mobile,
            );
        }, $results);

        // Cache this data for 12 hours (12 * 60 * 60 = 43,200 seconds)
        set_transient($transient_key, $slugObjects, 43200);

        return new WP_REST_Response($slugObjects, 200);
    }
}


// Initialize the plugin
Rt_Slots::get_instance();