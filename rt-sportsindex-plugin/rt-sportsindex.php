<?php
/**
 * Plugin Name: Rt SportsIndex Plugin
 * Description: Plugin that integrates with the provided API to dynamically generate and manage content for Sports. 
 * Version: 1.0.8
 * Author: Tiago Santos
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

// Include the main plugin class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-rt-sports.php';

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'Rt_Sports', 'get_instance' ) );
