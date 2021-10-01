<?php
/**
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

if ( ! class_exists( 'Gps_Plotter' ) ) :

/**
 * Main Gps_Plotter Class
 *
 * @since 1.0.0
 */
class Gps_Plotter {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.0.3';

	/**
	 * Unique identifier for your plugin.
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $plugin_slug = 'gpsplotter';

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Gps Plotter Endpoint Object
	 *
	 * @var object
	 * @since 1.0.0
	 */
	public $gpsplotter_endpoint;

	/**
	 * Gps Plotter Ajax Object
	 *
	 * @var object
	 * @since 1.0.0
	 */
	public $gpsplotter_ajax;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
        $this->includes();
        $this->gpsplotter_endpoint = new Gps_Plotter_Endpoint();
        $this->gpsplotter_ajax = new Gps_Plotter_Ajax(); 

		// load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// load public-facing style sheet and javascript
		add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );

        // to use this plugin, add this shortcode to any page or post: [gps_plotter]  
        add_shortcode( 'gps_plotter', array( $this,'gpsplotter_map_shortcode' ) );
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 * @return object   A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
    
	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'not good.', 'gpsplotter' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.0.0
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'not good.', 'gpsplotter' ), '1.0.0' );
	}
        
	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 1.0.0
	 * @return void
	 */
	private function includes() {
		require_once GPSPLOTTER_PLUGIN_DIR . 'includes/class-gpsplotter-gamajo-template-loader.php';
		require_once GPSPLOTTER_PLUGIN_DIR . 'includes/class-gpsplotter-template-loader.php';
		require_once GPSPLOTTER_PLUGIN_DIR . 'public/includes/class-gpsplotter-ajax.php';
        require_once GPSPLOTTER_PLUGIN_DIR . 'public/includes/class-gpsplotter-endpoint.php';
    }
        
    /**
     * Gps Plotter Map Shortcode
     *
     * Displays the map, dropdown boxes and buttons for Gps Plotter 
     *
     * @since 1.0.0
     * @return string
     */
    public function gpsplotter_map_shortcode() {
		$this->enqueue_scripts();
		$this->enqueue_styles();
        $templates = new GpsPlotter_Template_Loader();

        ob_start();
        $templates->get_template_part( 'shortcode', 'gpsplotter-map' );
        return ob_get_clean();
    }

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {  
		 
        wp_enqueue_style( $this->plugin_slug . '-leaflet-styles'); 		
        /*Steve edit 6/4/18 this css file breaks WordPress theme css files. 
		wp_enqueue_style('gpsplotter-bootstrap', '//maxcdn.bootstrapcdn.com/bootswatch/3.3.5/superhero/bootstrap.min.css', false, '3.3.0', 'all'); */
        wp_enqueue_style( $this->plugin_slug . '-light-styles'); 
		
	}

	public function register_styles() {  
		 
        wp_register_style( $this->plugin_slug . '-leaflet-styles', plugins_url( 'assets/js/leaflet-0.7.5/leaflet.css', __FILE__ ), array(), self::VERSION ); 		
        /*Steve edit 6/4/18 this css file breaks WordPress theme css files. 
		wp_enqueue_style('gpsplotter-bootstrap', '//maxcdn.bootstrapcdn.com/bootswatch/3.3.5/superhero/bootstrap.min.css', false, '3.3.0', 'all'); */
        wp_register_style( $this->plugin_slug . '-light-styles', plugins_url( 'assets/css/light.css', __FILE__ ), array(), self::VERSION ); 
		
	}

	

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since 1.0.0
	 
	 /*js?v=3&sensor=false&libraries=adsense*/

	public function register_scripts(){
		wp_register_script( $this->plugin_slug . '-gpsplotter-google-maps', '//maps.googleapis.com/maps/api/js?libraries=places&key='.get_option("gps-plotter-api-key"), array(), self::VERSION );
        wp_register_script( $this->plugin_slug . '-gpsplotter-map-js', plugins_url( 'assets/js/gpsplotter-map.js', __FILE__ ), array('jquery'), self::VERSION );
        wp_register_script( $this->plugin_slug . '-gpsplotter-openmap-js', plugins_url( 'assets/js/gpsplotter-openmap.js', __FILE__ ), array('jquery'), self::VERSION );
        wp_register_script( $this->plugin_slug . '-gpsplotter-leaflet-js', plugins_url( 'assets/js/leaflet-0.7.5/leaflet.js', __FILE__ ), array('jquery'), self::VERSION );
        wp_register_script( $this->plugin_slug . '-gpsplotter-google-js', plugins_url( 'assets/js/leaflet-plugins/google.js', __FILE__ ), array('jquery'), self::VERSION );
        wp_register_script( $this->plugin_slug . '-gpsplotter-bing-js', plugins_url( 'assets/js/leaflet-plugins/bing.js', __FILE__ ), array('jquery'), self::VERSION );        
		
		if(get_option( 'map-type',"openmap") == "google"){
        wp_localize_script( $this->plugin_slug . '-gpsplotter-map-js', 'map_js_vars', array(
            'plugin_url'                    => GPSPLOTTER_PLUGIN_URL,
            'ajax_url'                      => admin_url('admin-ajax.php'),
            'get_routes_nonce'              => wp_create_nonce('get-routes-nonce'),
            'get_geojson_route_nonce'       => wp_create_nonce('get-geojson-route-nonce'),
            'get_all_geojson_routes_nonce'  => wp_create_nonce('get-all-geojson-routes-nonce'),
            'get_last_position_nonce'       => wp_create_nonce('get-last-position-nonce'),
            'delete_route_nonce'            => wp_create_nonce('delete-route-nonce')
        	)
		);
		}else{
			wp_localize_script( $this->plugin_slug . '-gpsplotter-openmap-js', 'map_js_vars', array(
				'plugin_url'                    => GPSPLOTTER_PLUGIN_URL,
				'ajax_url'                      => admin_url('admin-ajax.php'),
				'get_routes_nonce'              => wp_create_nonce('get-routes-nonce'),
				'get_geojson_route_nonce'       => wp_create_nonce('get-geojson-route-nonce'),
				'get_all_geojson_routes_nonce'  => wp_create_nonce('get-all-geojson-routes-nonce'),
				'get_last_position_nonce'       => wp_create_nonce('get-last-position-nonce'),
				'delete_route_nonce'            => wp_create_nonce('delete-route-nonce')
				)
			);
		} 
	} 
	 
	 
	public function enqueue_scripts() { 
	
	    
		wp_enqueue_script( $this->plugin_slug . '-gpsplotter-google-maps');
		if(get_option( 'map-type',"openmap") == "google"){
			wp_enqueue_script( $this->plugin_slug . '-gpsplotter-map-js');
		}else{
			wp_enqueue_script( $this->plugin_slug . '-gpsplotter-openmap-js');
		}
        wp_enqueue_script( $this->plugin_slug . '-gpsplotter-leaflet-js');
        wp_enqueue_script( $this->plugin_slug . '-gpsplotter-google-js');
        wp_enqueue_script( $this->plugin_slug . '-gpsplotter-bing-js');        
        
	}
}
endif; // End if class_exists
