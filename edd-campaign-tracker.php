<?php
/**
 * Plugin Name: Easy Digital Downloads - Campaign Tracker
 * Plugin URI: http://bulkwp.com
 * Description: Tracks Google campaign data and associates EDD orders with campaign data.
 * License: GPL
 * Author: Bulk WP
 * Version: 1.0.0-beta2
 * Author URI: http://bulkwp.com/
 * Text Domain: edd-campaign-tracker
 * Domain Path: languages/
 *
 * @copyright Copyright (c) Bulk WP (email : support@bulkwp.com)
 * @author    Bulk WP <http://bulkwp.com>
 * @package   EDD\CampaignTracker
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Main Plugin class
 *
 * @version 1.0.0
 */
class EDD_Campaign_Tracker {

	/**
	 *
	 * @var         EDD_Campaign_Tracker $instance The one true EDD_Campaign_Tracker
	 * @since       1.0.0
	 */
	private static $instance;

	/**
	 * Get active instance
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      object self::$instance The one true EDD_Campaign_Tracker
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new EDD_Campaign_Tracker();
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->load_textdomain();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Setup plugin constants
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      void
	 */
	private function setup_constants() {
		// Plugin version
		define( 'EDD_CAMPAIGN_TRACKER_VER', '1.0.0' );

		// Plugin path
		define( 'EDD_CAMPAIGN_TRACKER_DIR', plugin_dir_path( __FILE__ ) );

		// Plugin URL
		define( 'EDD_CAMPAIGN_TRACKER_URL', plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Include necessary files
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      void
	 */
	private function includes() {
		// Include scripts
		require_once EDD_CAMPAIGN_TRACKER_DIR . '/includes/class-ga-parser.php';
		require_once EDD_CAMPAIGN_TRACKER_DIR . '/includes/campaign-logger.php';
		require_once EDD_CAMPAIGN_TRACKER_DIR . '/includes/payment-screen.php';
		require_once EDD_CAMPAIGN_TRACKER_DIR . '/includes/email-tag.php';
	}

	/**
	 * Internationalization
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function load_textdomain() {
		// Set filter for language directory
		$lang_dir = EDD_CAMPAIGN_TRACKER_DIR . '/languages/';
		$lang_dir = apply_filters( 'edd_campaign_tracker_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'edd-campaign-tracker' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'edd-campaign-tracker', $locale );

		// Setup paths to current locale file
		$mofile_local   = $lang_dir . $mofile;
		$mofile_global  = WP_LANG_DIR . '/edd-campaign-tracker/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/edd-campaign-tracker/ folder
			load_textdomain( 'edd-campaign-tracker', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/edd-campaign-tracker/languages/ folder
			load_textdomain( 'edd-campaign-tracker', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'edd-campaign-tracker', false, $lang_dir );
		}
	}

	/**
	 * Run action and filter hooks
	 *
	 * @since 1.0.0
	 */
	private function hooks() {
		add_action( 'admin_init', array( $this, 'licensed_updates' ), 9 );
	}

	/**
	 * Register EDD License
	 *
	 * @since 1.0.0
	 */
	public function licensed_updates() {
		if ( class_exists( 'EDD_License' ) ) {
			$license = new EDD_License( __FILE__, 'Campaign Tracker', EDD_CAMPAIGN_TRACKER_VER, 'Bulk WP' );
		}
	}
}

/**
 * The main function responsible for returning the one true EDD_Campaign_Tracker
 * instance to functions everywhere.
 *
 * @since       1.0.0
 * @return      \EDD_Campaign_Tracker The one true EDD_Campaign_Tracker
 */
function EDD_Campaign_Tracker_load() {
	if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		if ( ! class_exists( 'EDD_Extension_Activation' ) ) {
			require_once 'includes/class.extension-activation.php';
		}

		$activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();
		return EDD_Campaign_Tracker::instance();
	} else {
		return EDD_Campaign_Tracker::instance();
	}
}

add_action( 'plugins_loaded', 'EDD_Campaign_Tracker_load' );
