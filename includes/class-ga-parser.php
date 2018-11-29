<?php
/**
 * Parse Google Analytics Cookie.
 *
 * Based on GA_Parse from https://github.com/joaolcorreia/Google-Analytics-PHP-cookie-parser
 *
 * @license LGPL
 * @since   1.0.0
 * @author  Bulk WP
 * @package EDD\Campaign Tracker
 */


class GA_Parser {
	var $cookie_present = false;

	var $campaign_source;      // Campaign Source
	var $campaign_name;        // Campaign Name
	var $campaign_medium;      // Campaign Medium
	var $campaign_content;     // Campaign Content
	var $campaign_term;        // Campaign Term

	/**
	 * Setup parser.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( isset( $_COOKIE['__utmz'] ) ) {
			$this->parse_cookie();
			$this->cookie_present = true;
		}
	}

	/**
	 * Returns whether the cookie was parsed.
	 *
	 * @since 1.0.0
	 * @return bool True if parsed, False otherwise
	 */
	public function cookie_present() {
		return $this->cookie_present;
	}

	/**
	 * Parse Cookie.
	 *
	 * @since 1.0.0
	 */
	private function parse_cookie() {
		// Parse __utmz cookie
		list( $domain_hash, $timestamp, $session_number, $campaign_numer, $campaign_data ) = preg_split( '[\.]', $_COOKIE['__utmz'], 5 );

		// Parse the campaign data
		parse_str( strtr( $campaign_data, '|', '&' ), $campaign_data );

		$this->campaign_source  = isset( $campaign_data['utmcsr'] ) ? $campaign_data['utmcsr'] : null;
		$this->campaign_name    = isset( $campaign_data['utmccn'] ) ? $campaign_data['utmccn'] : null;
		$this->campaign_medium  = isset( $campaign_data['utmcmd'] ) ? $campaign_data['utmcmd'] : null;
		$this->campaign_term    = isset( $campaign_data['utmctr'] ) ? $campaign_data['utmctr'] : null;
		$this->campaign_content = isset( $campaign_data['utmcct'] ) ? $campaign_data['utmcct'] : null;

		// adwords
		if ( isset( $campaign_data['utmgclid'] ) ) {
			$this->campaign_source  = 'google';
			$this->campaign_name    = '';
			$this->campaign_medium  = 'cpc';
			$this->campaign_content = '';
			$this->campaign_term    = $campaign_data['utmctr'];
		}
	}
}
