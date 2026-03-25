<?php //phpcs:ignore phpcs: WordPress.Files.FileName.InvalidClassFileName.

/**
 * YITH_WC_Points_Rewards_Earning_Legacy Legacy Abstract Class.
 *
 * @class   YITH_WC_Points_Rewards_Earning_Legacy
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */



defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Earning_Legacy' ) ) {
	/**
	 * Class YITH_WC_Points_Rewards_Earning_Legacy
	 */
	abstract class YITH_WC_Points_Rewards_Earning_Legacy {

		/**
		 * |--------------------------------------------------------------------------
		 * | Deprecated Methods
		 * |--------------------------------------------------------------------------
		 */
		/**
		 * Return the global points of an object
		 *
		 * @param Object $object Object.
		 * @param string $type Calculated on product or order.
		 * @param bool   $integer Precision of points.
		 * @param string $currency Currency.
		 *
		 * @return int
		 * @deprecated 3.0.0
		 * @since   1.0.0
		 */
		public function get_point_earned( $object, $type = 'order', $integer = false, $currency = '' ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::get_point_earned', '3.0.0' );

			$price = 0;

			switch ( $type ) {
				case 'order':
					$price = $object->get_total();
					break;
				case 'product':
					$price = ywpar_get_product_price( $object, 'earn', $currency );
					break;
				default:
			}

			$calculated_points = yith_points()->earning->get_points_earned_from_price( $price, $currency, true );
			return $integer ? yith_ywpar_round_points( $calculated_points ) : $calculated_points;
		}

		/**
		 * Get date of first log creation.
		 *
		 * @deprecated 3.0.0
		 * @since  1.7.3
		 */
		public function ywpat_get_start_date() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::ywpat_get_start_date', '3.0.0', 'YITH_WC_Points_Rewards_Points_Log::get_start_date_of_all_actions' );
			return yith_points()->points_log->get_start_date_of_all_actions();
		}

		/**
		 * Assign points on birthdate
		 *
		 * @since  1.6.0
		 * @deprecated 3.0.0
		 */
		public function extra_points_birthdate() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::extra_points_birthdate', '3.0.0', 'YITH_WC_Points_Rewards_Extra_Points::extra_points_birthdate' );
			return YITH_WC_Points_Rewards_Extra_Points::get_instance()->extra_points_birthdate();
		}

		/**
		 * Return usable points
		 *
		 * @param int $user_id User id.
		 *
		 * @return int
		 * @since      1.0.0
		 * @deprecated 3.0.0
		 */
		public function get_usable_points( $user_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'yith_ywpar_points_log';
			$from_id    = 1;
			$query      = "SELECT id FROM  $table_name where user_id = $user_id AND action='points_exp' ORDER BY date_earning DESC LIMIT 1"; //phpcs:ignore
			$res        = $wpdb->get_row( $query ); //phpcs:ignore

			if ( ! empty( $res ) ) {
				$from_id = $res->id;
			}

			$query = "SELECT SUM(ywpar_points.amount) as usable_points FROM $table_name as ywpar_points where user_id = $user_id AND id > $from_id"; // phpcs:ignore
			$res   = $wpdb->get_row( $query ); //phpcs:ignore

			if ( ! empty( $res ) ) {
				return $res->usable_points;
			}
		}

		/**
		 * Check if an extra-points rule is used by customer.
		 *
		 * @param array $rule Rule.
		 * @param array $user_extrapoint Extrapoints.
		 *
		 * @deprecated 3.0.0
		 */
		private function check_extrapoint_rule( $rule, $user_extrapoint ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::check_extrapoint_rule', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Porting old extra-points method to the new
		 *
		 * @param int   $user_id User id.
		 * @param array $user_extra_point Extra points.
		 *
		 * @deprecated 3.0.0
		 */
		private function populate_extra_points_counter( $user_id, $user_extra_point ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::populate_extra_points_counter', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Registration extra-points.
		 *
		 * Assign extra-points to the user is the conditions setting about the registration are valid.
		 *
		 * @param mixed $customer_user Customer user.
		 * @deprecated 3.0.0
		 */
		public function extrapoints_to_new_customer( $customer_user ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::extrapoints_to_new_customer', '3.0.0', 'YITH_WC_Points_Rewards_Extra_Points::extra_points_to_new_customer_registration' );
			yith_points()->extra_points->extra_points_to_new_customer_registration( $customer_user );
		}

		/**
		 * Add extra points to the user.
		 *
		 * @param array $types Types.
		 * @param  int   $user_id User id.
		 * @param int   $order_id Order id.
		 *
		 * @return void|bool
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 */
		public function extra_points( $types, $user_id, $order_id = 0 ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::extra_points', '3.0.0', 'YITH_WC_Points_Rewards_Extra_Points::handle_actions' );
			yith_points()->extra_points->handle_actions( $types, $user_id, $order_id );
		}

		/**
		 * Calculate approx the points of product inside an order.
		 *
		 * @param integer               $product_id Product id.
		 * @param bool                  $integer Integer or no.
		 * @param WC_Order_Item_Product $order_item Order item.
		 * @param string                $currency Currency.
		 *
		 * @return int
		 * @deprecated 3.0.0
		 */
		public function calculate_product_points_in_order( $product_id, $integer, $order_item, $currency ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::calculate_product_points_in_order', '3.0.0', 'YITH_WC_Points_Rewards_Orders::calculate_order_item_points' );
			return YITH_WC_Points_Rewards_Orders::get_instance()->calculate_order_item_points( $order_item, $currency, $integer );
		}

		/**
		 * Check the validate on an interval of date
		 *
		 * @param string $datefrom Date from.
		 * @param string $dateto Date to.
		 *
		 * @return int
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 */
		public function is_ondate( $datefrom, $dateto ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::calculate_product_points_in_order', '3.0.0', 'ywpar_check_date_interval' );
			return ywpar_check_date_interval( $datefrom, $dateto );
		}

		/**
		 * Triggered when a reviews status changes.
		 *
		 * If extra point to reviews is set, call the extra method.
		 * Called by 'comment_post' 'wp_set_comment_status' hooks
		 *
		 * @param int    $comment_id Comment id.
		 * @param string $status Status.
		 *
		 * @deprecated 3.0.0
		 */
		public function add_order_points_with_review( $comment_id, $status ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::add_order_points_with_review', '3.0.0', 'YITH_WC_Points_Rewards_Extra_Points::extra_points_on_review' );
			yith_points()->extra_points->extra_points_on_review( $comment_id, $status );
		}

		/**
		 * Add Point to the user.
		 *
		 * @param  int    $user_id User id.
		 * @param  mixed  $points Points.
		 * @param  string $action Action.
		 * @param  int    $order_id Order id.
		 * @param bool   $register_log Register log.
		 *
		 * @return void
		 * @since      1.0.0
		 *
		 * @deprecated 3.0.0
		 */
		public function add_points( $user_id, $points, $action, $order_id, $register_log = true ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::add_points', '3.0.0', 'YITH_WC_Points_Rewards_Customer::update_points' );
			$point_customer = ywpar_get_customer( $user_id );
			if ( $point_customer ) {
				$args = array(
					'order_id' => $order_id,
				);

				$point_customer->update_points( $points, $action, $args );
			}
		}

		/**
		 * This function get the user id to pass to extra_points method
		 * when a review status changed is triggered in YITH WooCommerce Advanced Review
		 *
		 * Triggered by 'ywar_review_approve_status_changed' YITH Advanced Review hook
		 *
		 * @param  int    $review_id Review id.
		 * @param string $status Status.
		 * @deprecated 3.0.0
		 */
		public function add_order_points_with_advanced_reviews( $review_id, $status ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::add_order_points_with_advanced_reviews', '3.0.0', 'YITH_WC_Points_Rewards_Extra_Points::extra_points_on_review_with_advanced_reviews' );
			yith_points()->extra_points->extra_points_on_review_with_advanced_reviews( $review_id, $status );
		}

		/**
		 * Return the global points of an object from price
		 *
		 * @param float  $price  Price.
		 * @param bool   $integer Format of point.
		 * @param string $currency Currency.
		 *
		 * @return int
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 */
		public function get_point_earned_from_price( $price, $integer = false, $currency = '' ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Earning::get_point_earned_from_price', '3.0.0', 'YITH_WC_Points_Rewards_Earning::get_points_earned_from_price' );
			return $this->get_points_earned_from_price( $price, $currency, true );
		}

		/**
		 * Fix expiration point before version 1.3.0
		 *
		 * @return bool
		 * @deprecated 3.0.0
		 */
		public function yith_ywpar_reset_expiration_points() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::yith_ywpar_reset_expiration_points', '3.0.0', 'YITH_WC_Points_Rewards_Expiration_Points::reset_expiration_points' );
			return yith_points()->expiration_points->reset_expiration_points();
		}

	}
}

if ( ! function_exists( 'YITH_WC_Points_Rewards_Earning' ) ) {
	/**
	 * Unique access to instance of YITH_WC_Points_Rewards_Earning class
	 *
	 * @deprecated 3.0.0
	 * @since      1.0.0
	 */
	function YITH_WC_Points_Rewards_Earning() {
		_deprecated_function( __FUNCTION__, '3.0.0', 'yith_points()->earning' );
		return yith_points()->earning;
	}
}
