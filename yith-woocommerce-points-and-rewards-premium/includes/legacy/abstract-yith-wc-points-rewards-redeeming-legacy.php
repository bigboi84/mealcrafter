<?php //phpcs:ignore phpcs: WordPress.Files.FileName.InvalidClassFileName.
/**
 * YITH_WC_Points_Rewards_Redeeming_Legacy Legacy Abstract Class.
 *
 * @class   YITH_WC_Points_Rewards_Redeeming_Legacy
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Redeeming_Legacy' ) ) {
	/**
	 * Class YITH_WC_Points_Rewards_Redeeming_Legacy
	 */
	abstract class YITH_WC_Points_Rewards_Redeeming_Legacy {

		/**
		 * |--------------------------------------------------------------------------
		 * | Deprecated Methods
		 * |--------------------------------------------------------------------------
		 */

		/**
		 * Init the class.
		 *
		 * @deprecated 3.0.0
		 */
		public function init() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::init', '3.0.0' );
		}
		/**
		 * Calculate the max discount of a product.
		 *
		 * Check if some option is set on product or category if not the
		 * general conversion will be used.
		 *
		 * @param int        $product_id Product id.
		 * @param float      $price Price.
		 * @param WC_Product $_product Product.
		 * @return float|mixed|string
		 *
		 * @deprecated 3.0.0
		 */
		public function calculate_product_max_discounts( $product_id, $price = 0, $_product = null ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::calculate_product_max_discounts', '3.0.0', 'YITH_WC_Points_Rewards_Redeeming::calculate_product_max_discount' );
			$product = wc_get_product( $product_id );
			return yith_points()->redeeming->calculate_product_max_discount( $product, $price );
		}

		/**
		 * Calculate the product max discount
		 *
		 * @param int   $product_id Product id.
		 * @param float $price Price.
		 *
		 * @return float|int|string
		 * @deprecated 3.0.0
		 */
		public function calculate_product_max_discounts_percentage( $product_id, $price = 0 ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::calculate_product_max_discounts_percentage', '3.0.0', 'YITH_WC_Points_Rewards_Redeeming::calculate_product_max_discount' );
			$product = wc_get_product( $product_id );
			return yith_points()->redeeming->calculate_product_max_discount( $product, $price );
		}

		/**
		 * Return the max discount that can be used in the cart fore rewards
		 * must be called after the function calculate_points_and_discount
		 *
		 * @return  float
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 */
		public function get_max_percentual_discount() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::get_max_percentual_discount', '3.0.0', 'YITH_WC_Points_Rewards_Redeeming::get_max_percentage_discount' );
			return yith_points()->redeeming->get_max_percentage_discount();
		}

		/**
		 * Register the coupon amount and points in the post meta of order
		 * if there's a rewards
		 *
		 * @param int $order_id Order id.
		 *
		 * @return mixed
		 * @deprecated 3.0.0
		 */
		public function add_order_meta( $order_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::add_order_meta', '3.0.0', 'YITH_WC_Points_Rewards_Orders::add_order_meta' );
			yith_points()->redeeming->order->add_order_meta( $order_id );
		}


		/**
		 * Remove user points
		 *
		 * @param int|WC_Order $order Order.
		 *
		 * @deprecated 3.0.0
		 */
		public function deduce_order_points( $order ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::deduce_order_points', '3.0.0', 'YITH_WC_Points_Rewards_Orders::deduce_order_points' );
			yith_points()->redeeming->order->deduce_order_points( $order );
		}

		/**
		 * Remove the coupons after that the order is created
		 *
		 * @param WC_Order $order Order.
		 * @param string   $status_from Previous status.
		 *
		 * @return void
		 * @deprecated 3.0.0
		 */
		public function clear_ywpar_coupon_after_create_order( $order, $status_from ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::clear_ywpar_coupon_after_create_order', '3.0.0', 'YITH_WC_Points_Rewards_Orders::clear_ywpar_coupon_after_create_order' );
			yith_points()->redeeming->order->deduce_order_points( $order, $status_from );
		}

		/**
		 * Removed the redeemed points when an order changes status from cancelled to complete
		 *
		 * @param int $order_id Order id.
		 *
		 * @return void
		 * @deprecated 3.0.0
		 */
		public function add_redeemed_order_points( $order_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::add_redeemed_order_points', '3.0.0', 'YITH_WC_Points_Rewards_Orders::add_redeemed_order_points' );
			yith_points()->redeeming->order->add_redeemed_order_points( $order_id );
		}

		/**
		 * Add the redeemed points when an order is cancelled
		 *
		 * @param int $order_id Order id.
		 *
		 * @return void
		 * @deprecated 3.0.0
		 */
		public function remove_redeemed_order_points( $order_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::remove_redeemed_order_points', '3.0.0', 'YITH_WC_Points_Rewards_Orders::remove_redeemed_order_points' );
			yith_points()->redeeming->order->remove_redeemed_order_points( $order_id );
		}

		/**
		 * Set user rewarded points, add $rewarded_points to the user meta '_ywpar_rewarded_points'
		 *
		 * @param int $user_id User id.
		 * @param int $rewarded_point Points.
		 *
		 * @return void
		 * @since 1.3.0
		 * @deprecated 3.0.0
		 */
		public function set_user_rewarded_points( $user_id, $rewarded_point ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::set_user_rewarded_points', '3.0.0', 'YITH_WC_Points_Rewards_Customer::add_rewarded_points' );
			$customer = ywpar_get_customer( $user_id );
			if ( ! $customer ) {
				return;
			}
			$customer->add_rewarded_points( $rewarded_point );
			if ( apply_filters( 'ywpar_flush_cache', false ) ) {
				wp_cache_flush();
			}
		}

		/**
		 * Get the rewarded points of a user from the user meta if exists or from the database if
		 * do not exist. In this last case the value is saved on the user meta
		 *
		 * @param int $user_id User id.
		 * @return int
		 * @since 1.3.0
		 * @deprecated 3.0.0
		 */
		public function get_user_rewarded_points( $user_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::get_user_rewarded_points', '3.0.0', 'YITH_WC_Points_Rewards_Customer::get_rewarded_points' );
			$customer = ywpar_get_customer( $user_id );
			if ( ! $customer ) {
				return;
			}

			return $customer->get_rewarded_points( true );
		}

		/**
		 * Return the coupon code
		 *
		 * @since  1.0.0
		 * @deprecated 1.2.0
		 */
		public function get_coupon_code() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::get_coupon_code', '3.0.0', 'YITH_WC_Points_Rewards_Redeeming::get_coupon_code_prefix' );
			return yith_points()->redeeming->get_coupon_code_prefix();
		}

		/**
		 * Check if a YWPAR Coupons is in the list
		 *
		 * @param  array|WC_Coupon $coupon_list Coupon list.
		 *
		 * @return bool|WC_Coupon
		 * @deprecated 3.0.0
		 */
		public function check_coupon_is_ywpar( $coupon_list ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::check_coupon_is_ywpar', '3.0.0', 'ywpar_check_redeeming_coupons' );
			return ywpar_check_redeeming_coupons( $coupon_list );
		}

		/**
		 * Return the coupon code attributes
		 *
		 * @param array  $args Arguments.
		 * @param string $code Coupon code.
		 *
		 * @return array
		 * @deprecated 3.0.0
		 * @since  1.0.0
		 */
		public function create_coupon_discount( $args, $code ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::create_coupon_discount', '3.0.0' );
			return array();
		}

		/**
		 * Return the discount amount
		 *
		 * @return float
		 * @deprecated 3.0.0
		 * @since  1.0.0
		 */
		public function get_discount_amount() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::get_discount_amount', '3.0.0' );
			return 0;
		}

		/**
		 * Return the conversion percentage rate rewards
		 *
		 * @param string $currency Currency.
		 * @return array
		 * @deprecated 3.0.0
		 */
		public function get_conversion_percentual_rate_rewards( $currency = '' ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::get_conversion_percentual_rate_rewards', '3.0.0', 'YITH_WC_Points_Rewards_Redeeming::get_conversion_rate_rewards' ); //phpcs:ignore
			return yith_points()->redeeming->get_conversion_rate_rewards( $currency = '', $customer = null ); //phpcs:ignore
		}

		/**
		 * Return the min and maximum discount of a product variable
		 *
		 * @param WC_Product $product Product.
		 *
		 * @return mixed
		 * @since    1.1.3
		 * @deprecated 3.0.0
		 */
		public function calculate_product_discounts_on_variable( $product ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::calculate_product_discounts_on_variable', '3.0.0', 'YITH_WC_Points_Rewards_Redeeming::calculate_product_max_discount' );
			$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
			return yith_points()->redeeming->calculate_product_max_discount( $product );
		}

		/**
		 * Return the maximum discount of a product
		 *
		 * @param int $product_id Product id.
		 *
		 * @return mixed
		 * @deprecated 3.0.0
		 * @since   1.1.3
		 */
		public function calculate_product_discounts( $product_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::calculate_product_discounts', '3.0.0', 'YITH_WC_Points_Rewards_Redeeming::calculate_product_max_discount' );
			$product = is_numeric( $product_id ) ? wc_get_product( $product_id ) : $product_id;
			return yith_points()->redeeming->calculate_product_max_discount( $product );
		}

		/**
		 * Remove the rewards points to the customer if in the orders a discount was used
		 *
		 * @param int|WC_Order $order Order.
		 *
		 * @deprecated 3.0.0
		 */
		public function rewards_order_points( $order ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Redeeming::rewards_order_points', '3.0.0', 'YITH_WC_Points_Rewards_Orders::add_redeemed_order_points' );
		}
	}
}

if ( ! function_exists( 'YITH_WC_Points_Rewards_Redemption' ) ) {
	/**
	 * Unique access to instance of YITH_WC_Points_Rewards_Redemption class
	 *
	 * @deprecated 3.0.0
	 * @since      1.0.0
	 */
	function YITH_WC_Points_Rewards_Redemption() {
		_deprecated_function( __FUNCTION__, '3.0.0', 'yith_points()->redeeming' );
		return yith_points()->redeeming;
	}
}

