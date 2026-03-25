<?php
/**
 * Class to manage the expiration points
 *
 * @class   YITH_WC_Points_Rewards_Expiration_Points
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_WC_Points_Rewards_Expiration_Points' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Expiration_Points
	 */
	class YITH_WC_Points_Rewards_Expiration_Points {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Expiration_Points
		 */
		protected static $instance;

		/**
		 * Check if the fix has been done during process
		 *
		 * @var bool
		 */
		protected $points_expired_check = false;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Expiration_Points
		 * @since  3.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}


		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 3.0.0
		 */
		private function __construct() {
			if ( ywpar_automatic_earning_points_enabled() ) {
				add_action( 'wp_loaded', array( $this, 'check_expiration_points_version' ), 20 );
			}

		}

		/**
		 * Check the version of plugin to expiration points
		 */
		public function check_expiration_points_version() {
			$time = ywpar_get_option( 'days_before_expiration', array() );
			if ( ywpar_get_option( 'enable_expiration_point', 'no' ) === 'yes' && isset( $time['number'] ) && ! empty( $time['number'] ) ) {
				$expiration_mode = get_option( 'yit_ywpar_expiration_mode', 'from_1.3.0' );

				if ( 'from_1.3.0' !== $expiration_mode ) {
					update_option( 'ywpar_enable_expiration_point', 'no' );
					update_option( 'yit_ywpar_expiration_mode', 'from_1.3.0' );
				} else {
					add_action( 'ywpar_cron', array( $this, 'set_expired_points' ) );
					add_action( 'ywpar_cron', array( $this, 'send_email_before_expiration' ) );
				}
			}
		}

		/**
		 * Fix expiration point before version 1.3.0
		 *
		 * @return bool
		 */
		public function reset_expiration_points() {

			if ( $this->points_expired_check || 1 === ywpar_get_option( 'points_expired_check' ) || apply_filters( 'ywpar_expiration_old_mode', false ) ) {
				return false;
			}

			global $wpdb;
			$users      = array();
			$remove     = array();
			$table_name = yith_points()->points_log->get_table_name();

			$results = $wpdb->get_results( $wpdb->prepare( "SELECT id, amount, user_id, order_id FROM {$table_name} WHERE action LIKE %s", 'expired_points' ) ); //phpcs:ignore

			if ( $results ) {
				foreach ( $results as $result ) {
					if ( ! in_array( $result->user_id, $users, true ) ) {
						$customer      = ywpar_get_customer( $result->user_id );
						$current_point = $customer->get_total_points();
						$old_points    = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$table_name} WHERE action NOT LIKE 'expired_points' AND user_id=%d", $result->user_id ) ); //phpcs:ignore

						if ( $current_point > 0 ) {
							$points_to_add = $old_points - $current_point;
						} else {
							$points_to_add = absint( $old_points ) + absint( $current_point );
						}
						$customer = ywpar_get_customer( $result->user_id );
						$customer->update_points( $points_to_add, 'admin_action', array( 'order_id' => $result->order_id ) );
						$users[] = $result->user_id;
					}

					$remove[] = $result->id;
				}

				if ( ! empty( $remove ) ) {
					$query = "DELETE from $table_name WHERE id IN (" . implode( ',', $remove ) . ');';
					$wpdb->query( $query ); // phpcs:ignore
				}

				$query = "UPDATE $table_name SET `cancelled`=NULL WHERE 1=1;";
				$wpdb->query( $query ); // phpcs:ignore
			}

			$this->points_expired_check = true;

			update_option( 'ywpar_points_expired_check', 1 );
			update_option( 'yit_ywpar_expiration_mode', 'from_1.3.0' );
		}


		/**
		 * Return the expiration points
		 *
		 * @param int    $interval Interval.
		 * @param bool   $limit Limit of the query.
		 * @param string $action Action.
		 *
		 * @return array
		 */
		public function get_user_expiration_points( $interval, $limit, $action = 'expire' ) {
			global $wpdb;
			$table_name = yith_points()->points_log->get_table_name();

			if ( 'expire' === $action ) {
				$query = "SELECT * FROM $table_name where ( date_earning <=  CURDATE() - INTERVAL $interval DAY ) AND amount > 0 AND ( cancelled IS NULL OR cancelled = '0000-00-00 00:00:00') ORDER BY user_id,date_earning ASC";
			} else {
				$interval_b = $interval + 1;
				$query      = "SELECT * FROM $table_name where ( date_earning <= CURDATE() - INTERVAL $interval DAY ) AND ( date_earning > CURDATE() - INTERVAL $interval_b DAY  ) AND amount > 0 AND  (cancelled IS NULL OR cancelled = '0000-00-00 00:00:00') ORDER BY user_id,date_earning ASC";
			}

			$query .= $limit ? ' LIMIT ' . $limit : '';

			$items = $wpdb->get_results( $query );
			$users = array();
			if ( $items ) {
				foreach ( $items as $item ) {
					$points_expiring = $item->amount;
					$user_id         = $item->user_id;
					$customer        = ywpar_get_customer( $user_id );
					$rewarded_points = $customer->get_rewarded_points( true );

					if ( ! isset( $users[ $user_id ] ) ) {
						$users[ $user_id ]['cancel_rows']     = array();
						$users[ $user_id ]['used_points']     = $customer->get_used_points( true );
						$users[ $user_id ]['points_expiring'] = 0;
					}

					$points_to_exp = 0;
					if ( $rewarded_points > 0 ) {
						$r1 = ( $rewarded_points - $users[ $user_id ]['used_points'] ) - $points_expiring;

						if ( $r1 >= 0 ) {
							$users[ $user_id ]['used_points'] = $users[ $user_id ]['used_points'] + $points_expiring;
						} else {
							$users[ $user_id ]['used_points'] = $rewarded_points;
							$points_to_exp                    = absint( $r1 );
						}
					} else {
						$points_to_exp = $item->amount;
					}

					$users[ $user_id ]['points_expiring'] += $points_to_exp;
					$users[ $user_id ]['cancel_rows'][]    = $item->id;

				}
			}

			return $users;

		}

		/**
		 * Set expired points
		 *
		 * @return bool
		 */
		public function set_expired_points() {

			global $wpdb;
			$table_name       = $wpdb->prefix . 'yith_ywpar_points_log';
			$date             = date( 'Y-m-d H:i:s' );  //phpcs:ignore
			$limit            = 50;
			$expiration_infos = ywpar_get_option( 'days_before_expiration' );
			$days_before      = ( 'months' === $expiration_infos['time'] ) ? 30 * $expiration_infos['number'] : $expiration_infos['number'];

			if ( '' === $days_before || $days_before <= 0 ) {
				return false;
			}

			$num_items = $wpdb->get_var( "SELECT count(1) FROM $table_name where ( date_earning <= CURDATE() - INTERVAL $days_before DAY ) AND amount > 0 AND ( cancelled IS NULL OR cancelled = '0000-00-00 00:00:00') ORDER BY date_earning" ); //phpcs:ignore
			while ( $num_items > 0 ) {
				$users = $this->get_user_expiration_points( $days_before, $limit, 'expire' );
				if ( $users ) {
					foreach ( $users as $user_id => $user ) {
						$customer = ywpar_get_customer( $user_id );

						if ( ! $customer ) {
							continue;
						}

						if ( ! empty( $user['used_points'] ) ) {
							$customer->set_used_points( intval( $user['used_points'] ) );
						}

						if ( ! empty( $user['cancel_rows'] ) ) {
							$query = "UPDATE $table_name SET cancelled = '$date' WHERE id IN  (" . implode( ',', $user['cancel_rows'] ) . ' )';

							$wpdb->query( $query );
						}

						if ( 0 == $user['points_expiring'] ) { //phpcs:ignore
							continue;
						}

						yith_points()->logger->add( 'yith_points_expiration', 'Cancelling ' . $user['points_expiring'] . ' points to the user ' . $customer->get_id() . ' after ' . $expiration_infos['number'] . ' ' . $expiration_infos['time'] );

						$customer->update_points(
							-abs( $user['points_expiring'] ),
							'expired_points',
							array(
								'date_earning' => $date,
								'cancelled'    => date_i18n( 'Y-m-d H:i:s' ),
							)
						);

					}
				}

				$num_items -= $limit;
			}

		}

		/**
		 * Send email before expiration points
		 *
		 * @return bool
		 */
		public function send_email_before_expiration() {

			if ( ywpar_get_option( 'send_email_before_expiration_date', 'no' ) !== 'yes' ) {
				return false;
			}


			global $wpdb;
			$table_name           = yith_points()->points_log->get_table_name();
			$expiration_infos     = ywpar_get_option( 'days_before_expiration' );
			$expire_date          = ( 'months' === $expiration_infos['time'] ) ? 30 * $expiration_infos['number'] : $expiration_infos['number'];
			$days_before_send     = ywpar_get_option( 'send_email_days_before' );
			$expire_date_string   = strtotime( '+' . $days_before_send . ' day', time() );
			$interval             = intval( $expire_date ) - intval( $days_before_send );
			$interval_b           = $interval + 1;

			if ( '' === $expire_date || $expire_date <= 0 || '' === $days_before_send || $days_before_send <= 0 ) {
				return false;
			}



			$query = "SELECT count(*) FROM $table_name where ( date_earning <= CURDATE() - INTERVAL $interval DAY ) AND ( date_earning > CURDATE() - INTERVAL $interval_b DAY  ) AND amount > 0 AND ( cancelled IS NULL OR cancelled = '0000-00-00 00:00:00') ORDER BY date_earning";

			$num_items = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( $num_items > 0 ) {
				$users     = $this->get_user_expiration_points( $interval, false, 'email' );
				$user_sent = array();

				foreach ( $users as $user_id => $user ) {
					$customer = ywpar_get_customer( $user_id );
					if ( ! $customer->is_enabled() || $user['points_expiring'] == 0 ) { //phpcs:ignore
						continue;
					}

					if ( in_array( $user_id, $user_sent ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						continue;
					}

					$user_info     = $customer->get_wc_customer();
					$billing_email = $user_info->get_billing_email();

					if ( empty( $billing_email ) ) {
						continue;
					}

					/**
					 * APPLY_FILTERS: ywpar_expiration_email_send
					 *
					 * filter description.
					 *
					 * @param bool $send .
					 * @param string $billing_email customer billing email.
					 * @param YITH_WC_Points_Rewards_Customer $customer customer object.
					 */
					if ( ! apply_filters( 'ywpar_expiration_email_send', true, $billing_email, $customer ) ) {
						continue;
					}

					$user_locale = '';
					global $sitepress;
					if ( ! is_null( $sitepress ) ) {
						$user_locale = get_user_meta( $user_id, 'locale', true );

						if ( strpos($user_locale, '_') !== false ) {
							$user_locale_parts = explode( "_", $user_locale );
							$user_locale       = $user_locale_parts[0];
						}

						if ( ! empty( $user_locale ) ) {
							$sitepress->switch_lang( $user_locale );
						}
					}
					$email_content_option = ywpar_get_option( 'expiration_email_content' );
					$email_content = $email_content_option;


					$current_points = $customer->get_total_points();
					$worth          = yith_points()->redeeming->calculate_price_worth_from_points( abs( $user['points_expiring'] ), $customer );

					$email_content = str_replace( '{username}', $user_info->get_username(), $email_content );
					$email_content = str_replace( '{first_name}', $user_info->get_billing_first_name(), $email_content );
					$email_content = str_replace( '{last_name}', $user_info->get_billing_last_name(), $email_content );
					$email_content = str_replace( '{expiring_points}', abs( $user['points_expiring'] ), $email_content );
					$email_content = str_replace( '{label_points}', ywpar_get_option( 'points_label_plural' ), $email_content );
					$email_content = str_replace( '{expiring_date}', date_i18n( wc_date_format(), $expire_date_string ), $email_content );
					$email_content = str_replace( '{total_points}', $current_points, $email_content );
					$email_content = str_replace( '{shop_url}', wc_get_page_permalink( 'shop' ), $email_content );
					$email_content = str_replace( '{discount}', $worth, $email_content );
					$email_content = str_replace( '{website_name}', get_option( 'blogname', '' ), $email_content );

					$args = array(
						'user_email'     => $billing_email,
						'email_content'  => $email_content,
						'expiration_day' => $expire_date,
						'user_id'        => $user_id,
						'item_id'        => $user['cancel_rows'],
						'user_locale'    => $user_locale,
					);

					$user_sent[] = $user_id;
					/**
					 * DO_ACTION: expired_points_mail
					 *
					 * hook on the expired points email.
					 *
					 * @param array $args list of arguments.
					 */					
					do_action( 'expired_points_mail', $args );
				}
			}
		}
	}

}
