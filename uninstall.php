<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Gps_Plotter is based on GPS_Tracker by Nick Fox
 *
 * @package   Gps_Tracker
 * @category  Core
 * @author    Nick Fox <nickfox@websmithing.com>
 * @license   MIT/GPLv2 or later
 * @link      https://www.websmithing.com/gps-tracker
 * @copyright 2014 Nick Fox
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// delete gps plotter table and two stored procedures

global $wpdb;
$table_name = $wpdb->prefix . 'gps_locations';
$sql = "DROP TABLE IF EXISTS {$table_name};";
$wpdb->query($sql);

$table_name = $wpdb->prefix . 'gps_logger';
$sql = "DROP TABLE IF EXISTS {$table_name};";
$wpdb->query($sql);

$procedure_name =  $wpdb->prefix . "get_routes";
$wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");

$procedure_name =  $wpdb->prefix . "get_geojson_route";
$wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");

$procedure_name =  $wpdb->prefix . "get_all_geojson_routes";
$wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");

$procedure_name =  $wpdb->prefix . "get_last_position";
$wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");

$procedure_name =  $wpdb->prefix . "delete_route";
$wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");

$table_name = $wpdb->prefix . 'gps_logger';
$sql = "DROP TABLE IF EXISTS {$table_name};";
$wpdb->query($sql);

delete_option( 'gpsplotter_app_id' );