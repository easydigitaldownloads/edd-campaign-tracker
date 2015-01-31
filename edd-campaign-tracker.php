<?php
/**
 * Plugin Name: EDD Campaign Tracker
 * Plugin URI: http://bulkwp.com
 * Description: Tracks campaign and associates EDD orders with campaign
 * License: GPL
 * Author: Bulk WP
 * Version: 0.1
 * Author URI: http://sudarmuthu.com/
 * Text Domain: edd-campaign-tracker
 * Domain Path: languages/
 *
 * @copyright       Copyright (c) Bulk WP (email : support@bulkwp.com)
 * @author   Bulk WP <http://bulkwp.com>
 * @package         EDD\Campaign Tracker
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Main Plugin class
 *
 * @version 0.1
 * @author Sudar
 */
class EDD_Campaign_Tracker {

	/**
	 * Start everything
	 *
	 * @since 0.1
	 */
	public function __construct() {
		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugin_dir_url( __FILE__ );

		$this->includes();

		// Basic setup
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		add_action( 'admin_init', array( $this, 'licensed_updates' ), 9 );
		add_action( 'plugins_loaded', array( $this, 'i18n' ) );
	}

	/**
	 * Load localization.
	 *
	 * @since 0.1
	 */
	public function i18n() {
		load_plugin_textdomain( 'edd-campaign-tracker', false, $this->directory_path . '/languages/' );
	}

	/**
	 * Include file dependencies.
	 *
	 * @since 0.1
	 */
	public function includes() {
		if ( $this->meets_requirements() ) {
			require_once $this->directory_path . '/includes/class-ga-parser.php';
			require_once $this->directory_path . '/includes/campaign-logger.php';
			require_once $this->directory_path . '/includes/payment-screen.php';
			require_once $this->directory_path . '/includes/email-tag.php';
		}
	}

	/**
	 * Register EDD License
	 *
	 * @since 0.1
	 */
	public function licensed_updates() {
		if ( class_exists( 'EDD_License' ) ) {
			//$license = new EDD_License( __FILE__, 'Campaign Tracker', '1.0', 'Sudar Muthu' );
		}
	}

	/**
	 * Output error message and disable plugin if requirements are not met.
	 *
	 * This fires on admin_notices.
	 *
	 * @since 0.1
	 */
	public function maybe_disable_plugin() {
		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'EDD Campaign Tracker requires Easy Digital Downloads 2.0 or greater and has been <a href="%s">deactivated</a>.', 'edd-campaign-tracker' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Check if all requirements are met.
	 *
	 * @since 0.1
	 * @access private
	 * @return bool True if requirements are met, otherwise false.
	 */
	private function meets_requirements() {
		if ( function_exists( 'EDD' ) && defined( 'EDD_VERSION' ) && version_compare( EDD_VERSION, '2.0', '>=' ) ) {
			return true;
		} else {
			return false;
		}
	}
}
new EDD_Campaign_Tracker;
?>
