<?php
/**
 * Campaing Reports.
 *
 * @since 1.0.0
 * @author Bulk WP <http://bulkwp.com>
 * @package EDD\Campaign Tracker
 */


defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class EDDCT_Reports {

	/**
	 * Use `factory()` method to create instance of this class.
	 * Don't create instances directly
	 *
	 * @since 1.0.0
	 *
	 * @see factory()
	 */
	public function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Make this class a "hybrid Singleton".
	 *
	 * @static
	 * @since 1.0.0
	 * @return unknown
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Setup Hooks.
	 *
	 * @since 1.0
	 */
	protected function setup_hooks() {
		add_filter( 'edd_report_views', array( $this, 'add_campaign_view' ) );
		add_action( 'edd_reports_view_campaign', array( $this, 'render_campaign_earnings' ) );

		add_action( 'edd_filter_campaign_reports', array( $this, 'parse_report_dates' ) );
	}

	/**
	 * Add Campaign to the views dropdown in reports page.
	 *
	 * @since  1.0.0
	 * @param  array $views List of views
	 * @return array        Filtered set of views
	 */
	public function add_campaign_view( $views ) {
		$views['campaign'] = __( 'Campaigns', 'edd-campaign-tracker' );
		return $views;
	}

	/**
	 * Render the campaign graph.
	 *
	 * @since 1.0.0
	 */
	public function render_campaign_earnings() {
		if ( ! current_user_can( 'view_shop_reports' ) ) {
			return;
		}
?>
		<div class="tablenav top">
			<div class="alignleft actions"><?php edd_report_views(); ?></div>
		</div>
<?php
		$this->render_campaign_graph();
	}

	/**
	 * Show report graphs.
	 * Based on edd_reports_graph
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function render_campaign_graph() {
		$campaign = isset( $_GET['campaign' ] ) ? sanitize_text_field( $_GET['campaign'] ) : null;

		// Retrieve the queried dates
		$dates = edd_get_report_dates();

		// Determine graph options
		switch ( $dates['range'] ) {
			case 'today' :
			case 'yesterday' :
				$day_by_day	= true;
				break;
			case 'last_year' :
			case 'this_year' :
			case 'last_quarter' :
			case 'this_quarter' :
				$day_by_day = false;
				break;
			case 'other' :
				if ( $dates['m_end'] - $dates['m_start'] >= 2 || $dates['year_end'] > $dates['year'] && ( '12' != $dates['m_start'] && '1' != $dates['m_end'] ) ) {
					$day_by_day = false;
				} else {
					$day_by_day = true;
				}
				break;
			default:
				$day_by_day = true;
				break;
		}

		$earnings_totals = 0.00; // Total earnings for time period shown
		$sales_totals    = 0;    // Total sales for time period shown

		$earnings_data = array();
		$sales_data    = array();

		if ( 'today' == $dates['range'] || 'yesterday' == $dates['range'] ) {
			// Hour by hour
			$hour  = 1;
			$month = $dates['m_start'];
			while ( $hour <= 23 ) {
				$sales    = $this->get_sales_by_date( $campaign, $dates['day'], $month, $dates['year'], $hour );
				$earnings = $this->get_earnings_by_date( $campaign, $dates['day'], $month, $dates['year'], $hour );

				$sales_totals += $sales;
				$earnings_totals += $earnings;

				$date            = mktime( $hour, 0, 0, $month, $dates['day'], $dates['year'] ) * 1000;
				$sales_data[]    = array( $date, $sales );
				$earnings_data[] = array( $date, $earnings );

				$hour++;
			}
		} elseif ( 'this_week' == $dates['range'] || 'last_week' == $dates['range'] ) {

			// Day by day
			$day     = $dates['day'];
			$day_end = $dates['day_end'];
			$month   = $dates['m_start'];
			while ( $day <= $day_end ) {
				$sales = $this->get_sales_by_date( $campaign, $day, $month, $dates['year'] );
				$sales_totals += $sales;

				$earnings = $this->get_earnings_by_date( $campaign, $day, $month, $dates['year'] );
				$earnings_totals += $earnings;

				$date = mktime( 0, 0, 0, $month, $day, $dates['year'] ) * 1000;
				$sales_data[] = array( $date, $sales );
				$earnings_data[] = array( $date, $earnings );
				$day++;
			}
		} else {

			$y = $dates['year'];
			while ( $y <= $dates['year_end'] ) {
				if ( $dates['year'] == $dates['year_end'] ) {
					$month_start = $dates['m_start'];
					$month_end   = $dates['m_end'];
				} elseif ( $y == $dates['year'] ) {
					$month_start = $dates['m_start'];
					$month_end   = 12;
				} elseif ( $y == $dates['year_end'] ) {
					$month_start = 1;
					$month_end   = $dates['m_end'];
				} else {
					$month_start = 1;
					$month_end   = 12;
				}

				$i = $month_start;
				while ( $i <= $month_end ) {
					if ( $day_by_day ) {
						if ( $i == $month_end ) {
							$num_of_days = $dates['day_end'];
						} else {
							$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );
						}

						$d = $dates['day'];

						while ( $d <= $num_of_days ) {

							$sales = $this->get_sales_by_date( $campaign, $d, $i, $y );
							$sales_totals += $sales;

							$earnings = $this->get_earnings_by_date( $campaign, $d, $i, $y );
							$earnings_totals += $earnings;

							$date = mktime( 0, 0, 0, $i, $d, $y ) * 1000;
							$sales_data[] = array( $date, $sales );
							$earnings_data[] = array( $date, $earnings );
							$d++;
						}
					} else {

						$sales = $this->get_sales_by_date( $campaign, null, $i, $y );
						$sales_totals += $sales;

						$earnings = $this->get_earnings_by_date( $campaign, null, $i, $y );
						$earnings_totals += $earnings;

						if ( $i == $month_end ) {
							$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );
						} else {
							$num_of_days = 1;
						}

						$date = mktime( 0, 0, 0, $i, $num_of_days, $y ) * 1000;
						$sales_data[] = array( $date, $sales );
						$earnings_data[] = array( $date, $earnings );
					}

					$i++;
				}

				$y++;
			}
		}

		$data = array(
			__( 'Earnings', 'edd-campaign-tracker' ) => $earnings_data,
			__( 'Sales', 'edd-campaign-tracker' )    => $sales_data,
		);

		// start our own output buffer
		ob_start();
?>
		<div id="edd-dashboard-widgets-wrap">
			<div class="metabox-holder" style="padding-top: 0;">
				<div class="postbox">
					<h3><span><?php _e( 'Earnings Over Time based on Campaign', 'edd-campaign-tracker' ); ?></span></h3>

					<div class="inside">
<?php
		$this->render_reports_graph_controls();
		$graph = new EDD_Graph( $data );
		$graph->set( 'x_mode', 'time' );
		$graph->set( 'multiple_y_axes', true );
		$graph->display();

		if ( 'this_month' == $dates['range'] ) {
			$estimated = edd_estimated_monthly_stats();
		}
?>
						<p class="edd_graph_totals"><strong><?php _e( 'Total earnings for period shown: ', 'edd-campaign-tracker' ); echo edd_currency_filter( edd_format_amount( $earnings_totals ) ); ?></strong></p>
						<p class="edd_graph_totals"><strong><?php _e( 'Total sales for period shown: ', 'edd-campaign-tracker' ); echo edd_format_amount( $sales_totals, false ); ?></strong></p>

						<?php if ( 'this_month' == $dates['range'] ) : ?>
							<p class="edd_graph_totals"><strong><?php _e( 'Estimated monthly earnings: ', 'edd-campaign-tracker' ); echo edd_currency_filter( edd_format_amount( $estimated['earnings'] ) ); ?></strong></p>
							<p class="edd_graph_totals"><strong><?php _e( 'Estimated monthly sales: ', 'edd-campaign-tracker' ); echo edd_format_amount( $estimated['sales'], false ); ?></strong></p>
						<?php endif; ?>

						<?php do_action( 'edd_reports_graph_additional_stats' ); ?>

					</div>
				</div>
			</div>
		</div>
<?php
		// get output buffer contents and end our own buffer
		$output = ob_get_contents();
		ob_end_clean();

		echo $output;
	}

	/**
	 * Render reports graph controls.
	 * Based on edd_reports_graph_controls
	 *
	 * @since 1.0.0
	 */
	protected function render_reports_graph_controls() {
		$date_options = apply_filters( 'edd_report_date_options', array(
			'today' 	    => __( 'Today'        , 'edd-campaign-tracker' ),
			'yesterday'     => __( 'Yesterday'    , 'edd-campaign-tracker' ),
			'this_week' 	=> __( 'This Week'    , 'edd-campaign-tracker' ),
			'last_week' 	=> __( 'Last Week'    , 'edd-campaign-tracker' ),
			'this_month' 	=> __( 'This Month'   , 'edd-campaign-tracker' ),
			'last_month' 	=> __( 'Last Month'   , 'edd-campaign-tracker' ),
			'this_quarter'	=> __( 'This Quarter' , 'edd-campaign-tracker' ),
			'last_quarter'	=> __( 'Last Quarter' , 'edd-campaign-tracker' ),
			'this_year'		=> __( 'This Year'    , 'edd-campaign-tracker' ),
			'last_year'		=> __( 'Last Year'    , 'edd-campaign-tracker' ),
			'other'			=> __( 'Custom'       , 'edd-campaign-tracker' ),
		) );

		$dates            = edd_get_report_dates();
		$display          = $dates['range'] == 'other' ? '' : 'style="display:none;"';
		$view             = edd_get_reporting_view();
		$current_campaign = isset( $_GET['campaign' ] ) ? sanitize_text_field( $_GET['campaign'] ) : null;

		if ( empty( $dates['day_end'] ) ) {
			$dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, date( 'n' ), date( 'Y' ) );
		}

		?>
		<form id="edd-graphs-filter-2" method="get">
			<div class="tablenav top">
				<div class="alignleft actions">

					<input type="hidden" name="post_type" value="download">
					<input type="hidden" name="page" value="edd-reports">
					<input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>">
					<input type="hidden" name="view2" value="<?php echo esc_attr( $view ); ?>">

					<?php if ( isset( $_GET['download-id'] ) ) : ?>
						<input type="hidden" name="download-id" value="<?php echo absint( $_GET['download-id'] ); ?>"/>
					<?php endif; ?>

					<select id="edd-graphs-date-options" name="range">
						<?php foreach ( $date_options as $key => $option ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $dates['range'] ); ?>><?php echo esc_html( $option ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php $campaigns = $this->get_campaign_list(); ?>
					<select id="eddct-campaign-name" name="campaign">
						<option value="" selected><?php _e( 'All Campaigns', 'edd-campaign-tracker' ); ?></option>
						<?php foreach ( $campaigns as $campaign ) {
							if ( null != $campaign ) {
								printf( '<option value="%s" %s>%s</option>', esc_attr( $campaign ), selected( $current_campaign, $campaign, false ), $campaign );
							}
						} ?>
					</select>
					<div id="edd-date-range-options" <?php echo $display; ?>>
						<span><?php _e( 'From', 'edd-campaign-tracker' ); ?>&nbsp;</span>
						<select id="edd-graphs-month-start" name="m_start">
							<?php for ( $i = 1; $i <= 12; $i++ ) : ?>
								<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['m_start'] ); ?>><?php echo edd_month_num_to_name( $i ); ?></option>
							<?php endfor; ?>
						</select>
						<select id="edd-graphs-day-start" name="day">
							<?php for ( $i = 1; $i <= 31; $i++ ) : ?>
								<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['day'] ); ?>><?php echo $i; ?></option>
							<?php endfor; ?>
						</select>
						<select id="edd-graphs-year-start" name="year">
							<?php for ( $i = 2007; $i <= date( 'Y' ); $i++ ) : ?>
								<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['year'] ); ?>><?php echo $i; ?></option>
							<?php endfor; ?>
						</select>
						<span><?php _e( 'To', 'edd-campaign-tracker' ); ?>&nbsp;</span>
						<select id="edd-graphs-month-end" name="m_end">
							<?php for ( $i = 1; $i <= 12; $i++ ) : ?>
								<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['m_end'] ); ?>><?php echo edd_month_num_to_name( $i ); ?></option>
							<?php endfor; ?>
						</select>
						<select id="edd-graphs-day-end" name="day_end">
							<?php for ( $i = 1; $i <= 31; $i++ ) : ?>
								<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['day_end'] ); ?>><?php echo $i; ?></option>
							<?php endfor; ?>
						</select>
						<select id="edd-graphs-year-end" name="year_end">
							<?php for ( $i = 2007; $i <= date( 'Y' ); $i++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['year_end'] ); ?>><?php echo $i; ?></option>
							<?php endfor; ?>
						</select>
					</div>

					<input type="hidden" name="edd_action" value="filter_campaign_reports" />
					<input type="submit" class="button-secondary" value="<?php _e( 'Filter', 'edd-campaign-tracker' ); ?>"/>
				</div>
			</div>
		</form>
		<?php
	}

	/**
	 * Get the list of unique Campaigns in sorted order.
	 *
	 * @since 1.0.0
	 * @return array Sorted list of campaigns
	 */
	protected function get_campaign_list() {
		global $wpdb;

		$campaigns = $wpdb->get_col( $wpdb->prepare( "
			SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '%s'
			AND p.post_status = '%s'
			AND p.post_type = '%s'
		", '_eddct_campaign_name', 'publish', 'edd_payment' ) );

		asort( $campaigns );
		return $campaigns;
	}

	/**
	 * Grabs all of the selected date info and then redirects appropriately.
	 * Based on edd_parse_report_dates
	 *
	 * @since 1.0.0
	 *
	 * @param $data
	 */
	public function parse_report_dates( $data ) {
		$dates = edd_get_report_dates();
		$query_args = $dates;

		$view = edd_get_reporting_view();
		$query_args['view'] = $view;

		$id = isset( $_GET['download-id'] ) ? absint( $_GET['download-id'] ) : null;
		$query_args['id'] = $id;

		$campaign = isset( $_GET['campaign'] ) ? sanitize_text_field( $_GET['campaign'] ) : null;

		wp_redirect( add_query_arg( $query_args, admin_url( 'edit.php?post_type=download&page=edd-reports&campaign=' . $campaign ) ) );
		edd_die();
	}

	/**
	 * Get Earnings By Date.
	 * Based on edd_get_earnings_by_date.
	 *
	 * TODO: Use `EDD_Payment_Stats` get_earnings() function.
	 *
	 * @since  1.0.0
	 * @param  string $campaign  Campaign name
	 * @param  int    $day       Day number
	 * @param  int    $month_num Month number
	 * @param  int    $year      Year
	 * @param  int    $hour      Hour
	 * @return int    $earnings  Earnings
	 */
	protected function get_earnings_by_date( $campaign = null, $day = null, $month_num, $year = null, $hour = null ) {
		global $wpdb;

		$args = array(
			'post_type'              => 'edd_payment',
			'nopaging'               => true,
			'year'                   => $year,
			'monthnum'               => $month_num,
			'post_status'            => array( 'publish', 'revoked' ),
			'fields'                 => 'ids',
			'update_post_term_cache' => false,
		);

		if ( ! empty( $campaign ) ) {
			$args['meta_key']     = '_eddct_campaign_name';
			$args['meta_value']   = $campaign;
		} else {
			$args['meta_key']     = '_eddct_campaign_name';
			$args['meta_compare'] = 'EXISTS';
		}

		if ( ! empty( $day ) )
			$args['day'] = $day;

		if ( ! empty( $hour ) )
			$args['hour'] = $hour;

		$args     = apply_filters( 'eddct_get_earnings_by_date_args', $args );
		$key      = md5( serialize( $args ) );
		$earnings = get_transient( $key );

		if ( false === $earnings ) {
			$sales = get_posts( $args );
			$earnings = 0;
			if ( $sales ) {
				$sales = implode( ',', $sales );
				$earnings += $wpdb->get_var( "SELECT SUM(meta_value) FROM $wpdb->postmeta WHERE meta_key = '_edd_payment_total' AND post_id IN({$sales})" );

			}
			// Cache the results for one hour
			set_transient( $key, $earnings, 60*60 );
		}

		return round( $earnings, 2 );
	}

	/**
	 * Get Sales By Date.
	 * Based on edd_get_sales_by_date.
	 *
	 * TODO: Use `EDD_Payment_Stats` get_sales() function.
	 *
	 * @since  1.0.0
	 * @param  string $campaign  Campaign name
	 * @param  int    $day       Day number
	 * @param  int    $month_num Month number
	 * @param  int    $year      Year
	 * @param  int    $hour      Hour
	 * @return int    $count     Sales
	 */
	protected function get_sales_by_date( $campaign = null, $day = null, $month_num = null, $year = null, $hour = null ) {
		$args = array(
			'post_type'              => 'edd_payment',
			'nopaging'               => true,
			'year'                   => $year,
			'fields'                 => 'ids',
			'post_status'            => array( 'publish', 'revoked' ),
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( ! empty( $campaign ) ) {
			$args['meta_key']     = '_eddct_campaign_name';
			$args['meta_value']   = $campaign;
		} else {
			$args['meta_key']     = '_eddct_campaign_name';
			$args['meta_compare'] = 'EXISTS';
		}

		if ( ! empty( $month_num ) )
			$args['monthnum'] = $month_num;

		if ( ! empty( $day ) )
			$args['day'] = $day;

		if ( ! empty( $hour ) )
			$args['hour'] = $hour;

		$args = apply_filters( 'eddct_get_sales_by_date_args', $args  );

		$key   = md5( serialize( $args ) );
		$count = get_transient( $key, 'edd' );

		if ( false === $count ) {
			$sales = new WP_Query( $args );
			$count = (int) $sales->post_count;
			// Cache the results for one hour
			set_transient( $key, $count, 60*60 );
		}

		return $count;
	}
}

EDDCT_Reports::factory();
