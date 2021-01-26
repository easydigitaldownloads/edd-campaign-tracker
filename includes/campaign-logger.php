<?php
/**
 * Helper class to log campaign
 *
 * @since 1.0.0
 * @author Sandhills Development, LLC
 * @package EDD\CampaignTracker
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

class EDDCT_Campaign_Logger {

	/**
	 * Log Campaign data.
	 *
	 * @since  1.0.0
	 * @param array   $payment_meta Payment Meta Information
	 * @return array               Modified Payment Meta Information
	 */
	public static function log_campaign( $payment_meta ) {
		$ga_parser = new GA_Parser();
		$campaign_info = array();

		if ( $ga_parser->cookie_present() ) {
			$campaign_info['source']  = trim( $ga_parser->campaign_source );
			$campaign_info['name']    = trim( $ga_parser->campaign_name );
			$campaign_info['medium']  = trim( $ga_parser->campaign_medium );
			$campaign_info['term']    = trim( $ga_parser->campaign_term );
			$campaign_info['content'] = trim( $ga_parser->campaign_content );

			$payment_meta['eddct_campaign'] = $campaign_info;
		} else {
			$campaign_source   = EDD()->session->get( self::get_session_id( 'source' ) );
			$campaign_campaign = EDD()->session->get( self::get_session_id( 'campaign' ) );
			$campaign_medium   = EDD()->session->get( self::get_session_id( 'medium' ) );
			$campaign_term     = EDD()->session->get( self::get_session_id( 'term' ) );
			$campaign_content  = EDD()->session->get( self::get_session_id( 'content' ) );

			if ( ! empty( $campaign_source ) && ! empty( $campaign_campaign ) && ! empty( $campaign_medium ) ) {
				$campaign_info['source']  = trim( $campaign_source );
				$campaign_info['name']    = trim( $campaign_campaign );
				$campaign_info['medium']  = trim( $campaign_medium );
				$campaign_info['term']    = trim( $campaign_term );
				$campaign_info['content'] = trim( $campaign_content );

				$payment_meta['eddct_campaign'] = $campaign_info;
			}
		}
		return $payment_meta;
	}

	/**
	 * Log campaign name.
	 * We need to log campaign name separately since we need to filter payments based on the name.
	 *
	 * @param int $payment_id Payment id
	 * @param array $payment_data Payment Data
	 */
	public static function log_campaign_name( $payment_id, $payment_data ) {
		$payment_meta = edd_get_payment_meta( $payment_id );
		if ( array_key_exists( 'eddct_campaign', $payment_meta ) ) {
			$campaign_info = $payment_meta['eddct_campaign'];

			if ( isset( $campaign_info['name'] ) ) {
				update_post_meta( $payment_id, '_eddct_campaign_name', $campaign_info['name'] );
			}
		}
	}

	/**
	 * Store campaign information in EDD Session.
	 *
	 * @since 1.0.0
	 */
	public static function store_campaign() {
		$campaign_source   = isset( $_GET['utm_source'] ) ? $_GET['utm_source'] : '';
		$campaign_campaign = isset( $_GET['utm_campaign'] ) ? $_GET['utm_campaign'] : '';
		$campaign_medium   = isset( $_GET['utm_medium'] ) ? $_GET['utm_medium'] : '';
		$campaign_term     = isset( $_GET['utm_term'] ) ? $_GET['utm_term'] : '';
		$campaign_content  = isset( $_GET['utm_content'] ) ? $_GET['utm_content'] : '';

		if ( ! empty( $campaign_source ) && ! empty( $campaign_campaign ) && ! empty( $campaign_medium ) ) {
			EDD()->session->set( self::get_session_id( 'source' ), filter_var( $campaign_source , FILTER_SANITIZE_STRING ) );
			EDD()->session->set( self::get_session_id( 'campaign' ), filter_var( $campaign_campaign , FILTER_SANITIZE_STRING ) );
			EDD()->session->set( self::get_session_id( 'medium' ), filter_var( $campaign_medium , FILTER_SANITIZE_STRING ) );
			EDD()->session->set( self::get_session_id( 'term' ), filter_var( $campaign_term , FILTER_SANITIZE_STRING ) );
			EDD()->session->set( self::get_session_id( 'content' ), filter_var( $campaign_content , FILTER_SANITIZE_STRING ) );
		}
	}

	/**
	 * Returns the unique CT session keys for this EDD installation.
	 *
	 * @since 1.0.0
	 * @param string  $type (optional) Type of key
	 * @return string Key identifier for stored sessions
	 */
	protected static function get_session_id( $type = 'campaign' ) {
		return sprintf( 'edd_ct_%1$s_%2$s_id', substr( self::get_store_id(), 0, 10 ), $type );
	}

	/**
	 * Returns the store ID variable for use in the campaign tracking.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected static function get_store_id() {
		return md5( home_url() );
	}
}

add_action( 'init', array( 'EDDCT_Campaign_Logger', 'store_campaign' ) );
add_action( 'edd_payment_meta', array( 'EDDCT_Campaign_Logger', 'log_campaign' ) );
add_action( 'edd_insert_payment', array( 'EDDCT_Campaign_Logger', 'log_campaign_name' ), 10, 2 );
