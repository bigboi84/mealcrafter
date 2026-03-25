<?php
/**
 * Log Class to manage the customer points
 *
 * @class   YITH_WC_Points_Rewards_Points_Log
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
if ( ! class_exists( 'YITH_WC_Points_Rewards_Points_Log' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Points_Log
	 */
	class YITH_WC_Points_Rewards_Points_Log {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Points_Log
		 */
		protected static $instance;

		/**
		 * Table name
		 *
		 * @var string
		 */
		protected $table_name = 'yith_ywpar_points_log';

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Points_Log
		 * @since  2.2.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();

		}

		/**
		 * Return the table name
		 */
		public function get_table_name() {
			global $wpdb;

			return $wpdb->prefix . $this->table_name;
		}

		/**
		 * Return the table format
		 */
		public function get_table_format() {
			return array(
				'%d', // user_id.
				'%s', // action.
				'%d', // order_id.
				'%d', // amount.
				'%s', // date_earning.
				'%s', // cancelled.
				'%s', // description.
				'%s', // info.
			);
		}


		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 2.2.0
		 */
		private function __construct() {

		}

		/**
		 * Delete the history of a user
		 *
		 * @param int $user_id User id.
		 * @return bool
		 */
		public function remove_user_log( $user_id ) {
			global $wpdb;
			return $wpdb->delete( $this->get_table_name(), array( 'user_id' => $user_id ), array( '%d' ) );
		}

		/**
		 * Return how many items are registered inside the log for a specific customer
		 *
		 * @param int $customer_id Customer id.
		 * @return int
		 */
		public function get_customer_log_items_count( $customer_id ) {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->get_table_name()} WHERE user_id = %d", $customer_id ) );
		}

		/**
		 * Return the older earning date
		 *
		 * @return string
		 */
		public function get_start_date_of_all_actions() {
			global $wpdb;
			$transient_name = 'ywpar_start_date';
			$start_date     = get_transient( $transient_name );

			if ( false === $start_date ) {
				$start_date = $wpdb->get_var( "SELECT date_earning FROM  {$this->get_table_name()} ORDER BY date_earning ASC LIMIT 1" );
				set_transient( $transient_name, $start_date, 7 * DAY_IN_SECONDS );
			}

			// APPLY_FILTERS : ywpar_get_start_date_filter: change or disable the logger start date.
			return apply_filters( 'ywpar_get_start_date_filter', $start_date );
		}

		/**
		 * Return the older earning date for a specific action
		 *
		 * @param string $action Action.
		 * @param int    $customer_id Customer id.
		 *
		 * @return string
		 */
		public function get_start_date_of_action( $action, $customer_id ) {
			global $wpdb;
			$date_earning = $wpdb->get_var( $wpdb->prepare( "SELECT date_earning FROM {$this->get_table_name()} WHERE user_id = %d AND action=%s ORDER BY date_earning DESC LIMIT 1", $customer_id, $action ) );
			return $date_earning;
		}

		/**
		 * Add an item inside the log
		 *
		 * @param array $args Arguments.
		 * @return bool
		 */
		public function add_item( $args ) {

			global $wpdb;

			$default_args = array(
				'user_id'      => 0,
				'action'       => 'order_completed',
				'order_id'     => '',
				'amount'       => 0,
				'date_earning' => date_i18n( 'Y-m-d H:i:s' ),
				'description'  => '',
				'cancelled'    => null,
				'info'         => '',
			);

			$args = wp_parse_args( $args, $default_args );

			return $wpdb->insert( $this->get_table_name(), $args, $this->get_table_format() );
		}

		/**
		 * Return the list of items
		 *
		 * @param array $args Arguments.
		 * @return array|object|null
		 */
		public function get_items( $args ) {

			global $wpdb;

			$default_args = array(
				'user_id'   => 0,
				'per_pages' => -1,
				'page'      => 1,
				'order'     => 'DESC',
				'orderby'   => 'date_earning',
			);

			$args = wp_parse_args( $args, $default_args );

			if ( -1 === $args['per_pages'] ) {
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$this->get_table_name()} WHERE user_id = %d ORDER BY {$args['orderby']} {$args['order']}", $args['user_id'] ), ARRAY_A );
			} else {
				$offset  = ( $args['page'] - 1 ) * $args['per_pages'];
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$this->get_table_name()} WHERE user_id = %d ORDER BY {$args['orderby']} {$args['order']} LIMIT %d, %d", $args['user_id'], $offset, $args['per_pages'] ), ARRAY_A );
			}

			return $results;

		}

		/**
		 * Return the list of total points earned during a specific interval
		 *
		 * @param string $interval Monthly or Weekly.
		 */
		public function get_total_points_amount_and_user_by_interval( $interval ) {

			global $wpdb;

			$limit = 5;
			$result = false;

			if ( 'weekly' === $interval ) {
				$result = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()} where (date_earning >= CURDATE() - INTERVAL 7 DAY ) AND ( cancelled IS NULL OR cancelled = %s ) AND amount > 0 GROUP BY user_id ORDER BY total DESC LIMIT %d", '0000-00-00 00:00:00', $limit ) );
			}
			if ( 'monthly' === $interval ) {
				$result = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()} where (date_earning >= CURDATE() - INTERVAL 1 MONTH ) AND ( cancelled IS NULL OR cancelled = %s ) AND amount > 0  GROUP BY user_id ORDER BY total DESC LIMIT %d", '0000-00-00 00:00:00', $limit ) );
			}

			return $result;
		}



		/**
		 * Get Best Users by Time
		 *
		 * @param   string $time Time.
		 * @param   int    $num_of_customers Num of customer.
		 *
		 * @return  array
		 * @since   2.2.0
		 */
		public function get_best_users( $time = 'all_time', $num_of_customers = 3 ) {

			$best_users = wp_cache_get( 'ywpar_best_users_' . $time . '_' . $num_of_customers, 'ywpar_points' );
			if ( false !== $best_users ) {
				return $best_users;
			}

			global $wpdb;
			/**
			* APPLY_FILTERS: ywpar_best_users_query_limit
			*
			* best users query limit (default: 3).
			* 
			* @param int $num_of_customers number of customers
			*
			*/
			$limit = apply_filters( 'ywpar_best_users_query_limit', $num_of_customers );
			/**
			* APPLY_FILTERS: ypwar_get_best_users_calc_type
			*
			* best users calculation type (default: positive).
			* 
			* @param string $type positive or negative
			* @param string $time time of the query (all_time, today, last_month, this_week)
			*
			*/
			$calculation_type = apply_filters( 'ypwar_get_best_users_calc_type', 'positive', $time );
			switch ( $time ) {
				case 'today':
					if ( 'positive' === $calculation_type ) {
						$query = "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()} as pt INNER JOIN {$wpdb->users} as wpu ON wpu.ID = pt.user_id where (action!='order_refund') AND (date_earning >= CURDATE()) AND amount > 0 GROUP BY user_id ORDER BY total DESC LIMIT $limit";
					} else {
						$query = "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()} as pt INNER JOIN {$wpdb->users} as wpu ON wpu.ID = pt.user_id where (date_earning >= CURDATE()) GROUP BY user_id ORDER BY total DESC LIMIT $limit";
					}

					break;
				case 'last_year':
					if ( 'positive' === $calculation_type ) {
						$query = "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()} as pt INNER JOIN {$wpdb->users} as wpu ON wpu.ID = pt.user_id where (action!='order_refund') AND (amount > 0) AND (date_earning >= CURDATE() - INTERVAL 1 YEAR )  GROUP BY user_id ORDER BY total DESC LIMIT $limit";
					} else {
						$query = "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()} as pt INNER JOIN {$wpdb->users} as wpu ON wpu.ID = pt.user_id where (date_earning >= CURDATE() - INTERVAL 1 YEAR )  GROUP BY user_id ORDER BY total DESC LIMIT $limit";
					}	
					break;
				case 'last_month':
					if ( 'positive' === $calculation_type ) {
						$query = "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()} as pt INNER JOIN {$wpdb->users} as wpu ON wpu.ID = pt.user_id where (action!='order_refund') AND (amount > 0) AND (date_earning >= CURDATE() - INTERVAL 1 MONTH )  GROUP BY user_id ORDER BY total DESC LIMIT $limit";
					} else {
						$query = "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()} as pt INNER JOIN {$wpdb->users} as wpu ON wpu.ID = pt.user_id where (date_earning >= CURDATE() - INTERVAL 1 MONTH )  GROUP BY user_id ORDER BY total DESC LIMIT $limit";
					}
					break;
				case 'this_week':
					if ( 'positive' === $calculation_type ) {
						$query = "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()} as pt INNER JOIN {$wpdb->users} as wpu ON wpu.ID = pt.user_id where (action!='order_refund') AND (amount > 0) AND (date_earning >= CURDATE() - INTERVAL 7 DAY )  GROUP BY user_id ORDER BY total DESC LIMIT $limit";
					} else {
						$query = "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()} as pt INNER JOIN {$wpdb->users} as wpu ON wpu.ID = pt.user_id where (date_earning >= CURDATE() - INTERVAL 1 MONTH )  GROUP BY user_id ORDER BY total DESC LIMIT $limit";
					}
					break;
				default:
					if ( 'positive' === $calculation_type ) {
						$query = "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()} as pt INNER JOIN {$wpdb->users} as wpu ON wpu.ID = pt.user_id where (action!='order_refund') AND (amount > 0) GROUP BY user_id ORDER BY total DESC LIMIT $limit";
						
					} else {
						$query = "SELECT user_id, SUM(amount) as total FROM {$this->get_table_name()}  as pt INNER JOIN {$wpdb->users} as wpu ON wpu.ID = pt.user_id GROUP BY user_id ORDER BY total DESC LIMIT $limit";
					}
					break;
			}

			$result = $wpdb->get_results( $query ); //phpcs:ignore
			wp_cache_set( 'ywpar_best_users_' . $time . '_' . $num_of_customers, $result, 'ywpar_points' );

			return $result;

		}

		/**
		 * Get the rewarded points of a user from the database.
		 *
		 * @param int $user_id User id.
		 * @return int
		 * @since 3.0.0
		 */
		public function get_user_rewarded_points( $user_id ) {
			global $wpdb;

			$rewarded_points = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(pl.amount) FROM {$this->get_table_name()} as pl where pl.user_id = %d AND ( pl.action IN ( 'redeemed_points', 'order_refund', 'admin_action', 'order_cancelled', 'expired_points', 'shared_points') AND pl.amount < 0 )", $user_id ) );
			$rewarded_points = is_null( $rewarded_points ) ? 0 : absint( $rewarded_points );

			return $rewarded_points;
		}

		/**
		 * Get the collected points of a user from the database.
		 *
		 * @param int $user_id User id.
		 * @return int
		 * @since 3.0.0
		 */
		public function get_collected_points( $user_id ) {
			global $wpdb;

			$collected = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(pl.amount) FROM {$this->get_table_name()} as pl where pl.user_id = %d AND pl.amount > 0", $user_id ) );
			$collected = $collected ? $collected : 0;
			return $collected;
		}


		/**
		 * Get the collected points of a user from the database.
		 *
		 * @param int $user_id User id.
		 * @return int
		 * @since 3.0.0
		 */
		public function get_total_points( $user_id ) {
			global $wpdb;

			$total = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(pl.amount) FROM {$this->get_table_name()} as pl where pl.user_id = %d", $user_id ) );
			$total = $total ? $total : 0;

			return $total;
		}

		/**
		 * Get the used points of a user from the database.
		 *
		 * @param int $user_id User id.
		 * @return int
		 * @since 3.0.0
		 */
		public function get_user_used_points( $user_id ) {
			global $wpdb;
			$table_name  = $this->get_table_name();
			$query       = "SELECT SUM(pl.amount) FROM $table_name as pl where pl.user_id = $user_id AND amount > 0 AND ( cancelled IS NOT NULL AND cancelled <> '0000-00-00 00:00:00')";
			$used_points = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			return is_null( $used_points ) ? 0 : absint( $used_points );
		}


		/**
		 * Return the list of table rows with the date earning included in the last day.
		 *
		 * @param string|int $customer_id Customer id.
		 */
		public function get_user_list_active_in_the_last_day( $customer_id = '' ) {

			global $wpdb;
			$query = 'SELECT user_id FROM ' . $this->get_table_name() . " where ( date_earning >= CURDATE() - INTERVAL 1 DAY ) AND ( cancelled IS NULL OR cancelled = '0000-00-00 00:00:00')";

			if ( '' !== $customer_id ) {
				$query .= " AND user_id=$customer_id";
			}

			$query .= ' GROUP BY user_id';
			return $wpdb->get_col( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		}

		/**
		 * Return the list of table rows with the date earning included in the last day.
		 *
		 * @param int $customer_id Customer id.
		 */
		public function get_items_earned_in_last_day_by_customer( $customer_id ) {
			global $wpdb;
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->get_table_name()} where ( date_earning >= CURDATE() - INTERVAL 1 DAY ) AND ( cancelled IS NULL OR cancelled = %s ) AND user_id=%d", '0000-00-00 00:00:00', $customer_id ) );
		}

		/**
		 * Truncate the table
		 */
		public function truncate_table() {
			global $wpdb;
			$wpdb->query( "TRUNCATE TABLE {$this->get_table_name()}" );
		}


	}

}
