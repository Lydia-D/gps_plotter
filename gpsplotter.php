<?php
/*
Plugin Name: GPS Plotter
Plugin URI: https://www.stpetedesign.com/gps-plotter/
Description: Track Android phones in real time using a map displayed on your website. Upgrade to <a href="https://www.stpetedesign.com/product/gps-plotter-bootleg/">GPS Plotter BOOTLEG</a> our premium version with extra features.
Version: 5.1.2
Author: Steve Curtis, St. Pete Design
Author URI: https://www.stpetedesign.com/gps-plotter/
Text Domain: gpsplotter
Domain Path: /languages
*/
 
//GPS Plotter was forked from GPS Tracker by Nick Fox.

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Plugin version
if ( ! defined( 'GPSPLOTTER_VERSION' ) ) {
	define( 'GPSPLOTTER_VERSION', '1.0.3' );
}

// Plugin Folder Path
if ( ! defined( 'GPSPLOTTER_PLUGIN_DIR' ) ) {
	define( 'GPSPLOTTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin Folder URL
if ( ! defined( 'GPSPLOTTER_PLUGIN_URL' ) ) {
	define( 'GPSPLOTTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Public facing functionality

require_once( GPSPLOTTER_PLUGIN_DIR . 'public/class-gpsplotter.php' );

// Register hooks that are fired when the plugin is activated or deactivated.
// When the plugin is deleted, the uninstall.php file is loaded.

require_once GPSPLOTTER_PLUGIN_DIR . 'includes/class-gpsplotter-setup.php';

register_activation_hook( __FILE__, array( 'Gps_Plotter_Setup', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Gps_Plotter_Setup', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Gps_Plotter', 'get_instance' ) );

// Admin and dashboard functionality

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	require_once( GPSPLOTTER_PLUGIN_DIR . 'admin/views/options.php' );
	//add_action( 'plugins_loaded', array( 'Gps_Plotter_Admin', 'get_instance' ) );
}
