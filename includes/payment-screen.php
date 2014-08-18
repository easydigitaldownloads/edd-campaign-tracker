<?php
/**
 * Helper class to modify payment screen.
 *
 * @since 0.1
 */
class EDDCT_Payment_Screen {
    /**
     * Render metabox in Payment Screen.
     *
     * @since 0.1
	 * @param int $payment_id Payment post ID.
     */
    public static function render_metabox( $payment_id = 0 ) {
        self::do_meta_box( __( 'Campaign Information', 'edd-ct' ), self::show_campaign_info( $payment_id ) );
    }

    /**
     * Wrapper function for generating a metabox-style container on an EDD admin page.
     *
     * @since  0.1
     * @access private
     * @param  string $title    Metabox title.
     * @param  string $contents Metabox contents.
     * @return string           HTML markup.
     */
    private static function do_meta_box( $title = '', $contents = '' ) {
?>
        <div class="postbox-container">
            <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                <div id="edd-order-data" class="postbox">
                    <h3 class="hndle"><?php echo $title ?></h3>
                    <div class="inside">
                        <?php echo $contents; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Render Campaign Info.
     *
     * @since 0.1
	 * @param  int    $payment_id Payment post ID.
     * @return string             Campaign info
     */
    public static function render_campaign_info( $payment_id ) {
        // If we don't have an actual order object, bail now
        if ( empty( $payment_id ) ) {
            return FALSE;
        }

        $payment_meta = edd_get_payment_meta( $payment_id );
        if ( isset( $payment_meta['eddct_campaign'] ) ) {
            $campaign_info = $payment_meta['user_history'];
            ob_start();
?>
        <h3><?php _e( 'Campaign Details', 'edd-ct');?></h3>
        <table>
            <tr>
                <th><?php __( 'Campaign Name', 'edd-ct' );?></th>
                <td><?php echo empty( $campaign_info['name']) ? __( 'N/A' , 'edd-ct') : $campaign_info['name']; ?></td>
            </tr>
            <tr>
                <th><?php __( 'Campaign Source', 'edd-ct' );?></th>
                <td><?php echo empty( $campaign_info['source']) ? __( 'N/A' , 'edd-ct') : $campaign_info['source']; ?></td>
            </tr>
            <tr>
                <th><?php __( 'Campaign Medium', 'edd-ct' );?></th>
                <td><?php echo empty( $campaign_info['medium']) ? __( 'N/A' , 'edd-ct') : $campaign_info['medium']; ?></td>
            </tr>
            <tr>
                <th><?php __( 'Campaign Term', 'edd-ct' );?></th>
                <td><?php echo empty( $campaign_info['term']) ? __( 'N/A' , 'edd-ct') : $campaign_info['term']; ?></td>
            </tr>
            <tr>
                <th><?php __( 'Campaign Content', 'edd-ct' );?></th>
                <td><?php echo empty( $campaign_info['content']) ? __( 'N/A' , 'edd-ct') : $campaign_info['content']; ?></td>
            </tr>
        </table>
<?php
            $output = ob_get_clean();
        } else {
            $output = __( 'No Campaign Information found', 'edd-ct' );
        }
        return $output;
    }
}

add_action( 'edd_view_order_details_main_after', array( EDDCT_Payment_Screen, 'render_metabox' ) );
?>
