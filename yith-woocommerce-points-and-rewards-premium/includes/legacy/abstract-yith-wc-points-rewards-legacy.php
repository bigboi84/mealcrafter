<?php //phpcs:ignore phpcs: WordPress.Files.FileName.InvalidClassFileName.
/**
 * YITH_WC_Points_Rewards_Legacy Legacy Abstract Class.
 *
 * @class   YITH_WC_Points_Rewards_Legacy
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Legacy' ) ) {
	/**
	 * Class YITH_WC_Points_Rewards_Legacy
	 */
	abstract class YITH_WC_Points_Rewards_Legacy {

		/**
		 * Returns if the plugin is enabled by option
		 *
		 * @since  1.0.0
		 * @return boolean
		 * @deprecated 3.0.0
		 */
		public function is_enabled() {
			return apply_filters( 'ywpar_enabled', true );
		}

		/**
		 * Remove the user from the banned users
		 *
		 * @deprecated 3.0.0
		 * @param int $user_id User id.
		 */
		public function unban_user( $user_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::unban_user', '3.0.0', 'YITH_WC_Points_Rewards_Customer::unban' );
			$point_customer = ywpar_get_customer( $user_id );
			$point_customer && $point_customer->unban();
		}


		/**
		 * Ban an user
		 *
		 * @deprecated 3.0.0
		 * @param int $user_id User id.
		 */
		public function ban_user( $user_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::ban_user', '3.0.0', 'YITH_WC_Points_Rewards_Customer::ban' );
			$point_customer = ywpar_get_customer( $user_id );
			$point_customer && $point_customer->ban();
		}

		/**
		 * Check if a user is banned
		 *
		 * @deprecated 3.0.0
		 * @param int $user_id User id.
		 */
		public function is_banned( $user_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::is_banned', '3.0.0', 'YITH_WC_Points_Rewards_Customer::is_banned' );
			$point_customer = ywpar_get_customer( $user_id );
			return $point_customer ? $point_customer->is_banned() : false;
		}

		/**
		 * Sets used points of a user from the user meta if exists.
		 *
		 * @since 1.3.0
		 * @param int $user_id User id.
		 * @param int $new_used_points Points.
		 * @return void
		 * @deprecated 3.0.0
		 */
		public function set_used_points( $user_id, $new_used_points ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::set_used_points', '3.0.0', 'YITH_WC_Points_Rewards_Customer::set_used_points' );
			$point_customer = ywpar_get_customer( $user_id );
			if ( $point_customer ) {
				$point_customer->set_used_points( $new_used_points );
			}
		}

		/**
		/**
		 * Returns the list of entries for the customer
		 *
		 * @param int $user_id User id.
		 * @deprecated 3.0.0
		 *
		 * @return array
		 */
		public function get_history( $user_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::user_list_discount', '3.0.0', 'YITH_WC_Points_Rewards_Customer::get_history' );
			$point_customer = ywpar_get_customer( $user_id );
			return $point_customer ? $point_customer->get_history() : array();
		}

		/**
		 * Get the user birthdate,
		 * check if there's the date registered on YITH WooCommerce Coupons Email System.
		 *
		 * @param int $user_id User id.
		 * @deprecated 3.0.0
		 *
		 * @return mixed
		 */
		public function get_user_birthdate( $user_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::get_user_birthdate', '3.0.0', 'YITH_WC_Points_Rewards_Customer::get_birthdate' );
			$point_customer = ywpar_get_customer( $user_id );
			return $point_customer ? $point_customer->get_birthdate() : '';
		}

		/**
		 * Reset points of a user
		 *
		 * @param int $user_id User id.
		 * @since 1.1.3
		 * @deprecated 3.0.0
		 * @return void
		 */
		public function reset_user_points( $user_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::reset_user_points', '3.0.0', 'YITH_WC_Points_Rewards_Customer::reset' );
			$point_customer = ywpar_get_customer( $user_id );
			$point_customer && $point_customer->reset();
		}

		/**
		 * Returns if the user is enable to earn or redeem points
		 *
		 * @param string $action Action.
		 * @param string $user_id User id.
		 *
		 * @return bool
		 */
		public function is_user_enabled( $action = 'earn', $user_id = '' ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::is_user_enabled', '3.0.0', 'YITH_WC_Points_Rewards_Customer::is_enabled' );
			$point_customer = ywpar_get_customer( $user_id );
			return $point_customer && $point_customer->is_enabled( $action );
		}

		/**
		 * Filters woocommerce available mails, to add wishlist related ones
		 *
		 * @param array $emails Emails.
		 *
		 * @return array
		 * @deprecated 3.0.0
		 * @since 1.0
		 */
		public function add_woocommerce_emails( $emails ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::add_woocommerce_emails', '3.0.0', 'YITH_WC_Points_Rewards_Email::add_woocommerce_emails' );
			return YITH_WC_Points_Rewards_Email::add_woocommerce_emails();
		}

		/**
		 * Add CSS to WC emails
		 *
		 * @param string   $css   The email CSS.
		 * @param WC_Email $email The current email object.
		 * @deprecated 3.0.0
		 * @return string
		 */
		public function add_email_styles( $css, $email = null ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::add_email_styles', '3.0.0', 'YITH_WC_Points_Rewards_Email::add_email_styles' );
			return YITH_WC_Points_Rewards_Email::add_email_styles();
		}


		/**
		 * Returns the list of all postmeta of orders used be plugin
		 *
		 * @return array
		 * @deprecated 3.0.0
		 * @since 1.1.3
		 */
		public function get_ordermeta_list() {
			_deprecated_function( 'YITH_WC_Points_Rewards::get_ordermeta_list', '3.0.0', 'YITH_WC_Points_Rewards_Orders::get_ordermeta_list' );
			return YITH_WC_Points_Rewards_Orders::get_ordermeta_list();
		}


		/**
		 * Delete the history of a user
		 *
		 * @deprecated 3.0.0
		 * @param int $user_id User id.
		 */
		public function remove_user_log( $user_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::remove_user_log', '3.0.0', 'YITH_WC_Points_Rewards_Points_Log::remove_user_log' );
			yith_points()->points_log->remove_user_log( $user_id );
		}

		/**
		 * Get options from db
		 *
		 * @param string $option Option name.
		 * @param mixed  $value Default value.
		 *
		 * @deprecated 3.0.0
		 * @return mixed
		 * @since   1.0.0
		 */
		public function get_option( $option, $value = false ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::get_option', '3.0.0', 'ywpar_get_option' );
			ywpar_get_option( $option, $value );
		}

		/**
		 * Save customer birthdate
		 *
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 * @param   int $customer_id Customer id.
		 * @return  void
		 */
		public function save_birthdate( $customer_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::save_birthdate', '3.0.0', 'YITH_WC_Points_Rewards_Frontend::save_birthdate' );
			yith_points()->frontend->save_birthdate( $customer_id );
		}

		/**
		 * Returns the list of user order by the meta '_ywpar_user_total_discount' that is the
		 * total amount saved by each customer
		 *
		 * @param int $number Number of results.
		 * @deprecated 3.0.0
		 * @return array
		 */
		public function user_list_discount( $number ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::user_list_discount', '3.0.0', 'YITH_WC_Points_Rewards_Admin::user_list_discount' );
			return yith_points()->admin->user_list_discount( $number );
		}


		/**
		 * Returns the list of users order by the meta '_ywpar_user_total_points' that is the
		 * total points of each customer.
		 *
		 * @param int $number Number of results.
		 * @deprecated 3.0.0
		 * @return array
		 */
		public function user_list_points( $number ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::user_list_points', '3.0.0' );
			$user_query = new WP_User_Query(
				array(
					'number'   => $number,
					'meta_key' => '_ywpar_user_total_points' . ywpar_get_blog_suffix(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'orderby'  => 'meta_value_num',
					'order'    => 'DESC',
					'fields'   => array( 'ID', 'display_name' ),
				)
			);
			$users      = $user_query->get_results();

			return $users;
		}

		/**
		 * Send the email if the user has updated his points
		 *
		 * @param string|int $customer_id Customer id.
		 * @deprecated 3.0.0
		 */
		public function send_email_update_points( $customer_id = '' ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::send_email_update_points', '3.0.0', 'YITH_WC_Points_Rewards_Email::send_email_update_points' );
			YITH_WC_Points_Rewards_Email::send_email_update_points( $customer_id );
		}

		/**
		 * Loads WC Mailer when needed
		 *
		 * @return void
		 * @since 1.0
		 * @deprecated 3.0.0
		 */
		public function load_wc_mailer() {
			_deprecated_function( 'YITH_WC_Points_Rewards::load_wc_mailer', '3.0.0', 'YITH_WC_Points_Rewards_Email::load_wc_mailer' );
			YITH_WC_Points_Rewards_Email::load_wc_mailer();
		}

		/**
		 * Gets the label for an action
		 *
		 * @param string $label Label.
		 *
		 * @return string
		 * @since  1.0.0
		 * @deprecated 3.0.0
		 */
		public function get_action_label( $label ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::get_action_label', '3.0.0', 'ywpar_get_action_label' );
			return ywpar_get_action_label( $label );
		}

		/**
		 * Add a record inside the table of log
		 *
		 * @param int        $user_id User id.
		 * @param string     $action Action.
		 * @param int        $order_id Order id.
		 * @param int        $amount Points.
		 * @param bool|false $data_earning Earn data.
		 * @param bool|false $expired Expired points.
		 * @param string     $description Description.
		 * @deprecated 3.0.0
		 */
		public function register_log( $user_id, $action, $order_id, $amount, $data_earning = false, $expired = false, $description = '' ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::register_log', '3.0.0', 'YITH_WC_Points_Rewards_Points_Log::add_item' );
			$args = array(
				'user_id'      => $user_id,
				'action'       => $action,
				'order_id'     => $order_id,
				'amount'       => $amount,
				'date_earning' => $data_earning ? $data_earning : date_i18n( 'Y-m-d H:i:s' ),
				'description'  => $description,
				'cancelled'    => $expired ? date_i18n( 'Y-m-d H:i:s' ) : null,
			);

			yith_points()->points_log->add_item( $args );
		}

		/**
		 * Add points to customer
		 *
		 * @param int    $user_id User id.
		 * @param int    $points_to_add Points to add.
		 * @param string $action Action.
		 * @param string $description Description.
		 * @param int    $order_id Order id.
		 * @param string $data_earning Data earning.
		 * @param bool   $expired Expired.
		 *
		 * @param int    $register_log Register Log.
		 *
		 * @deprecated 3.0.0
		 */
		public function add_point_to_customer( $user_id, $points_to_add, $action, $description = '', $order_id = '', $data_earning = '', $expired = false, $register_log = 1 ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::add_point_to_customer', '3.0.0', 'YITH_WC_Points_Rewards_Customer::update_points' );
			$point_customer = ywpar_get_customer( $user_id );
			if ( $point_customer ) {
				$args = array(
					'order_id'     => $order_id,
					'description'  => $description,
					'data_earning' => $data_earning,
					'cancelled'    => $expired ? date_i18n( 'Y-m-d H:i:s' ) : null,
				);

				$point_customer->update_points( $points_to_add, $action, $args );
			}
		}

		/**
		 * Shortcode to show the current customer points
		 *
		 * @param array $atts Attributes.
		 * @param null  $content Content.
		 * @deprecated 3.0.0
		 * @return string|void
		 */
		public function add_shortcode( $atts, $content = null ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::add_shortcode', '3.0.0', 'YITH_WC_Points_Rewards_Shortcodes::get_current_customer_points' );
			return YITH_WC_Points_Rewards_Shortcodes::get_instance()->get_current_customer_points( $atts, $content );
		}

		/**
		 * Shortcode of the list of points in my account
		 *
		 * @param array $atts Attributes.
		 * @param null  $content Content.
		 * @deprecated 3.0.0
		 *
		 * @return string|void
		 */
		public function add_shortcode_list( $atts, $content = null ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::add_shortcode_list', '3.0.0', 'YITH_WC_Points_Rewards_Shortcodes::get_current_customer_history' );
			return YITH_WC_Points_Rewards_Shortcodes::get_instance()->get_current_customer_history( $atts, $content );
		}

		/**
		 * Check the version of plugin to expiration points
		 *
		 * @deprecated 3.0.0
		 */
		private function check_expiration_points_version() {
			_deprecated_function( 'YITH_WC_Points_Rewards::check_expiration_points_version', '3.0.0', 'YITH_WC_Points_Rewards_Expiration_Points::check_expiration_points_version' );
		}

		/**
		 * Send email before expiration points
		 *
		 * @deprecated 3.0.0
		 * @return bool
		 */
		public function send_email_before_expiration() : bool {
			_deprecated_function( 'YITH_WC_Points_Rewards::send_email_before_expiration', '3.0.0', 'YITH_WC_Points_Rewards_Expiration_Points::send_email_before_expiration' );
			return yith_points()->expiration_points->send_email_before_expiration();
		}

		/**
		 * Set points as expired
		 *
		 * @deprecated 3.0.0
		 * @return bool
		 */
		public function set_expired_points() {
			_deprecated_function( 'YITH_WC_Points_Rewards::set_expired_points', '3.0.0', 'YITH_WC_Points_Rewards_Expiration_Points::set_expired_points' );
			return yith_points()->expiration_points->set_expired_points();
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
			_deprecated_function( 'YITH_WC_Points_Rewards::get_user_expiration_points', '3.0.0', 'YITH_WC_Points_Rewards_Expiration_Points::get_user_expiration_points' );
			return yith_points()->expiration_points->get_user_expiration_points( $interval, $limit, $action );
		}

		/**
		 * Get used points of a user from the user meta if exists.
		 * Returns 0 and set meta if it doesn't exists
		 *
		 * @param int $user_id User id.
		 *
		 * @return int
		 * @since 1.3.0
		 */
		public function get_used_points( $user_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards::get_used_points', '3.0.0', 'YITH_WC_Points_Rewards_Customer::get_used_points' );
			$customer = ywpar_get_customer( $user_id );
			return $customer->get_used_points();
		}
	}
}

if ( ! function_exists( 'YITH_WC_Points_Rewards' ) ) {
	/**
	 * Unique access to instance of YITH_WC_Points class
	 *
	 * @deprecated 3.0.0
	 * @since      1.0.0
	 */
	function YITH_WC_Points_Rewards() {
		_deprecated_function( __FUNCTION__, '3.0.0', 'yith_points()' );
		return yith_points();
	}
}
