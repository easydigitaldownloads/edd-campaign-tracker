<?php
require_once( 'class-ga-parser.php' );

$ga_parser = new GA_Parser();

if ( $ga_parser->cookie_present() ) {
    $campaign_info            = array();

    $campaign_info['source']  = trim( $ga_parser->campaign_source );
    $campaign_info['name']    = trim( $ga_parser->campaign_name );
    $campaign_info['medium']  = trim( $ga_parser->campaign_medium );
    $campaign_info['term']    = trim( $ga_parser->campaign_term );
    $campaign_info['content'] = trim( $ga_parser->campaign_content );

    print_r( $campaign_info );
} else {
    echo "Cookie not found";
}
?>
