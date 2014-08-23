<?php
/**
 * Helper class to log campaign
 *
 * @since 0.1
 */
class EDDCT_Campaign_Logger {
    /**
     * Log Campaign data.
     *
     * @since  0.1
     * @param  array $payment_meta Payment Meta Information
     * @return array               Modified Payment Meta Information
     */
    public static function log_campaign( $payment_meta ) {
        $ga_parser = new GA_Parser();

        if ( $ga_parser->cookie_present() ) {
            $campaign_info            = array();

            $campaign_info['source']  = trim( $ga_parser->campaign_source );
            $campaign_info['name']    = trim( $ga_parser->campaign_name );
            $campaign_info['medium']  = trim( $ga_parser->campaign_medium );
            $campaign_info['term']    = trim( $ga_parser->campaign_term );
            $campaign_info['content'] = trim( $ga_parser->campaign_content );
            $payment_meta['eddct_campaign'] = $campaign_info;
        }
        return $payment_meta;
    }
}

add_action( 'edd_payment_meta', array( 'EDDCT_Campaign_Logger', 'log_campaign' ) );
?>
