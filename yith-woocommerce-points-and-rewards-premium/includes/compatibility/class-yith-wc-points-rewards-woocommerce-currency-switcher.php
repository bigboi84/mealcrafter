<?php
/**
 * Class to integrate Points and Rewards with WooCommerce Currency Switcher
 *
 * @class   YITH_WC_Points_Rewards_WooCommerce_Currency_Switcher
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_WooCommerce_Currency_Switcher' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_WooCommerce_Currency_Switcher
	 */
	class YITH_WC_Points_Rewards_WooCommerce_Currency_Switcher {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_WooCommerce_Currency_Switcher
		 */
		protected static $instance;


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_WooCommerce_Currency_Switcher
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
			add_filter( 'ywpar_get_active_currency_list', array( $this, 'get_currency_list' ) );
			add_action( 'ywpar_before_currency_loop', array( $this, 'before_currency_loop' ) );
			add_action( 'ywpar_after_rewards_message', array( $this, 'after_rewards_message' ) );

			add_filter( 'ywpar_get_point_earned_price', array( $this, 'convert_price' ), 10, 2 );
			add_filter( 'ywpar_calculate_rewards_discount_max_discount_fixed', array( $this, 'convert_price' ), 10, 1 );
			add_filter( 'ywpar_calculate_rewards_discount_max_discount_percentual', array( $this, 'convert_price' ), 10, 1 );

			add_filter( 'ywpar_hide_value_for_max_discount', array( $this, 'hide_value_for_max_discount' ) );
			add_filter( 'ywpar_adjust_discount_value', array( $this, 'adjust_discount_value' ) );

		}

		/**
		 * Return the list of active currencies.
		 *
		 * @param array $currencies Currencies.
		 *
		 * @return array
		 * @since  3.0.0
		 */
		public function get_currency_list( $currencies ) {
			global $WOOCS; //phpcs:ignore
			$enabled_currencies = array_keys( $WOOCS->get_currencies() ); //phpcs:ignore
			return ! empty( $enabled_currencies ) ? $enabled_currencies : $currencies;
		}

		/**
		 * Remove some filters
		 */
		public function before_currency_loop() {
			global $WOOCS; //phpcs:ignore
			remove_filter( 'wc_price_args', array( $WOOCS, 'wc_price_args' ), 9999 ); //phpcs:ignore
			remove_filter( 'raw_woocommerce_price', array( $WOOCS, 'raw_woocommerce_price' ), 9999 ); //phpcs:ignore
		}

		/**
		 * Add some filter after rewards message
		 *
		 * @since  3.0.0
		 */
		public function after_rewards_message() {
			global $WOOCS; //phpcs:ignore
			add_filter( 'wc_price_args', array( $WOOCS, 'wc_price_args' ), 9999 ); //phpcs:ignore
			if ( ! $WOOCS->is_multiple_allowed ) { //phpcs:ignore
				add_filter( 'raw_woocommerce_price', array( $WOOCS, 'raw_woocommerce_price' ), 9999 ); //phpcs:ignore
			}
		}

		/**
		 * Convert price
		 *
		 * @param float  $price Price to convert.
		 * @param string $currency Currency.
		 *
		 * @return float|int
		 * @since  3.0.0
		 */
		public function convert_price( float $price, string $currency = '' ) {
			global $WOOCS; //phpcs:ignore
			if ( ! $WOOCS || $WOOCS->is_multiple_allowed ) { //phpcs:ignore
				return $price;
			}

			$currencies = $WOOCS->get_currencies(); //phpcs:ignore
			$currency   = empty( $currency ) ? $WOOCS->current_currency : $currency; //phpcs:ignore
			if ( isset( $currencies[ $currency ] ) ) {
				$price = $price * $currencies[ $currency ]['rate'];
			}

			return $price;
		}

		/**
		 * Hide value for max discount.
		 *
		 * @param float $discount Discount.
		 *
		 * @return float
		 * @since  3.0.0
		 */
		public function hide_value_for_max_discount( $discount ) {
			global $WOOCS; //phpcs:ignore
			if ( $WOOCS->is_multiple_allowed ) { //phpcs:ignore
				$currencies = $WOOCS->get_currencies(); //phpcs:ignore
				return $WOOCS->back_convert( $discount, $currencies[ $WOOCS->current_currency ]['rate'] ); //phpcs:ignore
			}
			remove_all_filters( 'ywpar_calculate_rewards_discount_max_discount_fixed' );
			remove_all_filters( 'ywpar_calculate_rewards_discount_max_discount_percentual' );

			return yith_points()->redeeming->calculate_rewards_discount();
		}

		/**
		 * Adjust the discount value
		 *
		 * @param float $discount Discount.
		 * @return float
		 * @since  3.0.0
		 */
		public function adjust_discount_value( $discount ) {
			global $WOOCS; //phpcs:ignore
			if ( $WOOCS->is_multiple_allowed ) { //phpcs:ignore
				$currencies = $WOOCS->get_currencies(); //phpcs:ignore
				$discount   = $WOOCS->back_convert( $discount, $currencies[ $WOOCS->current_currency ]['rate'] ); //phpcs:ignore
			}
			return $discount;
		}

	}

	/**
	 * Unique access to instance of YITH_WC_Points_Rewards_WooCommerce_Currency_Switcher class
	 *
	 * @return YITH_WC_Points_Rewards_WooCommerce_Currency_Switcher
	 */
	function ywpar_woocommerce_currency_switcher() { //phpcs:ignore
		return YITH_WC_Points_Rewards_WooCommerce_Currency_Switcher::get_instance();
	}

	ywpar_woocommerce_currency_switcher();
}
