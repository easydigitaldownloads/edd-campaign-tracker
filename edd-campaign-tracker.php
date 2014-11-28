<?php
/**
Plugin Name: EDD Campaign Tracker
Plugin URI: http://bulkwp.com
Description: Tracks campaign and associates EDD orders with campaign
Donate Link: http://sudarmuthu.com/if-you-wanna-thank-me
License: GPL
Author: Sudar
Version: 0.1
Author URI: http://sudarmuthu.com/
Text Domain: edd-ct
Domain Path: languages/

=== RELEASE NOTES ===
Check readme file for release notes
*/

/**  Copyright 2014  Sudar Muthu  (email : sudar@sudarmuthu.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
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
		load_plugin_textdomain( 'edd-ct', false, $this->directory_path . '/languages/' );
	}

	/**
	 * Include file dependencies.
	 *
	 * @since 0.1
	 */
	public function includes() {
		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . '/includes/class-ga-parser.php' );
			require_once( $this->directory_path . '/includes/campaign-logger.php' );
			require_once( $this->directory_path . '/includes/payment-screen.php' );
			require_once( $this->directory_path . '/includes/email-tag.php' );
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
			echo '<p>' . sprintf( __( 'EDD Campaign Tracker requires Easy Digital Downloads 2.0 or greater and has been <a href="%s">deactivated</a>.', 'edd-ct' ), admin_url( 'plugins.php' ) ) . '</p>';
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
			return TRUE;
		} else {
			return FALSE;
		}
	}
}
new EDD_Campaign_Tracker;
?>
