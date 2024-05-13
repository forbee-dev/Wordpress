<?php
/**
 * Plugin Name: Raketech Slots
 * Description: Plugin that integrates with the provided API to dynamically generate and manage content for slots. 
 * Version: 1.1.3
 * Author: Tiago Santos
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}

// Include the main plugin class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-rt-slots.php';

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'Raketech_Slots', 'get_instance' ) );


