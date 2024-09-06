<?php
/**
 * Plugin Name: Rt TopList Plugin
 * Description: Plugin to display top lists from affiliation cloud
 * Version: 1.0.0
 * Author: Tiago Santos
 *
 * Changelog:
 * 1.0.0 - Initial version
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Include the main plugin class.
require_once plugin_dir_path( __FILE__ ) . 'includes/rt-toplist-api.php';

/**
 * Enqueue plugin styles and scripts just on admin page
 */
function enqueue_scripts() {
	global $pagenow;
	if ($pagenow == 'admin.php' && $_GET['page'] == 'toplist-manager') {
		wp_enqueue_script( 'toplist-manager', plugin_dir_url( __FILE__ ) . 'includes/js/toplist-manager.js', array( 'jquery' ), null, true );
		wp_enqueue_script( 'toplist-manger-ajax', plugin_dir_url( __FILE__ ) . 'includes/js/toplist-manger-ajax.js', array( 'jquery' ), null, true );
		wp_localize_script( 'toplist-manger-ajax', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'toplist-ajax-nonce' ) ) );
	}
}
add_action( 'admin_enqueue_scripts', 'enqueue_scripts' );

/**
 * Add ACF JSON load path.
 */
function add_acf_json_load_point( $paths ) {
	// Append path to your plugin's ACF JSON folder
	$paths[] = plugin_dir_path( __FILE__ ) . 'includes/acf-json/';
	return $paths;
}
// Hook to modify ACF JSON load path
add_filter( 'acf/settings/load_json', 'add_acf_json_load_point' );

/**
 * Toplist Settings page
 */
if ( function_exists( 'acf_add_options_page' ) ) {
	function acf_options_init() {
		acf_add_options_page( array(
			'page_title' => 'Toplist Manager',
			'menu_title' => 'Toplist Manager',
			'menu_slug' => 'toplist-manager',
			'capability' => 'edit_posts',
			'redirect' => false
		) );
	}
}
// Hook into ACF to add the Toplist Settings page
add_action( 'acf/init', 'acf_options_init' );

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

// Prevent saving ACF some fields
add_filter( 'acf/update_value', 'custom_prevent_acf_options_field_save', 10, 3 );
function custom_prevent_acf_options_field_save( $value, $post_id, $field ) {
	// Check if we are on a options page
	if ( $post_id === 'options' ) {
		// Fields to clear
		$fields_to_clear = [ 'toplists_market', 'toplists', 'toplist_json' ];

		// Check if the current field is one of those we want to clear
		if ( in_array( $field['name'], $fields_to_clear ) ) {
			$option_name = 'options_' . $field['name'];
			update_option( $option_name, '' ); // Set the field to an empty string

			return null;
		}
	}

	// Return the value for all other fields and conditions
	return $value;
}