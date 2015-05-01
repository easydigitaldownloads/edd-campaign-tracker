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
		$campaign_data = parse_str( strtr( $campaign_data, '|', '&' ) );

		$this->campaign_source  = isset( $utmcsr ) ? $utmcsr : null;
		$this->campaign_name    = isset( $utmccn ) ? $utmccn : null;
		$this->campaign_medium  = isset( $utmcmd ) ? $utmcmd : null;
		$this->campaign_term    = isset( $utmctr ) ? $utmctr : null;
		$this->campaign_content = isset( $utmcct ) ? $utmcct : null;

		// adwords
		if ( isset( $utmgclid ) ) {
			$this->campaign_source  = 'google';
			$this->campaign_name    = '';
			$this->campaign_medium  = 'cpc';
			$this->campaign_content = '';
			$this->campaign_term    = $utmctr;
		}
	}
}
