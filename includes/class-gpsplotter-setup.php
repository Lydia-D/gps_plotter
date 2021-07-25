<?php
/**
 * Handles activation/deactivation of plugin
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

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gps_Plotter_Setup Class
 *
 * @since 1.0.0
 */
class Gps_Plotter_Setup
{
	/**
	 * Fired when the plugin is activated. Create table for Gps Plotter and two stored procedures.
     * One to get all the routes for display in the drop down box and the other to get a single
     * route in geojson format to create the map and populate the markers.
	 *
	 * @since 1.0.0
     * @global $wpdb
     * @global $charset_collate
     * @return void
	 */
    public static function activate()
    {
        // clear the permalinks©
        flush_rewrite_rules();
        
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
        $plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "activate-plugin_{$plugin}" );

        global $wpdb;
        global $charset_collate;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); 
        $table_name = $wpdb->prefix . 'gps_locations';
        
        $sql = "IF NOT EXISTS{$table_name} (
          CREATE TABLE {$table_name} (
          gps_location_id int(10) unsigned NOT NULL AUTO_INCREMENT,
          last_update timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          latitude decimal(10,7) NOT NULL DEFAULT '0.0000000',
          longitude decimal(10,7) NOT NULL DEFAULT '0.0000000',
          user_name varchar(50) NOT NULL DEFAULT '',
          phone_number varchar(50) NOT NULL DEFAULT '',          
          session_id varchar(50) NOT NULL DEFAULT '',
          speed int(10) unsigned NOT NULL DEFAULT '0',
          direction int(10) unsigned NOT NULL DEFAULT '0',
          distance decimal(10,1) NOT NULL DEFAULT '0.0',
          gps_time timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
          location_method varchar(50) NOT NULL DEFAULT '',
          accuracy int(10) unsigned NOT NULL DEFAULT '0',
          extra_info varchar(255) NOT NULL DEFAULT '',
          event_type varchar(50) NOT NULL DEFAULT '',
          UNIQUE KEY (gps_location_id),
          KEY session_id_index (session_id),
          KEY user_name_index (user_name)
        ) $charset_collate; )";

        dbDelta($sql);
        
        $location_row_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name};" );
        
        
        $procedure_name =  $wpdb->prefix . "get_routes";
        $wpdb->query( "DROP PROCEDURE IF EXISTS {$procedure_name};" ); 
           
        $sql = "CREATE PROCEDURE {$procedure_name}()
        BEGIN
        CREATE TEMPORARY TABLE temp_routes (
            session_id VARCHAR(50),
            user_name VARCHAR(50),
            start_time DATETIME,
            end_time DATETIME)
            ENGINE = MEMORY;

        INSERT INTO temp_routes (session_id, user_name)
        SELECT DISTINCT session_id, user_name
        FROM {$table_name};

        UPDATE temp_routes tr
        SET start_time = (SELECT MIN(gps_time) FROM {$table_name} gl
        WHERE gl.session_id = tr.session_id
        AND gl.user_name = tr.user_name);

        UPDATE temp_routes tr
        SET end_time = (SELECT MAX(gps_time) FROM {$table_name} gl
        WHERE gl.session_id = tr.session_id
        AND gl.user_name = tr.user_name);

        SELECT
        CONCAT('{ \"session_id\": \"', CAST(session_id AS CHAR),  '\", \"user_name\": \"', user_name, '\", \"times\": \"(', DATE_FORMAT(start_time, '%b %e %Y %h:%i%p'), ' - ', DATE_FORMAT(end_time, '%b %e %Y %h:%i%p'), ')\" }') json
        FROM temp_routes
        ORDER BY start_time DESC;

        DROP TABLE temp_routes;
        END;";                

        $wpdb->query( $sql ); 
        // $wpdb->print_error();
        
        $procedure_name =  $wpdb->prefix . "get_geojson_route";
        $wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");
      
        $sql = "CREATE PROCEDURE {$procedure_name}(
        _session_id VARCHAR(50))
        BEGIN
        SET @counter := 0;
        SELECT
        CONCAT('{\"type\": \"Feature\", \"id\": \"', CAST(session_id AS CHAR), '\", \"properties\": {\"speed\": ', CAST(speed AS CHAR), ', \"direction\": ', CAST(direction AS CHAR), ', \"distance\": ', CAST(distance AS CHAR), ', \"location_method\": \"', CAST(location_method AS CHAR), '\", \"gps_time\": \"', DATE_FORMAT(gps_time, '%b %e %Y %h:%i%p'), '\", \"user_name\": \"', CAST(user_name AS CHAR), '\", \"phone_number\": \"', CAST(phone_number AS CHAR), '\", \"accuracy\": ', CAST(accuracy AS CHAR), ', \"geojson_counter\": ', @counter := @counter + 1, ', \"extra_info\": \"', CAST(extra_info AS CHAR), '\"}, \"geometry\": {\"type\": \"Point\", \"coordinates\": [', CAST(longitude AS CHAR), ', ', CAST(latitude AS CHAR), ']}}') geojson 
        FROM {$table_name}
        WHERE session_id = _session_id
        ORDER BY last_update;
        END;";

        $wpdb->query( $sql );
        
        $procedure_name =  $wpdb->prefix . "get_all_geojson_routes";
        $wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");
      
        $sql = "CREATE PROCEDURE {$procedure_name}()
        BEGIN
        SET @counter := 0;
        SELECT
        session_id,
        gps_time,
        CONCAT('{\"type\": \"Feature\", \"id\": \"', CAST(session_id AS CHAR), '\", \"properties\": {\"speed\": ', CAST(speed AS CHAR), ', \"direction\": ', CAST(direction AS CHAR), ', \"distance\": ', CAST(distance AS CHAR), ', \"location_method\": \"', CAST(location_method AS CHAR), '\", \"gps_time\": \"', DATE_FORMAT(gps_time, '%b %e %Y %h:%i%p'), '\", \"user_name\": \"', CAST(user_name AS CHAR), '\", \"phone_number\": \"', CAST(phone_number AS CHAR), '\", \"accuracy\": ', CAST(accuracy AS CHAR), ', \"geojson_counter\": ', @counter := @counter + 1, ', \"extra_info\": \"', CAST(extra_info AS CHAR), '\"}, \"geometry\": {\"type\": \"Point\", \"coordinates\": [', CAST(longitude AS CHAR), ', ', CAST(latitude AS CHAR), ']}}') geojson 
        FROM (SELECT MAX(gps_location_id) ID
        FROM {$table_name}  
        WHERE session_id != '0' && CHAR_LENGTH(session_id) != 0 && gps_time != '0000-00-00 00:00:00'
        GROUP BY session_id) AS MaxID
        JOIN {$table_name} ON {$table_name}.gps_location_id = MaxID.ID
        ORDER BY gps_time;
        END;";

        $wpdb->query( $sql );
        
        $procedure_name =  $wpdb->prefix . "delete_route";
        $wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");

        $sql = "CREATE PROCEDURE {$procedure_name}(
        _session_id VARCHAR(50))
        BEGIN
        DELETE FROM {$table_name}
        WHERE sessionID = _sessionID;
        END;";
        
        $wpdb->query( $sql ); 
        
        // make sure this is last AFTER the stored procedures or wrong table name gets used
        $table_name = $wpdb->prefix . 'gps_logger';
        $sql = "DROP TABLE IF EXISTS {$table_name};  
          CREATE TABLE {$table_name} (
          gps_logger_id int(10) unsigned NOT NULL AUTO_INCREMENT,
          last_update timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          gps_action varchar(5) NOT NULL DEFAULT '',
          phone_number varchar(50) NOT NULL DEFAULT '',
          app_id varchar(50) NOT NULL DEFAULT '',          
          session_id varchar(50) NOT NULL DEFAULT '',
          nonce varchar(50) NOT NULL DEFAULT '',
          UNIQUE KEY (gps_logger_id)
        ) $charset_collate;";

        dbDelta( $sql ); 
    }

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since 1.0.0
	 *
	 */
    public static function deactivate()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
        $plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "deactivate-plugin_{$plugin}" );

        // uncomment during development
       
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'gps_logger';
        $sql = "DROP TABLE IF EXISTS {$table_name};";
        $wpdb->query($sql);
    
        $procedure_name =  $wpdb->prefix . "get_routes";
        $wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");
    
        $procedure_name =  $wpdb->prefix . "get_geojson_route";
        $wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");
        
        $procedure_name =  $wpdb->prefix . "get_all_geojson_routes";
        $wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");
        
        $procedure_name =  $wpdb->prefix . "delete_route";
        $wpdb->query("DROP PROCEDURE IF EXISTS {$procedure_name};");
        
        $table_name = $wpdb->prefix . 'gps_logger';
        $sql = "DROP TABLE IF EXISTS {$table_name};";
        $wpdb->query($sql);
        
        delete_option( 'gpsplotter_app_id' );

    }
}