<?php
/**
 * The core plugin class.
 * 
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Get the ACF fields from the options page and set the global variables accordingly
 */
add_action( 'acf/init', 'set_global_variables' );
function set_global_variables() {
    global $sportsApiUrl, $tournamentsCPTSlug, $tournamentsCPT, $matchesCPTSlug, $matchesCPT, $site_specific_options, $activate_ocb_specific_settings ;

    if ( function_exists( 'get_field' ) ) {
        $sportsApiUrl = get_field( 'sports_index_url', 'option' ) ?: '';
        $tournamentsCPTSlug = strtolower( get_field( 'tournaments_post_type_slug', 'option' ) ?: 'tournaments' );
        $tournamentsCPT = strtolower( get_field( 'tournaments_post_type', 'option' ) ?: 'tournaments' );
        $matchesCPTSlug = strtolower( get_field( 'matches_post_type_slug', 'option' ) ?: 'matches' );
        $matchesCPT = strtolower( get_field( 'matches_post_type', 'option' ) ?: 'matches' );
        $site_specific_options = get_field('site_specific_custom_options', 'option');
        $activate_ocb_specific_settings = $site_specific_options['activate_ocb_specific_settings'] ?? false;
    }
}

if(!class_exists('Rt_Sports')) {
    class Rt_Sports {
        
        /**
        * The single instance of the class.
        *
        * @var Rt_Sports
        * @since 1.0
        */
        protected static $_instance = null;

        /**
        * Main Rt_Sports Instance.
        *
        * Ensures only one instance of Rt_Sports is loaded or can be loaded.
        *
        * @since 1.0
        * @static
        * @return Rt_Sports - Main instance.
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
            require_once plugin_dir_path( __FILE__ ) . 'class-rt-sports-api.php';
            require_once plugin_dir_path( __FILE__ ) . 'class-rt-tournaments-ajax.php';
            require_once plugin_dir_path( __FILE__ ) . 'class-rt-matches-ajax.php';
            require_once plugin_dir_path( __FILE__ ) . 'class-rt-tournament-matches-connection.php';
            // Site specific custom options
			include_once plugin_dir_path( __FILE__ ) . 'ocb/custom-options.php';

		}

        /**
         * Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
         *
         * @since 1.0
         * @access private
         */
        private function hooks() {
            register_deactivation_hook( __FILE__, array( 'Rt_Sports', 'deactivate' ) );
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
            // Hook into the admin menu to add the Sports Manager page
            add_action( 'admin_menu', [ $this, 'add_admin_submenu' ] );
            // Enqueue any scripts or stylesheets needed for the admin page
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
            // Hook into ACF to add the Sports Settings page
            add_action( 'acf/init', [ $this, 'acf_options_init' ] );

			add_action( 'wp_ajax_tournament_action', array( 'TournamentsActionHandler', 'handle_request' ));
            add_action( 'wp_ajax_matches_action', array( 'MatchesActionHandler', 'handle_matches_request' ), 10, 0 );
            // Add the AJAX action for fetching upcoming matches
            add_action('wp_ajax_fetch_upcoming_matches', array('MatchesActionHandler', 'handle_fetch_upcoming_matches'));
            add_action('wp_ajax_nopriv_fetch_upcoming_matches', array('MatchesActionHandler', 'handle_fetch_upcoming_matches'));
            
            add_action( 'wp_ajax_update_list', array( 'Rt_Sports_API', 'clearSportsCache' ) );

			// Hook to modify ACF JSON load path
			add_filter( 'acf/settings/load_json', array( $this, 'add_acf_json_load_point' ) );
            // Hook to modify ACF JSON Save path
            add_filter('acf/settings/save_json', array($this, 'add_acf_json_save_point'));
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

            global $tournamentsCPT, $matchesCPT;
            new RT_Tournament_Matches_Connection($tournamentsCPT, $matchesCPT);
        }

        /**
         * Add admin submenu for Sports Manager page.
         */
        public function add_admin_submenu() {
            add_menu_page(
                'Sports Options',
                'Sports Options',
                'manage_options',
                'sports-settings',
                '',
                'dashicons-games',
                30
            );

            add_submenu_page(
                'sports-settings',
                'Sports Manager',
                'Sports Manager',
                'manage_options',
                'sports-manager',
                [$this, 'sports_manager_page']
            );

            add_submenu_page(
                'sports-settings',
                'Update Matches',
                'Update Matches',
                'manage_options',
                'update-matches',
                [$this, 'update_matches_page']
            );
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
         * Add ACF options page for Sports Settings.
         */
        public function acf_options_init() {
            global $tournamentsCPT, $matchesCPT;

            if (function_exists('acf_add_options_sub_page')) {
                // Add Sports Options page
                acf_add_options_sub_page(array(
                    'page_title'  => 'Sports Settings',
                    'menu_title'  => 'Sports Settings',
                    'parent_slug' => 'sports-options',
                    'menu_slug'   => 'sports-settings',
                ));
            }
        }

        /**
         * Render the Sports Manager page.
         */
        public function sports_manager_page() {
            require_once plugin_dir_path( __FILE__ ) . 'templates/admin/sports-manager.php';
        }

        /**
         * Register the custom post type for Sports.
         */
        public function register_post_type() {
            global $tournamentsCPT, $tournamentsCPTSlug, $matchesCPT, $matchesCPTSlug, $activate_ocb_specific_settings;

            $rewrite_rule = $activate_ocb_specific_settings 
                ? [ "slug" => $matchesCPTSlug.'/%tournament%/%matches%', 'with_front' => false ]
                : [ "slug" => $matchesCPTSlug, 'with_front' => false ];

            register_post_type(
                $tournamentsCPT,
                [
                    'labels' => [
                        'name' => __( 'Tournaments' ),
                        'singular_name' => __( 'Tournament' ),
                        'add_new' => __( 'Add New Tournament' ),
                        'add_new_item' => __( 'Add New Tournament' ),
                        'edit_item' => __( 'Edit Tournament' ),
                        'new_item' => __( 'Add New Tournament' ),
                        'view_item' => __( 'View Tournament' ),
                        'search_items' => __( 'Search Tournaments' ),
                        'not_found' => __( 'No Tournaments found' ),
                        'not_found_in_trash' => __( 'No Tournaments found in trash' )
                    ],
                    'public' => true,
                    'publicly_queryable' => true,
                    'supports' => [ 'title', 'editor', 'thumbnail', 'excerpt', 'tags', 'revisions', 'author' ],
                    'capability_type' => 'page',
                    'rewrite' => [ "slug" => $tournamentsCPTSlug, 'with_front' => false ],
                    'menu_icon' => 'dashicons-games',
                    'has_archive' => false,
                    'show_in_rest' => true,
                    'taxonomies' => [ 'game_type' ],
                ]
            );

            register_post_type(
                $matchesCPT,
                [
                'labels' => [
                    'name' => __( 'Matches' ),
                    'singular_name' => __( 'Match' ),
                        'add_new' => __( 'Add New Match' ),
                        'add_new_item' => __( 'Add New Match' ),
                        'edit_item' => __( 'Edit Match' ),
                        'new_item' => __( 'Add New Match' ),
                        'view_item' => __( 'View Match' ),
                        'search_items' => __( 'Search Matches' ),
                        'not_found' => __( 'No Matches found' ),
                        'not_found_in_trash' => __( 'No Matches found in trash' )
                    ],
                    'public' => true,
                    'publicly_queryable' => true,
                    'supports' => [ 'title', 'editor', 'thumbnail', 'excerpt', 'tags', 'revisions', 'author' ],
                    'capability_type' => 'page',
                    'rewrite' => $rewrite_rule,
                    'menu_icon' => 'dashicons-games',
                    'has_archive' => false,
                    'show_in_rest' => true,
                    'taxonomies' => [ 'game_type' ],
                ]
            );
        }

        /**
         * Register the custom taxonomy for Sports.
         */
        public function create_taxonomies(){
            global $tournamentsCPT, $matchesCPT;
			// Game Types
            register_taxonomy(
                'game_type',
                [ $tournamentsCPT, $matchesCPT ],
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
        }

        
        
        public function enqueue_scripts() {
            global $pagenow, $tournamentsCPT, $matchesCPT;
            
            // Load only on Sports Manager page for both CPTs
            if ($pagenow == 'admin.php' && 
                isset($_GET['page']) && 
                $_GET['page'] == 'sports-manager') {
                
                //styles
                wp_enqueue_style('sports-manager-css', plugin_dir_url(__FILE__) . 'css/sports-manager.css', [], '1.0.0');
                wp_enqueue_style('data-table-css', 'https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css', [], '1.0.0');
                wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', array(), '1.12.1');
                
                //scripts
                wp_enqueue_script('sports-manager-js', plugin_dir_url(__FILE__) . 'js/sports-manager.js', [], '1.0.0', true);
                wp_localize_script('sports-manager-js', 'sports_manager', array(
                    'nonce' => wp_create_nonce('sports_manager_nonce'),
                    'ajax_url' => admin_url('admin-ajax.php')
                ));
                wp_enqueue_script('sports-manager-tables-js', plugin_dir_url(__FILE__) . 'js/sports-manager-tables.js', ['jquery', 'jquery-ui-datepicker'], '1.0.0', true);
                wp_enqueue_script('data-table-js', 'https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js', [], '1.0.0', true);
            }
        }

        // Add this method to your class
        public function update_matches_page() {
            if (isset($_POST['update_matches'])) {
                if (class_exists('MatchesActionHandler') && method_exists('MatchesActionHandler', 'update_matches_data')) {
                    MatchesActionHandler::update_matches_data(true);
                    echo '<div class="updated"><p>Matches updated successfully!</p></div>';
                } else {
                    echo '<div class="error"><p>Unable to update matches. Required class or method not found.</p></div>';
                }
            }
            ?>
            <div class="wrap">
                <h1>Update Matches</h1>
                <form method="post">
                    <input type="submit" name="update_matches" class="button button-primary" value="Update Matches">
                </form>
            </div>
            <?php
        }
    }
}

/**
 * Check if ACF is installed and activated
 */
function Rt_sports_check_acf() {
	if ( is_admin() && current_user_can( 'activate_plugins' ) && ! class_exists( 'ACF' ) ) {
		add_action( 'admin_notices', 'Rt_sports_missing_acf_notice' );

		deactivate_plugins( plugin_basename( __FILE__ ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}
add_action( 'admin_init', 'Rt_sports_check_acf' );

/**
 * Display notice if ACF is not installed and activated
 */
function Rt_sports_missing_acf_notice() {
	echo '<div class="notice notice-error"><p>Advanced Custom Fields plugin is required for the Rt SportsIndex plugin. Please install and activate Advanced Custom Fields.</p></div>';
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
    global $tournamentsCPT, $matchesCPT; // Define your two CPTs
    $transient_key = 'all_post_slugs';

    // Attempt to get cached data
    $cached_slugs = get_transient($transient_key);

    if ($cached_slugs !== false) {
        return new WP_REST_Response($cached_slugs, 200);
    } else {
        $post_types = array($tournamentsCPT, $matchesCPT);
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        $query = $wpdb->prepare("
            SELECT p.post_name, p.post_type
            FROM $wpdb->posts p
            WHERE p.post_type IN ($placeholders)
            GROUP BY p.post_name, p.post_type
        ", $post_types);

        $results = $wpdb->get_results($query);

        $slugObjects = array_map(function($result) {
            return array(
                'slug' => $result->post_name,
                'post_type' => $result->post_type
            );
        }, $results);

        set_transient($transient_key, $slugObjects, 43200);

        return new WP_REST_Response($slugObjects, 200);
    }
}

// Initialize the plugin
Rt_Sports::get_instance();
