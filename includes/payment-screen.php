<?php
/**
 * Helper class to modify payment screen.
 *
 * @since 0.1
 * @author Bulk WP <http://bulkwp.com>
 * @package EDD\Campaign Tracker
 */


class EDDCT_Payment_Screen {

	/**
	 * Render metabox in Payment Screen.
	 *
	 * @since 0.1
	 * @param int     $payment_id (optional) Payment post ID.
	 */
	public static function render_metabox( $payment_id = 0 ) {
		self::do_meta_box( __( 'Campaign Information', 'edd-campaign-tracker' ), self::render_campaign_info( $payment_id ) );
	}

	/**
	 * Wrapper function for generating a metabox-style container on an EDD admin page.
	 *
	 * @since  0.1
	 * @access private
	 * @param string  $title    (optional) Metabox title.
	 * @param string  $contents (optional) Metabox contents.
	 * @return string           HTML markup.
	 */
	private static function do_meta_box( $title = '', $contents = '' ) {
?>
        <div id="edd-order-data" class="postbox">
            <h3 class="hndle"><?php echo $title ?></h3>
            <div class="inside">
                <?php echo $contents; ?>
            </div>
        </div>
<?php
	}

	/**
	 * Render Campaign Info.
	 *
	 * @since  0.1
	 * @param int     $payment_id Payment post ID.
	 * @return string             Campaign info
	 */
	public static function render_campaign_info( $payment_id ) {
		// If we don't have an actual order object, bail now
		if ( empty( $payment_id ) ) {
			return false;
		}

		$payment_meta = edd_get_payment_meta( $payment_id );
		if ( isset( $payment_meta['eddct_campaign'] ) ) {
			$campaign_info = $payment_meta['eddct_campaign'];
			ob_start();
?>
        <table style="width: 100%; border:1px solid #eee;" border="0">
            <tr><th style="background:#333; color:#fff; text-align:left; padding:10px;"><?php _e( 'Campaign Detail', 'edd-campaign-tracker' ); ?></th><th style="background:#333; color:#fff; text-align:left; padding:10px;"><?php _e( 'Value', 'edd-campaign-tracker' ); ?></th></tr>
            <tr><td style="text-align:left; padding:10px;"><?php _e( 'Campaign Name', 'edd-campaign-tracker' );?></td><td style="text-align:left; padding:10px;"><?php echo empty( $campaign_info['name'] ) ? __( 'N/A' , 'edd-campaign-tracker' ) : $campaign_info['name']; ?></td></tr>
            <tr style="background: #f7f7f7;"><td style="text-align:left; padding:10px;"><?php _e( 'Campaign Source', 'edd-campaign-tracker' );?></td><td style="text-align:left; padding:10px;"><?php echo empty( $campaign_info['source'] ) ? __( 'N/A' , 'edd-campaign-tracker' ) : $campaign_info['source']; ?></td></tr>
            <tr><td style="text-align:left; padding:10px;"><?php _e( 'Campaign Medium', 'edd-campaign-tracker' );?></td><td style="text-align:left; padding:10px;"><?php echo empty( $campaign_info['medium'] ) ? __( 'N/A' , 'edd-campaign-tracker' ) : $campaign_info['medium']; ?></td></tr>
            <tr style="background: #f7f7f7;"><td style="text-align:left; padding:10px;"><?php _e( 'Campaign Term', 'edd-campaign-tracker' );?></td><td style="text-align:left; padding:10px;"><?php echo empty( $campaign_info['term'] ) ? __( 'N/A' , 'edd-campaign-tracker' ) : $campaign_info['term']; ?></td></tr>
            <tr><td style="text-align:left; padding:10px;"><?php _e( 'Campaign Content', 'edd-campaign-tracker' );?></td><td style="text-align:left; padding:10px;"><?php echo empty( $campaign_info['content'] ) ? __( 'N/A' , 'edd-campaign-tracker' ) : $campaign_info['content']; ?></td></tr>
        </table>
<?php
			$output = ob_get_clean();
		} else {
			$output = __( 'No campaign information available', 'edd-campaign-tracker' );
		}
		return $output;
	}

	/**
	 * Add a new column to the payment table.
	 *
	 * @static
	 * @since  0.1
	 * @param array   $columns List of columns
	 * @return array          Modified list of columns
	 */
	public static function add_campaign_column( $columns ) {
		$columns['campaign'] = __( 'Campaign', 'edd-campaign-tracker' );
		return $columns;
	}

	/**
	 * Render campaign column in payment table.
	 *
	 * @static
	 * @since  0.1
	 * @param string  $value       Value for the column
	 * @param int     $payment_id  Payment ID
	 * @param string  $column_name Name of the column
	 * @return string              Value for the column
	 */
	public static function render_campaign_column( $value, $payment_id, $column_name ) {
		if ( 'campaign' == $column_name ) {
			$payment_meta = edd_get_payment_meta( $payment_id );
			if ( isset( $payment_meta['eddct_campaign'] ) ) {
				$campaign_info = $payment_meta['eddct_campaign'];
				$value = $campaign_info['name'];
			} else {
				$value = __( 'N/A', 'edd-campaign-tracker' );
			}
		}
		return $value;
	}
}

add_action( 'edd_view_order_details_main_after', array( 'EDDCT_Payment_Screen', 'render_metabox' ) );
add_filter( 'edd_payments_table_columns', array( 'EDDCT_Payment_Screen', 'add_campaign_column' ) );
add_filter( 'edd_payments_table_column', array( 'EDDCT_Payment_Screen', 'render_campaign_column' ), 10, 3 );
?>
