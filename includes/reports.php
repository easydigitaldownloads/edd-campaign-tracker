<?php
/**
 * Campaign Reports.
 *
 * @since 1.0.0
 * @author Sandhills Development, LLC
 * @package EDD\Campaign Tracker
 */

use EDD\Reports\Data\Report_Registry;
use function EDD\Reports\get_filter_value;

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
	 * @return object Instance of the class
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
		if ( function_exists( 'edd_get_orders' ) ) {
			add_filter( 'edd_report_filters', array( $this, 'add_campaign_filter' ) );
			add_action( 'edd_reports_init', array( $this, 'register_reports' ) );
		} else {
			add_filter( 'edd_report_views', array( $this, 'add_campaign_view' ) );
			add_action( 'edd_reports_view_campaign', array( $this, 'render_campaign_earnings' ) );
			add_action( 'edd_filter_campaign_reports', array( $this, 'parse_report_dates' ) );
		}
	}

	/**
	 * Adds a new report filter for "Campaign".
	 *
	 * @param array $filters
	 *
	 * @since 1.0.1
	 * @return array
	 */
	public function add_campaign_filter( $filters ) {
		$filters['campaign_tracker'] = array(
			'label'            => __( 'Campaign', 'edd-campaign-tracker' ),
			'display_callback' => array( $this, 'display_campaign_filter' )
		);

		return $filters;
	}

	/**
	 * Displays the campaign filter.
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function display_campaign_filter() {
		$campaigns = $this->get_campaign_list();

		if ( empty( $campaigns ) ) {
			return;
		}

		$campaigns = array_combine( $campaigns, $campaigns );
		natsort( $campaigns );
		?>
		<span class="edd-graph-filter-options graph-option-section">
			<label for="edd-campaign-tracker-filter" class="screen-reader-text">
				<?php esc_html_e( 'Filter by campaign', 'edd-campaign-tracker' ); ?>
			</label>
			<?php
			echo EDD()->html->select( array(
				'options'          => $campaigns,
				'name'             => 'campaign_tracker',
				'id'               => 'edd-campaign-tracker-filter',
				'selected'         => get_filter_value( 'campaign_tracker' ),
				'show_option_all'  => __( 'All Campaigns', 'edd-campaign-tracker' ),
				'show_option_none' => false,
				'chosen'           => true
			) );
			?>
		</span>
		<?php
	}

	/**
	 * Registers reports in EDD 3.0+
	 *
	 * @param Report_Registry $reports
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function register_reports( $reports ) {
		try {
			$reports->add_report( 'campaign_tracker', array(
				'label'     => __( 'Campaigns', 'edd-campaign-tracker' ),
				'icon'      => 'share',
				'priority'  => 50,
				'filters'   => array( 'dates', 'campaign_tracker' ),
				'endpoints' => array(
					'tiles' => array(
						'campaign_tracker_earnings',
						'campaign_tracker_sales'
					),
					'charts' => array(
						'campaign_tracker_earnings_chart'
					)
				)
			) );

			$reports->register_endpoint( 'campaign_tracker_earnings', array(
				'label' => __( 'Earnings', 'edd-campaign-tracker' ),
				'views' => array(
					'tile' => array(
						'data_callback' => array( $this, 'earnings_callback' ),
						'display_args'  => array(
							'comparison_label' => ''
						)
					)
				)
			) );

			$reports->register_endpoint( 'campaign_tracker_sales', array(
				'label' => __( 'Sales', 'edd-campaign-tracker' ),
				'views' => array(
					'tile' => array(
						'data_callback' => array( $this, 'sales_callback' ),
						'display_args'  => array(
							'comparison_label' => ''
						)
					)
				)
			) );

			$reports->register_endpoint( 'campaign_tracker_earnings_chart', array(
				'label' => __( 'Earnings Over Time', 'edd-campaign-tracker' ),
				'views' => array(
					'chart' => array(
						'data_callback' => array( $this, 'earnings_chart' ),
						'type'          => 'line',
						'options'       => array(
							'datasets' => array(
								'number' => array(
									'label'                => __( 'Number of Sales', 'edd-campaign-tracker' ),
									'borderColor'          => 'rgb(252,108,18)',
									'backgroundColor'      => 'rgba(252,108,18,0.2)',
									'fill'                 => true,
									'borderDash'           => array( 2, 6 ),
									'borderCapStyle'       => 'round',
									'borderJoinStyle'      => 'round',
									'pointRadius'          => 4,
									'pointHoverRadius'     => 6,
									'pointBackgroundColor' => 'rgb(255,255,255)',
								),
								'amount' => array(
									'label'                => __( 'Earnings', 'edd-campaign-tracker' ),
									'borderColor'          => 'rgb(24,126,244)',
									'backgroundColor'      => 'rgba(24,126,244,0.05)',
									'fill'                 => true,
									'borderWidth'          => 2,
									'type'                 => 'currency',
									'pointRadius'          => 4,
									'pointHoverRadius'     => 6,
									'pointBackgroundColor' => 'rgb(255,255,255)',
								),
							)
						)
					)
				)
			) );
		} catch ( EDD_Exception $e ) {

		}
	}

	/**
	 * Returns the campaign WHERE clause for the database query.
	 *
	 * @since 1.0.1
	 * @return string
	 */
	private function get_campaign_condition() {
		global $wpdb;

		$campaign = EDD\Reports\get_filter_value( 'campaign_tracker' );

		$campaign_condition = "AND meta_key = '_eddct_campaign_name'";
		if ( $campaign && 'all' !== $campaign ) {
			$campaign_condition .= $wpdb->prepare( " AND meta_value = %s", $campaign );
		}

		return $campaign_condition;
	}

	/**
	 * Calculates the total campaign earnings within the current period.
	 *
	 * @since 1.0.1
	 * @return string
	 */
	public function earnings_callback() {
		global $wpdb;

		$dates  = EDD\Reports\get_dates_filter( 'objects' );
		$column = EDD\Reports\get_taxes_excluded_filter() ? 'total - tax' : 'total';

		$earnings = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM({$column}) FROM {$wpdb->edd_orders} edd_o
			INNER JOIN {$wpdb->edd_ordermeta} edd_ometa ON( edd_o.id = edd_ometa.edd_order_id )
			WHERE edd_o.type = 'sale'
			AND edd_o.status IN( 'complete', 'revoked' )
			AND edd_o.date_created >= %s AND edd_o.date_created <= %s
			{$this->get_campaign_condition()}",
			$dates['start']->copy()->format( 'mysql' ),
			$dates['end']->copy()->format( 'mysql' )
		) );

		if ( is_null( $earnings ) ) {
			$earnings = 0;
		}

		return edd_currency_filter( edd_format_amount( $earnings ) );
	}

	/**
	 * Calculates the total campaign sales within the current period.
	 *
	 * @since 1.0.1
	 * @return string
	 */
	public function sales_callback() {
		global $wpdb;

		$dates = EDD\Reports\get_dates_filter( 'objects' );

		$sales = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT( edd_o.id ) FROM {$wpdb->edd_orders} edd_o
			INNER JOIN {$wpdb->edd_ordermeta} edd_ometa ON( edd_o.id = edd_ometa.edd_order_id )
			WHERE edd_o.type = 'sale'
			AND edd_o.status IN( 'complete', 'revoked' )
			AND edd_o.date_created >= %s AND edd_o.date_created <= %s
			{$this->get_campaign_condition()}",
			$dates['start']->copy()->format( 'mysql' ),
			$dates['end']->copy()->format( 'mysql' )
		) );

		return number_format_i18n( absint( $sales ) );
	}

	/**
	 * Retrieves results for campaign earnings/sales over time.
	 *
	 * @since 1.0.1
	 * @return array
	 */
	public function earnings_chart() {
		global $wpdb;

		$dates        = EDD\Reports\get_dates_filter( 'objects' );
		$day_by_day   = EDD\Reports\get_dates_filter_day_by_day();
		$hour_by_hour = EDD\Reports\get_dates_filter_hour_by_hour();
		$column       = EDD\Reports\get_taxes_excluded_filter() ? 'total - tax' : 'total';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT COUNT(edd_o.id) AS number, SUM({$column}) AS amount, edd_o.date_created AS date
			FROM {$wpdb->edd_orders} edd_o
			INNER JOIN {$wpdb->edd_ordermeta} edd_ometa ON( edd_o.id = edd_ometa.edd_order_id )
			WHERE edd_o.type = 'sale'
			AND edd_o.status IN( 'complete', 'revoked' )
			AND edd_o.date_created >= %s AND edd_o.date_created <= %s
			{$this->get_campaign_condition()}
			GROUP BY DATE(date_created)
			ORDER BY DATE(date_created)",
			$dates['start']->copy()->format( 'mysql' ),
			$dates['end']->copy()->format( 'mysql' )
		) );

		$number = $amount = array();

		try {
			// Initialise all arrays with timestamps and set values to 0.
			while ( strtotime( $dates['start']->copy()->format( 'mysql' ) ) <= strtotime( $dates['end']->copy()->format( 'mysql' ) ) ) {
				$timestamp = strtotime( $dates['start']->copy()->format( 'mysql' ) );

				$number[ $timestamp ][0] = $timestamp;
				$number[ $timestamp ][1] = 0;

				$amount[ $timestamp ][0] = $timestamp;
				$amount[ $timestamp ][1] = 0.00;

				// Loop through each date there were results, which we queried from the database.
				foreach ( $results as $result ) {

					$timezone         = new DateTimeZone( 'UTC' );
					$date_of_db_value = new DateTime( $result->date, $timezone );
					$date_on_chart    = new DateTime( $dates['start'], $timezone );

					// Add any results that happened during this hour.
					if ( $hour_by_hour ) {
						// If the date of this db value matches the date on this line graph/chart, set the y axis value for the chart to the number in the DB result.
						if ( $date_of_db_value->format( 'Y-m-d H' ) === $date_on_chart->format( 'Y-m-d H' ) ) {
							$number[ $timestamp ][1] += $result->number;
							$amount[ $timestamp ][1] += abs( $result->amount );
						}
						// Add any results that happened during this day.
					} elseif ( $day_by_day ) {
						// If the date of this db value matches the date on this line graph/chart, set the y axis value for the chart to the number in the DB result.
						if ( $date_of_db_value->format( 'Y-m-d' ) === $date_on_chart->format( 'Y-m-d' ) ) {
							$number[ $timestamp ][1] += $result->number;
							$amount[ $timestamp ][1] += abs( $result->amount );
						}
						// Add any results that happened during this month.
					} else {
						// If the date of this db value matches the date on this line graph/chart, set the y axis value for the chart to the number in the DB result.
						if ( $date_of_db_value->format( 'Y-m' ) === $date_on_chart->format( 'Y-m' ) ) {
							$number[ $timestamp ][1] += $result->number;
							$amount[ $timestamp ][1] += abs( $result->amount );
						}
					}
				}

				// Move the chart along to the next hour/day/month to get ready for the next loop.
				if ( $hour_by_hour ) {
					$dates['start']->addHour( 1 );
				} elseif ( $day_by_day ) {
					$dates['start']->addDays( 1 );
				} else {
					$dates['start']->addMonth( 1 );
				}
			}
		} catch ( \Exception $e ) {

		}

		return array(
			'number' => array_values( $number ),
			'amount' => array_values( $amount ),
		);
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
		$campaign = isset( $_GET['campaign'] ) ? sanitize_text_field( $_GET['campaign'] ) : null;

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
?>
						<p class="edd_graph_totals"><strong><?php _e( 'Total earnings for period shown: ', 'edd-campaign-tracker' ); echo edd_currency_filter( edd_format_amount( $earnings_totals ) ); ?></strong></p>
						<p class="edd_graph_totals"><strong><?php _e( 'Total sales for period shown: ', 'edd-campaign-tracker' ); echo edd_format_amount( $sales_totals, false ); ?></strong></p>

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
		$display          = 'other' == $dates['range'] ? '' : 'style="display:none;"';
		$view             = edd_get_reporting_view();
		$current_campaign = isset( $_GET['campaign'] ) ? sanitize_text_field( $_GET['campaign'] ) : null;

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

		if ( function_exists( 'edd_get_orders' ) ) {
			$campaigns = $wpdb->get_col(
		"SELECT DISTINCT ometa.meta_value FROM {$wpdb->edd_ordermeta} ometa
				LEFT JOIN {$wpdb->edd_orders} o ON( o.id = ometa.edd_order_id )
				WHERE ometa.meta_key = '_eddct_campaign_name'
				AND o.status IN( 'complete', 'revoked' )"
			);
		} else {
			$campaigns = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = '%s'
				AND p.post_status = '%s'
				AND p.post_type = '%s'
			", '_eddct_campaign_name', 'publish', 'edd_payment' ) );
		}

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
	protected function get_earnings_by_date( $campaign, $day, $month_num, $year = null, $hour = null ) {
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

		if ( ! empty( $day ) ) {
			$args['day'] = $day;
		}

		if ( ! empty( $hour ) ) {
			$args['hour'] = $hour;
		}

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
			set_transient( $key, $earnings, 3600 );
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

		if ( ! empty( $month_num ) ) {
			$args['monthnum'] = $month_num;
		}

		if ( ! empty( $day ) ) {
			$args['day'] = $day;
		}

		if ( ! empty( $hour ) ) {
			$args['hour'] = $hour;
		}

		$args = apply_filters( 'eddct_get_sales_by_date_args', $args );

		$key   = md5( serialize( $args ) );
		$count = get_transient( $key );

		if ( false === $count ) {
			$sales = new WP_Query( $args );
			$count = (int) $sales->post_count;
			// Cache the results for one hour
			set_transient( $key, $count, 3600 );
		}

		return $count;
	}
}

EDDCT_Reports::factory();
