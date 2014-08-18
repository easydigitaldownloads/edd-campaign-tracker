<?php
/**
 * Helper for handling email tags.
 *
 * @since 0.1
 */
class EDDCT_Email_Tag {
	/**
	 * Register custom email tag.
	 *
	 * @since 0.1
	 */
	public static function register_email_tags() {
		edd_add_email_tag( 'campaign_info', __( 'Display Google Analytics Campaign info for this transaction.', 'edd-ct' ), array( __CLASS__, 'email_tag_campaign_info' ) );
	}

    /**
     * Callback for `campaign_info` email tag
     *
     * @since  0.1
	 * @param  int    $payment_id Payment post ID.
     * @return string             Content for `campaign_info` tag
     */
    public static function email_tag_campaign_info( $payment_id = 0 ) {
        return EDDCT_Payment_Screen::render_campaign_info();
    }
}

add_action( 'edd_add_email_tags', array( EDDCT_Email_Tag, 'register_email_tags' ) );
?>
