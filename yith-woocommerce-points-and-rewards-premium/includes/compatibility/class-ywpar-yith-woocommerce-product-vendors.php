<?php
/**
 * Class to add compatibility with YITH WooCommerce Product Vendors
 *
 * @class   YITH_WC_Points_Rewards_WooCommerce_Currency_Switcher
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

/**
 * YWPAR_YITH_WooCommerce_Product_Vendors class to add compatibility with YITH WooCommerce Multivendor
 *
 * @class   YWPAR_YITH_WooCommerce_Product_Vendors
 * @package YITH WooCommerce Points and Rewards
 * @since   1.1.3
 */
if ( ! class_exists( 'YWPAR_YITH_WooCommerce_Product_Vendors' ) ) {

	/**
	 * Class YWPAR_YITH_WooCommerce_Product_Vendors
	 */
	class YWPAR_YITH_WooCommerce_Product_Vendors {

		/**
		 * Single instance of the class
		 *
		 * @var \YWPAR_YITH_WooCommerce_Product_Vendors
		 */
		protected static $instance;


		/**
		 * @var string
		 */
		protected $current_order = '';


		/**
		 * Returns single instance of the class
		 *
		 * @return \YWPAR_YITH_WooCommerce_Product_Vendors
		 * @since 1.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize class and registers actions and filters to be used
		 *
		 * @since  1.3.0
		 */
		public function __construct() {
			add_filter( 'ywpar_add_order_points', array( $this, 'check_if_is_suborder' ), 10, 2 );
			add_filter( 'ywpar_save_points_earned_from_cart', array( $this, 'save_points_earned_from_cart' ) );
		}

		/**
		 * Get points only if the main order is completed.
		 *
		 * @param $order_id
		 *
		 * @return bool
		 */
		public function save_points_earned_from_cart( $order_id ) {
			if ( $this->check_if_is_suborder( $order_id ) ) {
				return true;
			}
		}

		/**
		 * Check if the order is a sub order
		 *
		 * @param $order_id
		 *
		 * @return bool
		 * @internal param $result
		 */
		public function check_if_is_suborder( $check, $order_id ) {
			return wp_get_post_parent_id( $order_id ) ? true : $check;
		}



	}

}

/**
 * Unique access to instance of YWPAR_YITH_WooCommerce_Product_Vendors class
 *
 * @return \YWPAR_YITH_WooCommerce_Product_Vendors
 */
function YWPAR_YITH_WooCommerce_Product_Vendors() {
	return YWPAR_YITH_WooCommerce_Product_Vendors::get_instance();
}

YWPAR_YITH_WooCommerce_Product_Vendors();
