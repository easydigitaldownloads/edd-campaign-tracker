<?php
/**
 * Helper class to modify payment screen.
 *
 * @since 1.0.0
 * @author Sandhills Development, LLC
 * @package EDD\Campaign Tracker
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

class EDDCT_Payment_Screen {

	/**
	 * Render metabox in Payment Screen.
	 *
	 * @since 1.0.0
	 * @param int     $payment_id (optional) Payment post ID.
	 */
	public static function render_metabox( $payment_id = 0 ) {
		self::do_meta_box( __( 'Campaign Information', 'edd-campaign-tracker' ), self::render_campaign_info( $payment_id ) );
	}

	/**
	 * Wrapper function for generating a metabox-style container on an EDD admin page.
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 * @param int     $payment_id Payment post ID.
	 * @return string             Campaign info
	 */
	public static function render_campaign_info( $payment_id ) {
		// If we don't have an actual order object, bail now
		if ( empty( $payment_id ) ) {
			return false;
		}

		$campaign_info = false;
		if ( function_exists( 'edd_get_order_meta' ) ) {
			$campaign_info = edd_get_order_meta( $payment_id, 'eddct_campaign', true );
		}
		if ( ! $campaign_info ) {
			$payment_meta = edd_get_payment_meta( $payment_id );
			if ( ! empty( $payment_meta['eddct_campaign'] ) ) {
				$campaign_info = $payment_meta['eddct_campaign'];
			}
			// In EDD 3.0, if this metadata exists, it was not migrated, so go ahead and migrate it now.
			if ( $campaign_info && function_exists( 'edd_add_order_meta' ) ) {
				edd_add_order_meta( $payment_id, 'eddct_campaign', $campaign_info );

				// Update the remaining payment meta, or delete if nothing is left.
				unset( $payment_meta['eddct_campaign'] );
				if ( empty( $payment_meta ) ) {
					edd_delete_order_meta( $payment_id, 'payment_meta' );
				} else {
					edd_update_order_meta( $payment_id, 'payment_meta', $payment_meta );
				}
			}
		}

		if ( ! $campaign_info ) {
			return __( 'No campaign information available.', 'edd-campaign-tracker' );
		}
		ob_start();
		?>
		<style type="text/css">.eddct-list { list-style: disc; margin-left: 24px; } </style>
		<ul class="eddct-list">
			<li><strong><?php esc_html_e( 'Campaign Source', 'edd-campaign-tracker' ); ?>:</strong> <code><?php echo esc_html( $campaign_info['source'] ); ?></code></li>
			<li><strong><?php esc_html_e( 'Campaign Medium', 'edd-campaign-tracker' ); ?>:</strong> <code><?php echo esc_html( $campaign_info['medium'] ); ?></code></li>
			<li><strong><?php esc_html_e( 'Campaign Name', 'edd-campaign-tracker' ); ?>:</strong> <code><?php echo esc_html( $campaign_info['name'] ); ?></code></li>
			<?php
			// The campaign term is optional so may be empty.
			if ( ! empty( $campaign_info['term'] ) ) {
				?>
				<li><strong><?php esc_html_e( 'Campaign Term', 'edd-campaign-tracker' ); ?>:</strong> <code><?php echo esc_html( $campaign_info['term'] ); ?></code></li>
				<?php
			}
			// The campaign content is optional so may be empty.
			if ( ! empty( $campaign_info['content'] ) ) {
				?>
				<li><strong><?php esc_html_e( 'Campaign Content', 'edd-campaign-tracker' ); ?>:</strong> <code><?php echo esc_html( $campaign_info['content'] ); ?></code></li>
				<?php
			}
			?>
		</ul>
		<?php
		return ob_get_clean();
	}

	/**
	 * Make campaign column sortable.
	 *
	 * @static
	 * @since  1.0.1
	 * @param array $columns List of sortable columns
	 * @return array         Modified list of sortable columns
	 */
	public static function make_campaign_column_sortable( $columns ) {
		$columns['campaign'] = array( 'campaign', false );
		return $columns;
	}

	/**
	 * Add a new column to the payment table.
	 *
	 * @static
	 * @since  1.0.0
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
	 * @since  1.0.0
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
				$display_name = $campaign_info['name'];
				$value = '<a href="' . esc_url( add_query_arg( array( 'campaign' => urlencode( $display_name ), 'paged' => false ) ) ) . '">' . $display_name . '</a>';
			} else {
				$value = __( 'N/A', 'edd-campaign-tracker' );
			}
		}
		return $value;
	}

	/**
	 * Restrict the list of payments returned based on Campaign selected.
	 *
	 * @static
	 * @since  1.0.0
	 */
	public static function pre_get_payments( $payment_query ) {
		if ( isset( $_GET['campaign'] ) ) {
			$campaign = sanitize_text_field( urldecode( $_GET['campaign'] ) );
			$payment_query->__set( 'meta_query', array(
				'key'   => '_eddct_campaign_name',
				'value' => $campaign,
			) );
		}
	}

	/**
	 * Modify the `join` used for calculating the payment count.
	 *
	 * @static
	 * @since 1.0.0
	 */
	public static function count_payments_join( $join ) {
		global $wpdb;

		if ( isset( $_GET['campaign'] ) ) {
			$campaign = urldecode( sanitize_text_field( $_GET['campaign'] ) );
			if ( '' != $campaign ) {
				$join = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";
			}
		}

		return $join;
	}

	/**
	 * Modify the `where` used for calculating the payment count.
	 *
	 * @static
	 * @since 1.0.0
	 */
	public static function count_payments_where( $where ) {
		global $wpdb;

		if ( isset( $_GET['campaign'] ) ) {
			$campaign = urldecode( sanitize_text_field( $_GET['campaign'] ) );
			if ( '' != $campaign ) {
				$where .= "
				AND m.meta_key = '_eddct_campaign_name'
				AND m.meta_value = '{$campaign}'";
			}
		}

		return $where;
	}
}

add_action( 'edd_view_order_details_main_after', array( 'EDDCT_Payment_Screen', 'render_metabox' ) );
add_filter( 'edd_payments_table_columns', array( 'EDDCT_Payment_Screen', 'add_campaign_column' ) );
add_filter( 'edd_payments_table_sortable_columns', array( 'EDDCT_Payment_Screen', 'make_campaign_column_sortable' ) );
add_filter( 'edd_payments_table_column', array( 'EDDCT_Payment_Screen', 'render_campaign_column' ), 10, 3 );
add_action( 'edd_pre_get_payments', array( 'EDDCT_Payment_Screen', 'pre_get_payments' ) );

// `edd_count_payments_join` was introduced only in https://github.com/easydigitaldownloads/Easy-Digital-Downloads/pull/3352
// If the `where` clause is modified without `join` clause then it will result in a SQL error.
if ( version_compare( EDD_VERSION, '2.3.9', '>=') ) {
	add_filter( 'edd_count_payments_where', array( 'EDDCT_Payment_Screen', 'count_payments_where' ) );
	add_filter( 'edd_count_payments_join', array( 'EDDCT_Payment_Screen', 'count_payments_join' ) );
}
