<?php
/**
 * Helper for handling email tags.
 *
 * @since 1.0.0
 * @author Sandhills Development, LLC
 * @package EDD\CampaignTracker
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

class EDDCT_Email_Tag {

	/**
	 * Register custom email tag.
	 *
	 * @since 1.0.0
	 */
	public static function register_email_tags() {
		edd_add_email_tag( 'campaign_info', __( 'Display Google Analytics Campaign info for this transaction', 'edd-campaign-tracker' ), array( __CLASS__, 'email_tag_campaign_info' ) );
	}

	/**
	 * Callback for `campaign_info` email tag
	 *
	 * @since  1.0.0
	 * @param int     $payment_id (optional) Payment post ID.
	 * @return string             Content for `campaign_info` tag
	 */
	public static function email_tag_campaign_info( $payment_id = 0 ) {
		$output = '<h3>' . __( 'Campaign Information', 'edd-campaign-tracker' ) . '</h3>';
		return $output . EDDCT_Payment_Screen::render_campaign_info( $payment_id );
	}
}

add_action( 'edd_add_email_tags', array( 'EDDCT_Email_Tag', 'register_email_tags' ) );
