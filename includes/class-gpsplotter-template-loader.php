<?php
/**
 * Handles loading locating and loading template files, extends GpsPlotter_Gamajo_Template_Loader class
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
 * GpsPlotter_Template_Loader Class
 *
 * @since 1.0.0
 */
class GpsPlotter_Template_Loader extends GpsPlotter_Gamajo_Template_Loader {

	/**
	 * Prefix for filter names.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $filter_prefix = 'gpsplotter';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $theme_template_directory = 'templates';

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $plugin_directory = GPSPLOTTER_PLUGIN_DIR;

}